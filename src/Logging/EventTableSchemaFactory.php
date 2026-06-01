<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Logging;

use VictorWitkamp\OpenWPSecurity\Core\Database\TableColumn;
use VictorWitkamp\OpenWPSecurity\Core\Database\TableIndex;
use VictorWitkamp\OpenWPSecurity\Core\Database\TableSchema;

final class EventTableSchemaFactory {
	/**
	 * @param TableColumn[] $additional_columns
	 * @param TableIndex[]  $additional_indexes
	 */
	public function create( EventTableReference $table, string $version_option_name, string $version, array $additional_columns = array(), array $additional_indexes = array() ): TableSchema {
		return new TableSchema(
			$table,
			$version_option_name,
			$version,
			array_merge( $this->base_columns(), $additional_columns ),
			array_merge( $this->base_indexes(), $additional_indexes )
		);
	}

	/**
	 * @return TableColumn[]
	 */
	private function base_columns(): array {
		return array(
			new TableColumn( 'id', 'id bigint(20) unsigned NOT NULL AUTO_INCREMENT' ),
			new TableColumn( 'created_at', 'created_at datetime NOT NULL', '', '%s' ),
			new TableColumn( 'event_type', 'event_type varchar(50) NOT NULL', '', '%s' ),
			new TableColumn( 'ip_address', 'ip_address varchar(45) NOT NULL', '', '%s' ),
			new TableColumn( 'country_code', "country_code varchar(12) NOT NULL DEFAULT ''", '', '%s' ),
			new TableColumn( 'country_name', "country_name varchar(191) NOT NULL DEFAULT ''", '', '%s' ),
			new TableColumn( 'username', "username varchar(191) NOT NULL DEFAULT ''", '', '%s' ),
			new TableColumn( 'user_agent', 'user_agent text NULL', '', '%s' ),
			new TableColumn( 'request_uri', 'request_uri text NULL', '', '%s' ),
			new TableColumn( 'lockout_expires_at', 'lockout_expires_at datetime NULL', null, '%s' ),
			new TableColumn( 'details', 'details longtext NULL', '', '%s' ),
		);
	}

	/**
	 * @return TableIndex[]
	 */
	private function base_indexes(): array {
		return array(
			new TableIndex( 'PRIMARY KEY  (id)' ),
			new TableIndex( 'KEY event_type_created_at (event_type, created_at)' ),
			new TableIndex( 'KEY ip_address_created_at (ip_address, created_at)' ),
		);
	}
}
