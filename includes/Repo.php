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

use FileRepo;
use MediaWiki\MediaWikiServices;

class Repo extends FileRepo {

	protected string $foreignRepoName;
	protected FileRepo $foreignRepo;

	/** @inheritDoc */
	protected $fileFactory = [ APIBackedLocalFile::class, 'newFromTitle' ];

	/** @inheritDoc */
	public function __construct( array $info = null ) {
		parent::__construct( $info );
		$this->foreignRepoName = $info['foreignRepo'];
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
}
