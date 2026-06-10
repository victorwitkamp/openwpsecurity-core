<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Tests\Admin\Assets;

use PHPUnit\Framework\TestCase;
use VictorWitkamp\OpenWPSecurity\Core\Admin\Assets\AssetVersion;

final class AssetVersionTest extends TestCase {
	public function test_for_files_appends_the_latest_asset_timestamp(): void {
		$first_file  = tempnam( sys_get_temp_dir(), 'openwpsecurity-asset-' );
		$second_file = tempnam( sys_get_temp_dir(), 'openwpsecurity-asset-' );

		self::assertIsString( $first_file );
		self::assertIsString( $second_file );

		touch( $first_file, 100 );
		touch( $second_file, 200 );

		self::assertSame( '1.2.3-200', ( new AssetVersion() )->for_files( array( $first_file, $second_file ), '1.2.3' ) );

		unlink( $first_file );
		unlink( $second_file );
	}

	public function test_for_files_returns_the_base_version_when_assets_are_missing(): void {
		self::assertSame( '1.2.3', ( new AssetVersion() )->for_files( array( __DIR__ . '/missing.js' ), '1.2.3' ) );
	}
}
