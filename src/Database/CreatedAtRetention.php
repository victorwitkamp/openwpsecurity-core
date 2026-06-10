<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Database;

use VictorWitkamp\OpenWPSecurity\Core\Configuration\SettingsStore;

final class CreatedAtRetention {
	private SettingsStore $settings;
	/** @var TableReference[] */
	private array $tables;
	private string $cleanup_hook;
	private string $retention_setting_key;

	/**
	 * @param TableReference[] $tables
	 */
	public function __construct( SettingsStore $settings, array $tables, string $cleanup_hook, string $retention_setting_key = 'event_retention_days' ) {
		$this->settings              = $settings;
		$this->tables                = $tables;
		$this->cleanup_hook          = $cleanup_hook;
		$this->retention_setting_key = $retention_setting_key;
	}

	public function register_hooks(): void {
		add_action( $this->cleanup_hook, array( $this, 'delete_expired_rows' ) );
		add_action( 'init', array( $this, 'synchronize_schedule' ), 5 );
		add_action( 'update_option_' . $this->settings->option_name(), array( $this, 'handle_settings_update' ), 10, 0 );
	}

	public function activate(): void {
		$this->synchronize_schedule();
		$this->delete_expired_rows();
	}

	public function deactivate(): void {
		wp_clear_scheduled_hook( $this->cleanup_hook );
	}

	public function handle_settings_update(): void {
		$this->synchronize_schedule();
	}

	public function synchronize_schedule(): void {
		$retention_days = $this->retention_days();
		$scheduled_at   = wp_next_scheduled( $this->cleanup_hook );

		if ( $retention_days <= 0 ) {
			if ( false !== $scheduled_at ) {
				wp_clear_scheduled_hook( $this->cleanup_hook );
			}

			return;
		}

		if ( false === $scheduled_at ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', $this->cleanup_hook );
		}
	}

	public function delete_expired_rows(): void {
		global $wpdb;

		$retention_days = $this->retention_days();

		if ( $retention_days <= 0 ) {
			return;
		}

		$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( $retention_days * DAY_IN_SECONDS ) );

		foreach ( $this->tables as $table ) {
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are generated internally from $wpdb->prefix.
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$table->name()} WHERE created_at < %s",
					$cutoff
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
	}

	private function retention_days(): int {
		$settings = $this->settings->get();

		return (int) ( $settings[ $this->retention_setting_key ] ?? 0 );
	}
}
