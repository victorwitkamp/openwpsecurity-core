<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Security\Ban;

final class TemporaryBanCleanup {
	private TemporaryBanRepository $temporary_ban_repository;
	private string $cleanup_hook;

	public function __construct( TemporaryBanRepository $temporary_ban_repository, string $cleanup_hook ) {
		$this->temporary_ban_repository = $temporary_ban_repository;
		$this->cleanup_hook             = $cleanup_hook;
	}

	public function register_hooks(): void {
		add_action( $this->cleanup_hook, array( $this, 'purge_expired_temporary_bans' ) );
		add_action( 'init', array( $this, 'synchronize_schedule' ), 5 );
	}

	public function activate(): void {
		$this->synchronize_schedule();
		$this->temporary_ban_repository->purge_expired_temporary_bans();
	}

	public function deactivate(): void {
		wp_clear_scheduled_hook( $this->cleanup_hook );
	}

	public function synchronize_schedule(): void {
		if ( false === wp_next_scheduled( $this->cleanup_hook ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', $this->cleanup_hook );
		}
	}

	public function purge_expired_temporary_bans(): void {
		$this->temporary_ban_repository->purge_expired_temporary_bans();
	}
}
