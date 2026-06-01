<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Logging;

class EventLookup {
	private EventTableReference $event_table;
	private array $select_columns;
	private array $supported_filters;

	public function __construct( EventTableReference $event_table, array $select_columns, array $supported_filters = array() ) {
		$this->event_table       = $event_table;
		$this->select_columns    = $select_columns;
		$this->supported_filters = array_fill_keys( $supported_filters, true );
	}

	public function count_events_matching_types( array $event_types, array $filters = array(), ?int $period_seconds = null ): int {
		global $wpdb;

		if ( empty( $event_types ) ) {
			return 0;
		}

		$params = array();
		$sql    = $this->build_event_type_query( 'SELECT COUNT(*)', $event_types, $params, $filters, $period_seconds );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared with dynamic placeholder count immediately before execution.
		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
	}

	public function find_rows_matching_types( array $event_types, array $filters = array(), ?int $period_seconds = null, ?int $limit = null, int $offset = 0 ): array {
		global $wpdb;

		if ( empty( $event_types ) ) {
			return array();
		}

		$params = array();
		$sql    = $this->build_event_type_query(
			'SELECT ' . implode( ', ', $this->select_columns ),
			$event_types,
			$params,
			$filters,
			$period_seconds
		);
		$sql   .= ' ORDER BY created_at DESC';

		if ( null !== $limit ) {
			$sql     .= ' LIMIT %d OFFSET %d';
			$params[] = max( 1, $limit );
			$params[] = max( 0, $offset );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared with dynamic placeholder count immediately before execution.
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );

		return is_array( $rows ) ? $rows : array();
	}

	public function country_totals_matching_types( array $event_types, array $filters = array(), ?int $period_seconds = null, int $limit = 8 ): array {
		global $wpdb;

		if ( empty( $event_types ) ) {
			return array();
		}

		$params = array();
		$sql    = $this->build_event_type_query(
			"SELECT
				CASE WHEN country_code = '' THEN '--' ELSE country_code END AS country_code,
				CASE WHEN country_name = '' THEN 'Unknown' ELSE country_name END AS country_name,
				COUNT(*) AS total
			",
			$event_types,
			$params,
			$filters,
			$period_seconds
		);
		$sql   .= " GROUP BY
				CASE WHEN country_code = '' THEN '--' ELSE country_code END,
				CASE WHEN country_name = '' THEN 'Unknown' ELSE country_name END
			ORDER BY total DESC
			LIMIT %d";

		$params[] = max( 1, $limit );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared with dynamic placeholder count immediately before execution.
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map(
			static function ( array $row ): array {
				return array(
					'country_code' => (string) ( $row['country_code'] ?? '--' ),
					'country_name' => (string) ( $row['country_name'] ?? 'Unknown' ),
					'total'        => (int) ( $row['total'] ?? 0 ),
				);
			},
			$rows
		);
	}

	public function country_options_matching_types( array $event_types, array $filters = array(), ?int $period_seconds = null, int $limit = 20 ): array {
		$rows = $this->country_totals_matching_types( $event_types, $filters, $period_seconds, $limit );

		return array_map(
			static function ( array $row ): array {
				return array(
					'code'  => (string) $row['country_code'],
					'label' => trim( (string) $row['country_code'] . ' ' . (string) $row['country_name'] ),
				);
			},
			$rows
		);
	}

	private function build_event_type_query( string $select_clause, array $event_types, array &$params, array $filters, ?int $period_seconds ): string {
		$placeholders = implode( ', ', array_fill( 0, count( $event_types ), '%s' ) );
		$params       = array_values( $event_types );
		$sql          = "{$select_clause} FROM {$this->event_table->name()} WHERE event_type IN ({$placeholders})";

		return $this->add_event_filters_to_query( $sql, $params, $filters, $period_seconds, $event_types );
	}

	private function add_event_filters_to_query( string $sql, array &$params, array $filters, ?int $period_seconds, array $event_types ): string {
		global $wpdb;

		if ( null !== $period_seconds ) {
			$sql     .= ' AND created_at >= %s';
			$params[] = gmdate( 'Y-m-d H:i:s', time() - $period_seconds );
		}

		if ( $this->supports_filter( 'event_type' ) && ! empty( $filters['event_type'] ) && in_array( (string) $filters['event_type'], $event_types, true ) ) {
			$sql     .= ' AND event_type = %s';
			$params[] = (string) $filters['event_type'];
		}

		if ( $this->supports_filter( 'request_type' ) && ! empty( $filters['request_type'] ) ) {
			$sql = $this->add_details_match( $sql, $params, 'request_type', (string) $filters['request_type'] );
		}

		if ( $this->supports_filter( 'method' ) && ! empty( $filters['method'] ) ) {
			$sql = $this->add_details_match( $sql, $params, 'method', strtoupper( (string) $filters['method'] ) );
		}

		if ( $this->supports_filter( 'country_code' ) && ! empty( $filters['country_code'] ) ) {
			$sql     .= ' AND country_code = %s';
			$params[] = (string) $filters['country_code'];
		}

		if ( $this->supports_filter( 'ip_address' ) && ! empty( $filters['ip_address'] ) ) {
			$sql     .= ' AND ip_address LIKE %s';
			$params[] = '%' . $wpdb->esc_like( (string) $filters['ip_address'] ) . '%';
		}

		if ( $this->supports_filter( 'username' ) && ! empty( $filters['username'] ) ) {
			$sql     .= ' AND username LIKE %s';
			$params[] = '%' . $wpdb->esc_like( (string) $filters['username'] ) . '%';
		}

		if ( $this->supports_filter( 'request_uri' ) && ! empty( $filters['request_uri'] ) ) {
			$sql     .= ' AND request_uri LIKE %s';
			$params[] = '%' . $wpdb->esc_like( (string) $filters['request_uri'] ) . '%';
		}

		if ( $this->supports_filter( 'user_agent' ) && ! empty( $filters['user_agent'] ) ) {
			$sql     .= ' AND user_agent LIKE %s';
			$params[] = '%' . $wpdb->esc_like( (string) $filters['user_agent'] ) . '%';
		}

		if ( $this->supports_filter( 'external_only' ) && ! empty( $filters['external_only'] ) ) {
			$sql .= " AND country_code <> 'LOCAL' AND ip_address <> ''";
		}

		if ( $this->supports_filter( 'exclude_internal' ) && ! empty( $filters['exclude_internal'] ) ) {
			$sql .= " AND details NOT LIKE '%\"request_type\":\"wp_admin\"%' AND details NOT LIKE '%\"request_type\":\"admin_ajax\"%' AND details NOT LIKE '%\"request_type\":\"wp_cron\"%'";
		}

		if ( $this->supports_filter( 'exclude_my_ip' ) && ! empty( $filters['exclude_my_ip'] ) && ! empty( $filters['current_ip'] ) ) {
			$sql     .= ' AND ip_address <> %s';
			$params[] = (string) $filters['current_ip'];
		}

		return $sql;
	}

	private function add_details_match( string $sql, array &$params, string $field, string $value ): string {
		global $wpdb;

		$sql     .= ' AND details LIKE %s';
		$params[] = '%' . $wpdb->esc_like( '"' . $field . '":"' . $value . '"' ) . '%';

		return $sql;
	}

	private function supports_filter( string $filter ): bool {
		return isset( $this->supported_filters[ $filter ] );
	}
}
