<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Security\Ban;

use VictorWitkamp\OpenWPSecurity\Core\Database\TableColumn;
use VictorWitkamp\OpenWPSecurity\Core\Database\TableIndex;
use VictorWitkamp\OpenWPSecurity\Core\Database\TableReference;
use VictorWitkamp\OpenWPSecurity\Core\Database\TableSchema;
use VictorWitkamp\OpenWPSecurity\Core\Database\TableSchemaInstaller;

abstract class AbstractTemporaryBanRepository implements TableReference, TemporaryBanRepository {
	private const DB_VERSION = '1.0.0';

	private TableSchemaInstaller $schema_installer;
	private string $version_option_name;
	private string $cache_group;

	public function __construct( TableSchemaInstaller $schema_installer, string $version_option_name, string $cache_group ) {
		$this->schema_installer    = $schema_installer;
		$this->version_option_name = $version_option_name;
		$this->cache_group         = $cache_group;
	}

	public function maybe_upgrade_schema(): void {
		$this->schema_installer->maybe_upgrade_schema( $this->table_schema() );
	}

	public function find_active_temporary_ban( string $ip_address ): ?TemporaryBan {
		global $wpdb;

		if ( '' === $ip_address ) {
			return null;
		}

		$cache_key = $this->cache_key( $ip_address );
		$found     = false;
		$cached    = wp_cache_get( $cache_key, $this->cache_group, false, $found );

		if ( $found && is_array( $cached ) ) {
			$temporary_ban = empty( $cached ) ? null : TemporaryBan::from_array( $cached );

			if ( null === $temporary_ban || $temporary_ban->expires_at() > time() ) {
				return $temporary_ban;
			}

			wp_cache_delete( $cache_key, $this->cache_group );
		}

		$table = $this->name();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated internally from $wpdb->prefix.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT ip_address, created_at, expires_at, source, scope, reason, details, evidence_json FROM {$table} WHERE ip_address = %s AND expires_at > %s LIMIT 1",
				$ip_address,
				current_time( 'mysql', true )
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! is_array( $row ) ) {
			wp_cache_set( $cache_key, array(), $this->cache_group, 15 );
			return null;
		}

		$temporary_ban = TemporaryBan::from_row( $row );
		$this->cache_temporary_ban( $temporary_ban );

		return $temporary_ban;
	}

	/**
	 * @return list<TemporaryBan>
	 */
	public function get_active_temporary_bans(): array {
		global $wpdb;

		$this->purge_expired_temporary_bans();
		$table = $this->name();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated internally from $wpdb->prefix.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ip_address, created_at, expires_at, source, scope, reason, details, evidence_json FROM {$table} WHERE expires_at > %s ORDER BY expires_at ASC",
				current_time( 'mysql', true )
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map(
			function ( array $row ): TemporaryBan {
				$temporary_ban = TemporaryBan::from_row( $row );
				$this->cache_temporary_ban( $temporary_ban );
				return $temporary_ban;
			},
			$rows
		);
	}

	public function count_active_temporary_bans(): int {
		global $wpdb;

		$table = $this->name();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated internally from $wpdb->prefix.
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE expires_at > %s",
				current_time( 'mysql', true )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public function save_temporary_ban( TemporaryBan $temporary_ban ): bool {
		global $wpdb;

		$table = $this->name();
		$row   = $temporary_ban->to_row();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated internally from $wpdb->prefix.
		$result = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table} (ip_address, created_at, expires_at, source, scope, reason, details, evidence_json)
				VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
				ON DUPLICATE KEY UPDATE created_at = VALUES(created_at), expires_at = VALUES(expires_at), source = VALUES(source), scope = VALUES(scope), reason = VALUES(reason), details = VALUES(details), evidence_json = VALUES(evidence_json)",
				$row['ip_address'],
				$row['created_at'],
				$row['expires_at'],
				$row['source'],
				$row['scope'],
				$row['reason'],
				$row['details'],
				$row['evidence_json']
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( false === $result ) {
			return false;
		}

		$this->cache_temporary_ban( $temporary_ban );
		return true;
	}

	public function remove_temporary_ban( string $ip_address ): bool {
		global $wpdb;

		if ( '' === $ip_address ) {
			return false;
		}

		$deleted = $wpdb->delete( $this->name(), array( 'ip_address' => $ip_address ), array( '%s' ) );
		wp_cache_delete( $this->cache_key( $ip_address ), $this->cache_group );

		return false !== $deleted && $deleted > 0;
	}

	public function clear_temporary_bans(): int {
		global $wpdb;

		$active_bans = $this->get_active_temporary_bans();
		$table       = $this->name();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated internally from $wpdb->prefix.
		$wpdb->query( "DELETE FROM {$table}" );

		foreach ( $active_bans as $temporary_ban ) {
			wp_cache_delete( $this->cache_key( $temporary_ban->ip_address() ), $this->cache_group );
		}

		return count( $active_bans );
	}

	public function purge_expired_temporary_bans(): int {
		global $wpdb;

		$table = $this->name();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated internally from $wpdb->prefix.
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE expires_at <= %s",
				current_time( 'mysql', true )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return false === $deleted ? 0 : (int) $deleted;
	}

	private function table_schema(): TableSchema {
		return new TableSchema(
			$this,
			$this->version_option_name,
			self::DB_VERSION,
			array(
				new TableColumn( 'ip_address', 'ip_address varchar(45) NOT NULL', '', '%s' ),
				new TableColumn( 'created_at', 'created_at datetime NOT NULL', '', '%s' ),
				new TableColumn( 'expires_at', 'expires_at datetime NOT NULL', '', '%s' ),
				new TableColumn( 'source', "source varchar(80) NOT NULL DEFAULT ''", '', '%s' ),
				new TableColumn( 'scope', "scope varchar(120) NOT NULL DEFAULT ''", '', '%s' ),
				new TableColumn( 'reason', "reason varchar(255) NOT NULL DEFAULT ''", '', '%s' ),
				new TableColumn( 'details', 'details text NULL', '', '%s' ),
				new TableColumn( 'evidence_json', 'evidence_json longtext NULL', '', '%s' ),
			),
			array(
				new TableIndex( 'PRIMARY KEY  (ip_address)' ),
				new TableIndex( 'KEY expires_at (expires_at)' ),
			)
		);
	}

	private function cache_temporary_ban( TemporaryBan $temporary_ban ): void {
		$ttl = max( 1, $temporary_ban->expires_at() - time() );
		wp_cache_set( $this->cache_key( $temporary_ban->ip_address() ), $temporary_ban->to_array(), $this->cache_group, $ttl );
	}

	private function cache_key( string $ip_address ): string {
		return hash( 'sha256', $ip_address );
	}
}
