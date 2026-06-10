<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Database;

final class WordPressTableReference implements TableReference {
	private string $unprefixed_name;

	public function __construct( string $unprefixed_name ) {
		$this->unprefixed_name = $unprefixed_name;
	}

	public function name(): string {
		global $wpdb;

		return $wpdb->prefix . $this->unprefixed_name;
	}
}
