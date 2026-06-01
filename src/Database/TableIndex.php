<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Database;

final class TableIndex {
	private string $definition;

	public function __construct( string $definition ) {
		$this->definition = $definition;
	}

	public function definition(): string {
		return $this->definition;
	}
}
