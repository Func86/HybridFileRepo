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

use File;
use MediaWiki\Permissions\Authority;
use MediaWiki\User\UserIdentity;
use UnregisteredLocalFile;

class APIBackedLocalFile extends UnregisteredLocalFile {

	protected ?File $foreignFile = null;

	/** @inheritDoc */
	public function getDescription( $audience = self::FOR_PUBLIC, Authority $performer = null ) {
		if ( $this->getForeignFile() ) {
			return $this->getForeignFile()->getDescription();
		}
		return parent::getDescription( $audience, $performer );
	}

	/** @inheritDoc */
	public function getUploader( int $audience = self::FOR_PUBLIC, Authority $performer = null ): ?UserIdentity {
		if ( $this->getForeignFile() ) {
			return $this->getForeignFile()->getUploader();
		}
		return parent::getUploader( $audience, $performer );
	}

	protected function getForeignFile() {
		$this->foreignFile ??= $this->repo->getForeignRepo()->findFile( $this->getName() );

		return $this->foreignFile;
	}
}
