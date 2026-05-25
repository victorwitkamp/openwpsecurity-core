<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Tests\Http;

use PHPUnit\Framework\TestCase;
use VictorWitkamp\OpenWPSecurity\Core\Http\IpAddressInspector;

final class IpAddressInspectorTest extends TestCase {
	private IpAddressInspector $inspector;

	protected function setUp(): void {
		$this->inspector = new IpAddressInspector();
	}

	public function test_resolves_first_valid_trusted_header_candidate(): void {
		self::assertSame(
			'203.0.113.10',
			$this->inspector->resolve_from_headers(
				array(
					'HTTP_X_FORWARDED_FOR' => 'not-an-ip, 203.0.113.10, 198.51.100.20',
					'REMOTE_ADDR'          => '198.51.100.30',
				),
				array(
					'HTTP_X_FORWARDED_FOR',
				)
			)
		);
	}

	public function test_remote_addr_is_used_when_not_listed_explicitly(): void {
		self::assertSame(
			'198.51.100.30',
			$this->inspector->resolve_from_headers(
				array(
					'REMOTE_ADDR' => '198.51.100.30',
				),
				array()
			)
		);
	}

	public function test_normalizes_ipv4_address_with_port(): void {
		self::assertSame(
			'198.51.100.30',
			$this->inspector->resolve_from_headers(
				array(
					'REMOTE_ADDR' => '198.51.100.30:443',
				),
				array()
			)
		);
	}

	public function test_normalizes_bracketed_ipv6_address_with_port(): void {
		self::assertSame(
			'2001:db8::1',
			$this->inspector->resolve_from_headers(
				array(
					'REMOTE_ADDR' => '[2001:db8::1]:443',
				),
				array()
			)
		);
	}

	public function test_private_and_reserved_addresses_are_private(): void {
		self::assertTrue( $this->inspector->is_private( '127.0.0.1' ) );
		self::assertTrue( $this->inspector->is_private( '10.0.0.1' ) );
		self::assertTrue( $this->inspector->is_private( '2001:db8::1' ) );
		self::assertFalse( $this->inspector->is_private( '8.8.8.8' ) );
	}
}
