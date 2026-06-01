<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Security\Ban;

use VictorWitkamp\OpenWPSecurity\Core\Http\IpAddressInspector;

final class PermanentBanStore {
	private string $option_name;
	private IpAddressInspector $ip_address_inspector;
	private \Closure $ban_event_logger;
	private string $default_source;

	public function __construct( string $option_name, IpAddressInspector $ip_address_inspector, \Closure $ban_event_logger, string $default_source = 'manual' ) {
		$this->option_name          = $option_name;
		$this->ip_address_inspector = $ip_address_inspector;
		$this->ban_event_logger     = $ban_event_logger;
		$this->default_source       = $default_source;
	}

	public function ensure_storage(): void {
		if ( get_option( $this->option_name, null ) === null ) {
			add_option( $this->option_name, array(), '', false );
		}
	}

	public function get_all_bans(): array {
		$bans = get_option( $this->option_name, array() );
		$bans = is_array( $bans ) ? $bans : array();

		foreach ( $bans as $ip => $ban ) {
			if ( ! is_array( $ban ) ) {
				unset( $bans[ $ip ] );
			}
		}

		return $bans;
	}

	public function count_bans(): int {
		return count( $this->get_all_bans() );
	}

	public function get_ban_for_ip( string $ip ): array {
		$bans = $this->get_all_bans();

		return isset( $bans[ $ip ] ) && is_array( $bans[ $ip ] ) ? $bans[ $ip ] : array();
	}

	public function is_banned( string $ip ): bool {
		return array() !== $this->get_ban_for_ip( $ip );
	}

	public function create_ban( string $ip, string $reason, string $source = '', array $context = array() ): void {
		if ( '' === $source ) {
			$source = $this->default_source;
		}

		if ( '' === $ip || $this->ip_address_inspector->is_private( $ip ) ) {
			return;
		}

		$bans = $this->get_all_bans();

		if ( isset( $bans[ $ip ] ) ) {
			return;
		}

		$bans[ $ip ] = array(
			'ip_address' => $ip,
			'banned_at'  => current_time( 'mysql', true ),
			'reason'     => $reason,
			'source'     => $source,
		);
		update_option( $this->option_name, $bans, false );

		( $this->ban_event_logger )( $ip, $reason, $source, $context );
	}

	public function remove_ban( string $ip ): bool {
		$bans = $this->get_all_bans();

		if ( ! isset( $bans[ $ip ] ) ) {
			return false;
		}

		unset( $bans[ $ip ] );
		update_option( $this->option_name, $bans, false );

		return true;
	}

	public function clear_bans(): int {
		$bans  = $this->get_all_bans();
		$count = count( $bans );

		if ( $count > 0 ) {
			update_option( $this->option_name, array(), false );
		}

		return $count;
	}
}
