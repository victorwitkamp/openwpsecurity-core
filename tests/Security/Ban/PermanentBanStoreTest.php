<?php

declare(strict_types=1);

namespace {
	if ( ! defined( 'ARRAY_A' ) ) {
		define( 'ARRAY_A', 'ARRAY_A' );
	}

	function current_time( string $type, bool $gmt = false ): string {
		return '2026-05-28 12:00:00';
	}
}

namespace VictorWitkamp\OpenWPSecurity\Core\Security\Ban {
	function wp_json_encode( $value ) {
		return json_encode( $value );
	}
}

namespace VictorWitkamp\OpenWPSecurity\Core\Tests\Security\Ban {
	use PHPUnit\Framework\TestCase;
	use VictorWitkamp\OpenWPSecurity\Core\Database\TableSchemaInstaller;
	use VictorWitkamp\OpenWPSecurity\Core\Database\WordPressTableReference;
	use VictorWitkamp\OpenWPSecurity\Core\Http\IpAddressInspector;
	use VictorWitkamp\OpenWPSecurity\Core\Security\Ban\PermanentBanSchema;
	use VictorWitkamp\OpenWPSecurity\Core\Security\Ban\PermanentBanStore;

	final class PermanentBanStoreTest extends TestCase {
		private PermanentBanStore $store;
		private array $logged_events;
		private TestWpdb $wpdb;

		protected function setUp(): void {
			global $wpdb;

			$this->wpdb          = new TestWpdb();
			$wpdb                = $this->wpdb;
			$this->logged_events = array();
			$table               = new WordPressTableReference( 'openwpsecurity_test_bans' );
			$schema              = new PermanentBanSchema( new TableSchemaInstaller(), $table, 'openwpsecurity_test_bans_db_version' );
			$this->store         = new PermanentBanStore(
				$table,
				$schema,
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
			self::assertSame( array(), $this->store->get_bans( 25 ) );
		}

		public function test_remove_ban_returns_false_when_ip_is_not_banned(): void {
			self::assertFalse( $this->store->remove_ban( '8.8.4.4' ) );
		}

		public function test_clear_bans_removes_all_bans_and_returns_count(): void {
			$this->store->create_ban( '8.8.8.8', 'first' );
			$this->store->create_ban( '1.1.1.1', 'second' );

			self::assertSame( 2, $this->store->clear_bans() );
			self::assertSame( 0, $this->store->count_bans() );
			self::assertSame( array(), $this->store->get_bans( 25 ) );
		}

		public function test_clear_bans_returns_zero_when_store_is_empty(): void {
			self::assertSame( 0, $this->store->clear_bans() );
		}

		public function test_get_bans_returns_a_database_ordered_page(): void {
			$this->store->create_ban( '8.8.8.8', 'first' );
			$this->store->create_ban( '1.1.1.1', 'second' );

			$rows = $this->store->get_bans( 1, 1 );

			self::assertCount( 1, $rows );
			self::assertSame( '1.1.1.1', $rows[0]['ip_address'] );
		}
	}

	final class TestWpdb {
		public string $prefix = 'wp_';
		private array $rows = array();

		public function insert( string $table, array $data, array $formats ): bool {
			$data['id']      = count( $this->rows[ $table ] ?? array() ) + 1;
			$this->rows[ $table ][] = $data;

			return true;
		}

		public function delete( string $table, array $where, array $formats ) {
			$before              = count( $this->rows[ $table ] ?? array() );
			$this->rows[ $table ] = array_values(
				array_filter(
					$this->rows[ $table ] ?? array(),
					static function ( array $row ) use ( $where ): bool {
						return (string) ( $row['ip_address'] ?? '' ) !== (string) $where['ip_address'];
					}
				)
			);

			return $before - count( $this->rows[ $table ] );
		}

		public function get_results( $query, string $output = ARRAY_A ): array {
			$table = $this->table_from_query( $query );
			$rows  = $this->rows[ $table ] ?? array();

			usort(
				$rows,
				static function ( array $left, array $right ): int {
					return strcmp( (string) ( $right['created_at'] ?? '' ), (string) ( $left['created_at'] ?? '' ) );
				}
			);

			if ( is_array( $query ) && str_contains( (string) $query['query'], 'LIMIT %d OFFSET %d' ) ) {
				$limit  = (int) ( $query['params'][0] ?? 25 );
				$offset = (int) ( $query['params'][1] ?? 0 );
				$rows   = array_slice( $rows, $offset, $limit );
			}

			return $rows;
		}

		public function get_row( $query, string $output = ARRAY_A ): ?array {
			$table = $this->table_from_query( $query );
			$ip    = is_array( $query ) ? (string) ( $query['params'][0] ?? '' ) : '';

			foreach ( $this->rows[ $table ] ?? array() as $row ) {
				if ( (string) ( $row['ip_address'] ?? '' ) === $ip ) {
					return $row;
				}
			}

			return null;
		}

		public function get_var( $query ): int {
			$table = $this->table_from_query( $query );

			return count( $this->rows[ $table ] ?? array() );
		}

		public function query( $query ): bool {
			$table = $this->table_from_query( $query );

			$this->rows[ $table ] = array();

			return true;
		}

		public function prepare( string $query, ...$params ): array {
			return array(
				'query'  => $query,
				'params' => is_array( $params[0] ?? null ) ? $params[0] : $params,
			);
		}

		public function get_charset_collate(): string {
			return '';
		}

		private function table_from_query( $query ): string {
			$sql = is_array( $query ) ? (string) $query['query'] : (string) $query;

			if ( preg_match( '/FROM\s+([a-zA-Z0-9_]+)/', $sql, $matches ) ) {
				return $matches[1];
			}

			if ( preg_match( '/DELETE\s+FROM\s+([a-zA-Z0-9_]+)/', $sql, $matches ) ) {
				return $matches[1];
			}

			return 'wp_openwpsecurity_test_bans';
		}
	}
}
