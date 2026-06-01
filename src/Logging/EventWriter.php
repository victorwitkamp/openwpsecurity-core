<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Logging;

use VictorWitkamp\OpenWPSecurity\Core\Database\TableSchema;

class EventWriter {
	private EventTableReference $event_table;
	private array $defaults;
	private array $formats;

	public function __construct( EventTableReference $event_table, TableSchema $schema ) {
		$this->event_table = $event_table;
		$this->defaults    = $schema->insert_defaults();
		$this->formats     = $schema->insert_formats();
	}

	public function insert( array|EventRecord $event ): void {
		global $wpdb;

		$event                  = $event instanceof EventRecord ? $event->to_array() : $event;
		$defaults               = $this->defaults;
		$defaults['created_at'] = current_time( 'mysql', true );
		$event                  = array_intersect_key( array_replace( $defaults, $event ), $defaults );

		$wpdb->insert(
			$this->event_table->name(),
			$event,
			$this->formats
		);
	}
}
