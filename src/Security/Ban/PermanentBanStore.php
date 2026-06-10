<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Security\Ban;

use VictorWitkamp\OpenWPSecurity\Core\Database\TableReference;
use VictorWitkamp\OpenWPSecurity\Core\Database\TableWriter;
use VictorWitkamp\OpenWPSecurity\Core\Http\IpAddressInspector;

final class PermanentBanStore {
	private TableReference $ban_table;
	private PermanentBanSchema $ban_schema;
	private IpAddressInspector $ip_address_inspector;
	private \Closure $ban_event_logger;
	private string $default_source;
	private TableWriter $table_writer;

	public function __construct( TableReference $ban_table, PermanentBanSchema $ban_schema, IpAddressInspector $ip_address_inspector, \Closure $ban_event_logger, string $default_source = 'manual' ) {
		$this->ban_table            = $ban_table;
		$this->ban_schema           = $ban_schema;
		$this->ip_address_inspector = $ip_address_inspector;
		$this->ban_event_logger     = $ban_event_logger;
		$this->default_source       = $default_source;
		$this->table_writer         = new TableWriter( $ban_table, $ban_schema->table_schema() );
	}

	public function ensure_storage(): void {
		$this->ban_schema->maybe_upgrade_schema();
	}

	public function get_bans( int $limit, int $offset = 0 ): array {
		global $wpdb;

		$table = $this->ban_table->name();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated internally from $wpdb->prefix.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared immediately before execution.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT created_at, ip_address, country_code, country_name, source, reason, request_uri, user_agent, evidence_json FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d",
				max( 1, $limit ),
				max( 0, $offset )
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_values(
			array_filter(
				array_map(
					static function ( array $row ): array {
						return PermanentBan::from_row( $row )->to_array();
					},
					$rows
				),
				static function ( array $ban ): bool {
					return '' !== $ban['ip_address'];
				}
			)
		);
	}

	public function count_bans(): int {
		global $wpdb;

		$table = $this->ban_table->name();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated internally from $wpdb->prefix.
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
	}

	public function get_ban_for_ip( string $ip ): array {
		global $wpdb;

		if ( '' === $ip ) {
			return array();
		}

		$table = $this->ban_table->name();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated internally from $wpdb->prefix.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT created_at, ip_address, country_code, country_name, source, reason, request_uri, user_agent, evidence_json FROM {$table} WHERE ip_address = %s LIMIT 1",
				$ip
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return is_array( $row ) ? PermanentBan::from_row( $row )->to_array() : array();
	}

	public function is_banned( string $ip ): bool {
		return array() !== $this->get_ban_for_ip( $ip );
	}

	public function create_ban( string $ip, string $reason, string $source = '', array $context = array() ): void {
		if ( '' === $source ) {
			$source = $this->default_source;
		}

		if ( '' === $ip || $this->ip_address_inspector->is_private( $ip ) ) {
			return;
		}

		if ( $this->is_banned( $ip ) ) {
			return;
		}

		$ban      = new PermanentBan(
			$ip,
			(string) ( $context['country_code'] ?? '' ),
			(string) ( $context['country_name'] ?? '' ),
			$source,
			$reason,
			(string) ( $context['request_uri'] ?? '' ),
			(string) ( $context['user_agent'] ?? '' ),
			$context ? wp_json_encode( $context ) : ''
		);
		$inserted = $this->table_writer->insert( $ban->to_insert_row() );

		if ( ! $inserted ) {
			return;
		}

		( $this->ban_event_logger )( $ip, $reason, $source, $context );
	}

	public function remove_ban( string $ip ): bool {
		global $wpdb;

		if ( '' === $ip ) {
			return false;
		}

		$deleted = $wpdb->delete(
			$this->ban_table->name(),
			array( 'ip_address' => $ip ),
			array( '%s' )
		);

		return false !== $deleted && $deleted > 0;
	}

	public function clear_bans(): int {
		global $wpdb;

		$table = $this->ban_table->name();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated internally from $wpdb->prefix.
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

		if ( $count <= 0 ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated internally from $wpdb->prefix.
		$wpdb->query( "DELETE FROM {$table}" );

		return $count;
	}

	public function count_since( ?int $period_seconds = null ): int {
		global $wpdb;

		$table  = $this->ban_table->name();
		$params = array();
		$sql    = "SELECT COUNT(*) FROM {$table} WHERE 1=1";

		if ( null !== $period_seconds ) {
			$sql     .= ' AND created_at >= %s';
			$params[] = gmdate( 'Y-m-d H:i:s', time() - $period_seconds );
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated internally from $wpdb->prefix.
		if ( empty( $params ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query contains no user input.
			return (int) $wpdb->get_var( $sql );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared immediately before execution.
		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}
