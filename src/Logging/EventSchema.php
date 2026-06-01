<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Logging;

use VictorWitkamp\OpenWPSecurity\Core\Database\TableSchema;
use VictorWitkamp\OpenWPSecurity\Core\Database\TableSchemaInstaller;

class EventSchema {
	private TableSchemaInstaller $schema_installer;
	private TableSchema $schema;

	public function __construct( TableSchemaInstaller $schema_installer, TableSchema $schema ) {
		$this->schema_installer = $schema_installer;
		$this->schema           = $schema;
	}

	public function maybe_upgrade_schema(): void {
		$this->schema_installer->maybe_upgrade_schema( $this->schema );
	}

	public function table_schema(): TableSchema {
		return $this->schema;
	}
}
