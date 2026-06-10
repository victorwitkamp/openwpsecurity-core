<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Database;

final class TableWriter {
	private TableReference $table;
	private array $defaults;
	private array $formats;

	public function __construct( TableReference $table, TableSchema $schema ) {
		$this->table    = $table;
		$this->defaults = $schema->insert_defaults();
		$this->formats  = $schema->insert_formats();
	}

	public function insert( array $row ): bool {
		global $wpdb;

		$defaults               = $this->defaults;
		$defaults['created_at'] = current_time( 'mysql', true );
		$row                    = array_intersect_key( array_replace( $defaults, $row ), $defaults );

		return false !== $wpdb->insert(
			$this->table->name(),
			$row,
			$this->formats
		);
	}
}
