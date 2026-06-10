<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Database;

final class TableSchemaInstaller {
	public function maybe_upgrade_schema( TableSchema $schema ): void {
		$current_version = (string) get_option( $schema->version_option_name(), '' );

		if ( $current_version === $schema->version() ) {
			return;
		}

		$this->create_table( $schema );
		update_option( $schema->version_option_name(), $schema->version(), false );
	}

	private function create_table( TableSchema $schema ): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( $schema->create_sql( $wpdb->get_charset_collate() ) );
	}
}
