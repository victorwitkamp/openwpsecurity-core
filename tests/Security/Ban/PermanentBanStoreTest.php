<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Security\Ban {
	function get_option( string $option_name, $default = false ) {
		return TestWordPressOptions::$options[ $option_name ] ?? $default;
	}

	function add_option( string $option_name, $value, string $deprecated = '', bool $autoload = true ): bool {
		TestWordPressOptions::$options[ $option_name ] = $value;

		return true;
	}

	function update_option( string $option_name, $value, bool $autoload = true ): bool {
		TestWordPressOptions::$options[ $option_name ] = $value;

		return true;
	}

	function current_time( string $type, bool $gmt = false ): string {
		return '2026-05-28 12:00:00';
	}

	final class TestWordPressOptions {
		public static array $options = array();
	}
}

namespace VictorWitkamp\OpenWPSecurity\Core\Tests\Security\Ban {
	use PHPUnit\Framework\TestCase;
	use VictorWitkamp\OpenWPSecurity\Core\Http\IpAddressInspector;
	use VictorWitkamp\OpenWPSecurity\Core\Security\Ban\PermanentBanStore;
	use VictorWitkamp\OpenWPSecurity\Core\Security\Ban\TestWordPressOptions;

	final class PermanentBanStoreTest extends TestCase {
		private PermanentBanStore $store;
		private array $logged_events;

		protected function setUp(): void {
			TestWordPressOptions::$options = array();
			$this->logged_events           = array();
			$this->store                   = new PermanentBanStore(
				'openwpsecurity_test_bans',
				new IpAddressInspector(),
				function ( string $ip, string $reason, string $source, array $context ): void {
					$this->logged_events[] = compact( 'ip', 'reason', 'source', 'context' );
				},
				'test'
			);
		}

		public function test_remove_ban_deletes_existing_ban(): void {
			$this->store->create_ban( '8.8.8.8', 'manual test' );

			self::assertTrue( $this->store->remove_ban( '8.8.8.8' ) );
			self::assertFalse( $this->store->is_banned( '8.8.8.8' ) );
			self::assertSame( array(), $this->store->get_all_bans() );
		}

		public function test_remove_ban_returns_false_when_ip_is_not_banned(): void {
			self::assertFalse( $this->store->remove_ban( '8.8.4.4' ) );
		}

		public function test_clear_bans_removes_all_bans_and_returns_count(): void {
			$this->store->create_ban( '8.8.8.8', 'first' );
			$this->store->create_ban( '1.1.1.1', 'second' );

			self::assertSame( 2, $this->store->clear_bans() );
			self::assertSame( 0, $this->store->count_bans() );
			self::assertSame( array(), $this->store->get_all_bans() );
		}

		public function test_clear_bans_returns_zero_when_store_is_empty(): void {
			self::assertSame( 0, $this->store->clear_bans() );
		}
	}
}
