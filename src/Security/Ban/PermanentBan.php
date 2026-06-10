<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Security\Ban;

final class PermanentBan {
	private string $ip_address;
	private string $country_code;
	private string $country_name;
	private string $source;
	private string $reason;
	private string $request_uri;
	private string $user_agent;
	private string $evidence_json;
	private string $created_at;

	public function __construct( string $ip_address, string $country_code, string $country_name, string $source, string $reason, string $request_uri, string $user_agent, string $evidence_json, string $created_at = '' ) {
		$this->ip_address    = $ip_address;
		$this->country_code  = $country_code;
		$this->country_name  = $country_name;
		$this->source        = $source;
		$this->reason        = $reason;
		$this->request_uri   = $request_uri;
		$this->user_agent    = $user_agent;
		$this->evidence_json = $evidence_json;
		$this->created_at    = $created_at;
	}

	public static function from_row( array $row ): self {
		return new self(
			(string) ( $row['ip_address'] ?? '' ),
			(string) ( $row['country_code'] ?? '' ),
			(string) ( $row['country_name'] ?? '' ),
			(string) ( $row['source'] ?? '' ),
			(string) ( $row['reason'] ?? '' ),
			(string) ( $row['request_uri'] ?? '' ),
			(string) ( $row['user_agent'] ?? '' ),
			(string) ( $row['evidence_json'] ?? '' ),
			(string) ( $row['created_at'] ?? '' )
		);
	}

	public function ip_address(): string {
		return $this->ip_address;
	}

	public function to_insert_row(): array {
		return array(
			'ip_address'    => $this->ip_address,
			'country_code'  => $this->country_code,
			'country_name'  => $this->country_name,
			'source'        => $this->source,
			'reason'        => $this->reason,
			'request_uri'   => $this->request_uri,
			'user_agent'    => $this->user_agent,
			'evidence_json' => $this->evidence_json,
		);
	}

	public function to_array(): array {
		return array(
			'ip_address'    => $this->ip_address,
			'banned_at'     => $this->created_at,
			'created_at'    => $this->created_at,
			'country_code'  => $this->country_code,
			'country_name'  => $this->country_name,
			'reason'        => $this->reason,
			'source'        => $this->source,
			'request_uri'   => $this->request_uri,
			'user_agent'    => $this->user_agent,
			'evidence_json' => $this->evidence_json,
			'details'       => $this->evidence_json,
			'event_type'    => 'permanent_ban_created',
		);
	}
}
