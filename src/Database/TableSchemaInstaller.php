<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Database;

final class TableSchemaInstaller {
	public function maybe_upgrade_schema( TableSchema $schema ): void {
		$current_version = (string) get_option( $schema->version_option_name(), '' );
		$table_exists    = $this->table_exists( $schema->table()->name() );

		if ( ! $table_exists || $current_version !== $schema->version() ) {
			$this->create_table( $schema );
		}

		update_option( $schema->version_option_name(), $schema->version(), false );
	}

	private function create_table( TableSchema $schema ): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( $schema->create_sql( $wpdb->get_charset_collate() ) );
	}

	private function table_exists( string $table_name ): bool {
		global $wpdb;

		$match = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$table_name
			)
		);

		return is_string( $match ) && $match === $table_name;
	}
}
