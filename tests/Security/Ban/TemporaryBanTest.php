<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Tests\Security\Ban;

use PHPUnit\Framework\TestCase;
use VictorWitkamp\OpenWPSecurity\Core\Security\Ban\TemporaryBan;

final class TemporaryBanTest extends TestCase {
	public function test_temporary_ban_round_trips_through_array_data(): void {
		$data = array(
			'ip_address'    => '203.0.113.10',
			'created_at'    => 100,
			'expires_at'    => 200,
			'source'        => 'firewall',
			'scope'         => 'All request types',
			'reason'        => 'Rate limit exceeded.',
			'details'       => 'Triggered by XML-RPC.',
			'evidence_json' => '{"trigger_request_type":"xmlrpc"}',
		);

		self::assertSame( $data, TemporaryBan::from_array( $data )->to_array() );
		self::assertSame( array( 'trigger_request_type' => 'xmlrpc' ), TemporaryBan::from_array( $data )->evidence() );
	}
}
