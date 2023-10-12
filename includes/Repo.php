<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @author Func <Funcer@outlook.com>
 */

namespace MediaWiki\Extension\HybridFileRepo;

use FileBackend;
use FileRepo;
use JobSpecification;
use MediaWiki\MediaWikiServices;

class Repo extends FileRepo {

	protected string $foreignRepoName;
	protected bool $replaceUnderscore;
	protected bool $downloadForeignFile;
	protected FileRepo $foreignRepo;

	/** @inheritDoc */
	protected $fileFactory = [ APIBackedLocalFile::class, 'newFromTitle' ];

	/** @inheritDoc */
	public function __construct( array $info = null ) {
		parent::__construct( $info );
		$this->foreignRepoName = $info['foreignRepo'];
		$this->replaceUnderscore = $info['replaceUnderscore'] ?? false;
		$this->downloadForeignFile = $info['downloadForeignFile'] ?? false;
	}

	public function getForeignRepo() {
		if ( !isset( $this->foreignRepo ) ) {
			$foreignRepo = MediaWikiServices::getInstance()->getRepoGroup()
				->getRepoByName( $this->foreignRepoName );
			if ( !$foreignRepo ) {
				throw new \RuntimeException( "The 'foreignRepo' config is not valid: `{$this->foreignRepoName}`." );
			}
			$this->foreignRepo = $foreignRepo;
		}
		return $this->foreignRepo;
	}

	/** @inheritDoc */
	protected function resolveToStoragePathIfVirtual( $path ) {
		$path = parent::resolveToStoragePathIfVirtual( $path );
		if ( $this->replaceUnderscore && !str_starts_with( $path, '/tmp' ) ) {
			$path = str_replace( '_', ' ', $path );
		}
		return $path;
	}

	public function fileExistsBatch( array $files ) {
		$result = parent::fileExistsBatch( $files );
		if ( !$this->downloadForeignFile ) {
			return $result;
		}

		$downloadJobs = [];
		foreach ( $files as $key => $file ) {
			if ( $result[$key] ) {
				continue;
			}

			[ , , $fileName ] = FileBackend::splitStoragePath( $file );
			$foreignFile = $this->getForeignRepo()->findFile( $fileName );
			if ( !$foreignFile ) {
				continue;
			}

			$destPath = $this->resolveToStoragePathIfVirtual( $file );
			$downloadJobs[] = new JobSpecification(
				'downloadForeignFile', [
					'fileUrl' => $foreignFile->getUrl(),
					'destPath' => $destPath,
				],
				[ 'removeDuplicates' => true ]
			);
			wfDebugLog( 'HybridFileRepo', "Download job queued for $destPath" );
		}
		if ( $downloadJobs ) {
			MediaWikiServices::getInstance()->getJobQueueGroup()->lazyPush( $downloadJobs );
		}

		return $result;
	}
}
