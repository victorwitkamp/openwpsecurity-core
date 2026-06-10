<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Security\Ban;

use VictorWitkamp\OpenWPSecurity\Core\Database\TableColumn;
use VictorWitkamp\OpenWPSecurity\Core\Database\TableIndex;
use VictorWitkamp\OpenWPSecurity\Core\Database\TableReference;
use VictorWitkamp\OpenWPSecurity\Core\Database\TableSchema;
use VictorWitkamp\OpenWPSecurity\Core\Database\TableSchemaInstaller;

abstract class AbstractTemporaryBanCounterStore implements TableReference {
	private const DB_VERSION = '1.0.0';

	private TableSchemaInstaller $schema_installer;
	private string $version_option_name;

	public function __construct( TableSchemaInstaller $schema_installer, string $version_option_name ) {
		$this->schema_installer    = $schema_installer;
		$this->version_option_name = $version_option_name;
	}

	public function maybe_upgrade_schema(): void {
		$this->schema_installer->maybe_upgrade_schema( $this->table_schema() );
	}

	public function count_for_ip( string $ip_address ): int {
		global $wpdb;

		if ( '' === $ip_address ) {
			return 0;
		}

		$table = $this->name();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated internally from $wpdb->prefix.
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ban_count FROM {$table} WHERE ip_address = %s LIMIT 1",
				$ip_address
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public function increment_for_ip( string $ip_address ): int {
		global $wpdb;

		if ( '' === $ip_address ) {
			return 0;
		}

		$table      = $this->name();
		$updated_at = current_time( 'mysql', true );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated internally from $wpdb->prefix.
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table} (ip_address, ban_count, updated_at) VALUES (%s, 1, %s)
				ON DUPLICATE KEY UPDATE ban_count = ban_count + 1, updated_at = VALUES(updated_at)",
				$ip_address,
				$updated_at
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $this->count_for_ip( $ip_address );
	}

	public function clear_for_ip( string $ip_address ): bool {
		global $wpdb;

		$deleted = $wpdb->delete( $this->name(), array( 'ip_address' => $ip_address ), array( '%s' ) );

		return false !== $deleted && $deleted > 0;
	}

	private function table_schema(): TableSchema {
		return new TableSchema(
			$this,
			$this->version_option_name,
			self::DB_VERSION,
			array(
				new TableColumn( 'ip_address', 'ip_address varchar(45) NOT NULL', '', '%s' ),
				new TableColumn( 'ban_count', 'ban_count int(10) unsigned NOT NULL DEFAULT 0', 0, '%d' ),
				new TableColumn( 'updated_at', 'updated_at datetime NOT NULL', '', '%s' ),
			),
			array(
				new TableIndex( 'PRIMARY KEY  (ip_address)' ),
				new TableIndex( 'KEY updated_at (updated_at)' ),
			)
		);
	}
}
