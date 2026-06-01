<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Logging;

final class EventRecord {
	private array $data;

	public function __construct( array $data ) {
		$this->data = $data;
	}

	public static function from_array( array $data ): self {
		return new self( $data );
	}

	public function to_array(): array {
		return $this->data;
	}

	public function value( string $name, mixed $fallback = null ): mixed {
		return $this->data[ $name ] ?? $fallback;
	}

	public function string_value( string $name, string $fallback = '' ): string {
		return isset( $this->data[ $name ] ) ? (string) $this->data[ $name ] : $fallback;
	}

	public function int_value( string $name, int $fallback = 0 ): int {
		return isset( $this->data[ $name ] ) ? (int) $this->data[ $name ] : $fallback;
	}
}
