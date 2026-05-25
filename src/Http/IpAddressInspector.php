<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Http;

final class IpAddressInspector {
	public function resolve_from_headers( array $server, array $trusted_headers ): string {
		$trusted_headers = array_filter( array_map( 'trim', $trusted_headers ) );

		if ( ! in_array( 'REMOTE_ADDR', $trusted_headers, true ) ) {
			$trusted_headers[] = 'REMOTE_ADDR';
		}

		foreach ( $trusted_headers as $header ) {
			if ( empty( $server[ $header ] ) ) {
				continue;
			}

			$candidates = array_map( 'trim', explode( ',', (string) $server[ $header ] ) );

			foreach ( $candidates as $candidate ) {
				$ip_address = $this->normalize_candidate( $candidate );

				if ( filter_var( $ip_address, FILTER_VALIDATE_IP ) ) {
					return (string) $ip_address;
				}
			}
		}

		return '';
	}

	public function is_private( string $ip_address ): bool {
		if ( '' === $ip_address ) {
			return true;
		}

		return ! (bool) filter_var(
			$ip_address,
			FILTER_VALIDATE_IP,
			FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
		);
	}

	private function normalize_candidate( string $candidate ): string {
		$candidate = trim( $candidate );

		if ( '' === $candidate ) {
			return '';
		}

		if ( filter_var( $candidate, FILTER_VALIDATE_IP ) ) {
			return $candidate;
		}

		if ( preg_match( '/^\[([0-9a-fA-F:.]+)\](?::\d+)?$/', $candidate, $matches ) ) {
			return (string) $matches[1];
		}

		if ( preg_match( '/^(\d{1,3}(?:\.\d{1,3}){3}):\d+$/', $candidate, $matches ) ) {
			return (string) $matches[1];
		}

		return '';
	}
}
