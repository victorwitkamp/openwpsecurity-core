<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Admin\Reporting;

class EventReportFormatter {
	private array $event_type_labels;
	private array $ban_source_labels;
	private array $request_type_labels;
	private array $method_options;

	public function __construct( array $event_type_labels = array(), array $ban_source_labels = array(), array $request_type_labels = array(), array $method_options = array() ) {
		$this->event_type_labels   = $event_type_labels;
		$this->ban_source_labels   = $ban_source_labels;
		$this->request_type_labels = array() !== $request_type_labels ? $request_type_labels : $this->default_request_type_labels();
		$this->method_options      = array() !== $method_options ? $method_options : $this->default_method_options();
	}

	public function details_from_json( string $details_json ): array {
		if ( '' === $details_json ) {
			return array();
		}

		$decoded = json_decode( $details_json, true );

		return is_array( $decoded ) ? $decoded : array();
	}

	public function admin_datetime( string $gmt_datetime ): string {
		return get_date_from_gmt( $gmt_datetime, 'Y-m-d H:i:s' );
	}

	public function event_type_label( string $event_type ): string {
		return $this->event_type_labels[ $event_type ] ?? $event_type;
	}

	public function event_type_options( array $event_types, string $all_label = 'All Types' ): array {
		$options = array(
			'' => $all_label,
		);

		foreach ( $event_types as $event_type ) {
			$options[ $event_type ] = $this->event_type_label( $event_type );
		}

		return $options;
	}

	public function request_type_label( string $request_type ): string {
		return $this->request_type_labels[ $request_type ] ?? $request_type;
	}

	public function request_type_options(): array {
		return array_merge(
			array(
				'' => 'All Types',
			),
			$this->request_type_labels
		);
	}

	public function method_options(): array {
		return $this->method_options;
	}

	public function ban_source_label( string $source ): string {
		return $this->ban_source_labels[ $source ] ?? $source;
	}

	private function default_request_type_labels(): array {
		return array(
			'frontend_page' => 'Frontend Page',
			'wp_login'      => 'WP Login',
			'wp_admin'      => 'WP Admin',
			'admin_ajax'    => 'Admin AJAX',
			'rest_api'      => 'REST API',
			'xmlrpc'        => 'XML-RPC',
			'wp_cron'       => 'WP Cron',
			'cli'           => 'WP-CLI',
			'other'         => 'Other',
		);
	}

	private function default_method_options(): array {
		return array(
			''        => 'All Methods',
			'GET'     => 'GET',
			'POST'    => 'POST',
			'PUT'     => 'PUT',
			'PATCH'   => 'PATCH',
			'DELETE'  => 'DELETE',
			'HEAD'    => 'HEAD',
			'OPTIONS' => 'OPTIONS',
		);
	}
}
