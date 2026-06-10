<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Security\Ban;

final class TemporaryBan {
	private string $ip_address;
	private int $created_at;
	private int $expires_at;
	private string $source;
	private string $scope;
	private string $reason;
	private string $details;
	private string $evidence_json;

	public function __construct( string $ip_address, int $created_at, int $expires_at, string $source, string $scope, string $reason, string $details = '', string $evidence_json = '' ) {
		$this->ip_address    = $ip_address;
		$this->created_at    = $created_at;
		$this->expires_at    = $expires_at;
		$this->source        = $source;
		$this->scope         = $scope;
		$this->reason        = $reason;
		$this->details       = $details;
		$this->evidence_json = $evidence_json;
	}

	public static function from_array( array $data ): self {
		return new self(
			(string) ( $data['ip_address'] ?? '' ),
			self::timestamp( $data['created_at'] ?? 0 ),
			self::timestamp( $data['expires_at'] ?? 0 ),
			(string) ( $data['source'] ?? '' ),
			(string) ( $data['scope'] ?? '' ),
			(string) ( $data['reason'] ?? '' ),
			(string) ( $data['details'] ?? '' ),
			(string) ( $data['evidence_json'] ?? '' )
		);
	}

	public static function from_row( array $row ): self {
		return self::from_array( $row );
	}

	public function ip_address(): string {
		return $this->ip_address;
	}

	public function created_at(): int {
		return $this->created_at;
	}

	public function expires_at(): int {
		return $this->expires_at;
	}

	public function source(): string {
		return $this->source;
	}

	public function scope(): string {
		return $this->scope;
	}

	public function reason(): string {
		return $this->reason;
	}

	public function details(): string {
		return $this->details;
	}

	public function evidence(): array {
		$evidence = json_decode( $this->evidence_json, true );

		return is_array( $evidence ) ? $evidence : array();
	}

	public function evidence_json(): string {
		return $this->evidence_json;
	}

	public function to_row(): array {
		return array(
			'ip_address'    => $this->ip_address,
			'created_at'    => gmdate( 'Y-m-d H:i:s', $this->created_at ),
			'expires_at'    => gmdate( 'Y-m-d H:i:s', $this->expires_at ),
			'source'        => $this->source,
			'scope'         => $this->scope,
			'reason'        => $this->reason,
			'details'       => $this->details,
			'evidence_json' => $this->evidence_json,
		);
	}

	public function to_array(): array {
		return array(
			'ip_address'    => $this->ip_address,
			'created_at'    => $this->created_at,
			'expires_at'    => $this->expires_at,
			'source'        => $this->source,
			'scope'         => $this->scope,
			'reason'        => $this->reason,
			'details'       => $this->details,
			'evidence_json' => $this->evidence_json,
		);
	}

	private static function timestamp( mixed $value ): int {
		if ( is_int( $value ) || is_float( $value ) || ( is_string( $value ) && ctype_digit( $value ) ) ) {
			return (int) $value;
		}

		$value = trim( (string) $value );

		if ( '' === $value ) {
			return 0;
		}

		$timestamp = strtotime( $value . ' UTC' );

		return false === $timestamp ? 0 : $timestamp;
	}
}
