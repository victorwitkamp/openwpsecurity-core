<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Database;

final class TableSchema {
	private TableReference $table;
	private string $version_option_name;
	private string $version;
	/** @var TableColumn[] */
	private array $columns;
	/** @var TableIndex[] */
	private array $indexes;

	/**
	 * @param TableColumn[] $columns
	 * @param TableIndex[]  $indexes
	 */
	public function __construct( TableReference $table, string $version_option_name, string $version, array $columns, array $indexes ) {
		$this->table               = $table;
		$this->version_option_name = $version_option_name;
		$this->version             = $version;
		$this->columns             = $columns;
		$this->indexes             = $indexes;
	}

	public function table(): TableReference {
		return $this->table;
	}

	public function version_option_name(): string {
		return $this->version_option_name;
	}

	public function version(): string {
		return $this->version;
	}

	public function create_sql( string $charset_collate ): string {
		$definitions = array();

		foreach ( $this->columns as $column ) {
			$definitions[] = $column->definition();
		}

		foreach ( $this->indexes as $index ) {
			$definitions[] = $index->definition();
		}

		return sprintf(
			"CREATE TABLE %s (\n\t%s\n) %s;",
			$this->table->name(),
			implode( ",\n\t", $definitions ),
			$charset_collate
		);
	}

	public function insert_defaults(): array {
		$defaults = array();

		foreach ( $this->columns as $column ) {
			if ( null !== $column->insert_format() ) {
				$defaults[ $column->name() ] = $column->default_value();
			}
		}

		return $defaults;
	}

	public function insert_formats(): array {
		$formats = array();

		foreach ( $this->columns as $column ) {
			if ( null !== $column->insert_format() ) {
				$formats[] = $column->insert_format();
			}
		}

		return $formats;
	}
}
