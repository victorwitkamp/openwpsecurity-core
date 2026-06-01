<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Database;

final class TableColumn {
	private string $name;
	private string $definition;
	private mixed $default_value;
	private ?string $insert_format;

	public function __construct( string $name, string $definition, mixed $default_value = null, ?string $insert_format = null ) {
		$this->name          = $name;
		$this->definition    = $definition;
		$this->default_value = $default_value;
		$this->insert_format = $insert_format;
	}

	public function name(): string {
		return $this->name;
	}

	public function definition(): string {
		return $this->definition;
	}

	public function default_value(): mixed {
		return $this->default_value;
	}

	public function insert_format(): ?string {
		return $this->insert_format;
	}
}
