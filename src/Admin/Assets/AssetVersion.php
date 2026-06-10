<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Admin\Assets;

final class AssetVersion {
	public function for_files( array $asset_files, string $base_version ): string {
		$latest = 0;

		foreach ( $asset_files as $asset_file ) {
			if ( file_exists( $asset_file ) ) {
				$latest = max( $latest, (int) filemtime( $asset_file ) );
			}
		}

		return $latest > 0 ? $base_version . '-' . $latest : $base_version;
	}
}
