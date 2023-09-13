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

use FileBackendGroup;
use Job;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Status\Status;
use Title;

class DownloadForeignFileJob extends Job {

	private HttpRequestFactory $httpRequestFactory;

	private FileBackendGroup $fileBackendGroup;

	public function __construct(
		Title $title,
		array $params,
		HttpRequestFactory $httpRequestFactory,
		FileBackendGroup $fileBackendGroup
	) {
		parent::__construct( 'downloadForeignFile', $title, $params );
		$this->httpRequestFactory = $httpRequestFactory;
		$this->fileBackendGroup = $fileBackendGroup;
	}

	public function run() {
		$fileUrl = $this->params['fileUrl'];
		$destPath = $this->params['destPath'];

		wfDebugLog( 'HybridFileRepo', "Download job started: $destPath" );
		$backend = $this->fileBackendGroup->backendFromPath( $destPath );
		if ( !$backend ) {
			wfDebugLog( 'HybridFileRepo', "Download job get file backend failed: $destPath" );
			return false;
		}

		$response = $this->httpRequestFactory->get( $fileUrl );
		if ( !$response ) {
			wfDebugLog( 'HybridFileRepo', "Download job get URL failed: $fileUrl" );
			return false;
		} else {
			wfDebugLog( 'HybridFileRepo', "Download job get URL done: $fileUrl" );
		}

		$status = $backend->create( [
			'dst' => $destPath,
			'content' => $response,
			'overwrite' => false,
		] );
		if ( !$status->isGood() ) {
			$error = Status::wrap( $status )->getWikiText( false, false, 'en' );
			wfDebugLog( 'HybridFileRepo', "Download job create file failed: $error" );
			return false;
		}
		wfDebugLog( 'HybridFileRepo', "Download job for $destPath done." );

		return true;
	}
}
