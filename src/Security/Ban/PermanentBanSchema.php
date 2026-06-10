<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Security\Ban;

use VictorWitkamp\OpenWPSecurity\Core\Database\TableColumn;
use VictorWitkamp\OpenWPSecurity\Core\Database\TableIndex;
use VictorWitkamp\OpenWPSecurity\Core\Database\TableReference;
use VictorWitkamp\OpenWPSecurity\Core\Database\TableSchema;
use VictorWitkamp\OpenWPSecurity\Core\Database\TableSchemaInstaller;

final class PermanentBanSchema {
	private const VERSION = '1.0.0';

	private TableSchemaInstaller $schema_installer;
	private TableSchema $schema;

	public function __construct( TableSchemaInstaller $schema_installer, TableReference $table, string $version_option_name ) {
		$this->schema_installer = $schema_installer;
		$this->schema           = new TableSchema(
			$table,
			$version_option_name,
			self::VERSION,
			array(
				new TableColumn( 'id', 'id bigint(20) unsigned NOT NULL AUTO_INCREMENT' ),
				new TableColumn( 'created_at', 'created_at datetime NOT NULL', '', '%s' ),
				new TableColumn( 'ip_address', 'ip_address varchar(45) NOT NULL', '', '%s' ),
				new TableColumn( 'country_code', "country_code varchar(12) NOT NULL DEFAULT ''", '', '%s' ),
				new TableColumn( 'country_name', "country_name varchar(191) NOT NULL DEFAULT ''", '', '%s' ),
				new TableColumn( 'source', "source varchar(80) NOT NULL DEFAULT ''", '', '%s' ),
				new TableColumn( 'reason', "reason varchar(255) NOT NULL DEFAULT ''", '', '%s' ),
				new TableColumn( 'request_uri', 'request_uri text NULL', '', '%s' ),
				new TableColumn( 'user_agent', 'user_agent text NULL', '', '%s' ),
				new TableColumn( 'evidence_json', 'evidence_json longtext NULL', '', '%s' ),
			),
			array(
				new TableIndex( 'PRIMARY KEY  (id)' ),
				new TableIndex( 'UNIQUE KEY ip_address (ip_address)' ),
				new TableIndex( 'KEY created_at (created_at)' ),
				new TableIndex( 'KEY source_created_at (source, created_at)' ),
				new TableIndex( 'KEY country_created_at (country_code, created_at)' ),
			)
		);
	}

	public function maybe_upgrade_schema(): void {
		$this->schema_installer->maybe_upgrade_schema( $this->schema );
	}

	public function table_schema(): TableSchema {
		return $this->schema;
	}
}
