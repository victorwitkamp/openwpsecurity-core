<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Tests\Configuration;

use PHPUnit\Framework\TestCase;
use VictorWitkamp\OpenWPSecurity\Core\Configuration\SettingsInputSanitizer;

final class SettingsInputSanitizerTest extends TestCase {
	private SettingsInputSanitizer $sanitizer;

	protected function setUp(): void {
		$this->sanitizer = new SettingsInputSanitizer();
	}

	public function test_lines_trim_and_remove_empty_values(): void {
		self::assertSame(
			array( 'one', 'two', 'three' ),
			$this->sanitizer->lines( " one \r\n\r\n two \n three " )
		);
	}

	public function test_headers_trim_and_remove_empty_values(): void {
		self::assertSame(
			array( 'REMOTE_ADDR', 'HTTP_CF_CONNECTING_IP' ),
			$this->sanitizer->headers( ' REMOTE_ADDR, , HTTP_CF_CONNECTING_IP ' )
		);
	}

	public function test_ip_addresses_trim_remove_empty_values_and_deduplicate(): void {
		self::assertSame(
			array( '192.0.2.10', '2001:db8::1' ),
			$this->sanitizer->ip_addresses( array( ' 192.0.2.10 ', '', '192.0.2.10', '2001:db8::1' ) )
		);
	}
}
