<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Admin\Presentation;

use VictorWitkamp\OpenWPSecurity\Core\Admin\Reporting\EventReportFormatter;
use VictorWitkamp\OpenWPSecurity\Core\Security\Ban\TemporaryBan;
use VictorWitkamp\OpenWPSecurity\Core\Security\Ban\TemporaryBanRepository;

final class TemporaryBansPanel {
	private EventReportFormatter $event_report_formatter;

	public function __construct( EventReportFormatter $event_report_formatter ) {
		$this->event_report_formatter = $event_report_formatter;
	}

	public function handle_actions( TemporaryBanRepository $ban_repository, string $nonce_action ): array {
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			return array();
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Presence check only; nonce validation runs before action data is used.
		if ( ! isset( $_POST['openwpsecurity_temporary_ban_action'] ) ) {
			return array();
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions.' );
		}

		check_admin_referer( $nonce_action );

		$post   = wp_unslash( $_POST );
		$action = sanitize_key( (string) $post['openwpsecurity_temporary_ban_action'] );

		if ( 'remove_ban' === $action ) {
			$ip_address = isset( $post['ip_address'] ) ? sanitize_text_field( (string) $post['ip_address'] ) : '';
			$removed    = '' !== $ip_address && $ban_repository->remove_temporary_ban( $ip_address );

			return array(
				'type'    => $removed ? 'success' : 'warning',
				'message' => $removed ? 'Temporary ban removed.' : 'Temporary ban was not found.',
			);
		}

		if ( 'clear_bans' === $action ) {
			$count = $ban_repository->clear_temporary_bans();

			return array(
				'type'    => 'success',
				'message' => sprintf( '%d temporary ban(s) removed.', $count ),
			);
		}

		return array(
			'type'    => 'error',
			'message' => 'Unknown temporary-ban action.',
		);
	}

	public function sorted_rows( TemporaryBanRepository $ban_repository ): array {
		$rows = $ban_repository->get_active_temporary_bans();

		usort(
			$rows,
			static function ( TemporaryBan $left, TemporaryBan $right ): int {
				return $left->expires_at() <=> $right->expires_at();
			}
		);

		return $rows;
	}

	public function render_notice( array $notice ): void {
		if ( empty( $notice['message'] ) ) {
			return;
		}

		$type = in_array( $notice['type'] ?? '', array( 'success', 'warning', 'error', 'info' ), true ) ? (string) $notice['type'] : 'info';
		?>
		<div class="notice notice-<?php echo esc_attr( $type ); ?> is-dismissible">
			<p><?php echo esc_html( (string) $notice['message'] ); ?></p>
		</div>
		<?php
	}

	public function render( string $page_slug, string $nonce_action, string $title, string $description, array $rows, string $empty_message ): void {
		?>
		<div class="vwfw-panel vwfw-record-panel">
			<div class="vwfw-record-header">
				<div>
					<h2><?php echo esc_html( $title ); ?></h2>
					<p class="description"><?php echo esc_html( $description ); ?></p>
				</div>
				<div class="vwfw-record-total">
					<span class="vwfw-record-total-label">Current temporary bans</span>
					<strong><?php echo esc_html( number_format_i18n( count( $rows ) ) ); ?></strong>
				</div>
			</div>

			<?php if ( ! empty( $rows ) ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . $page_slug ) ); ?>" data-confirm="Clear all temporary bans? This immediately restores access for every listed IP address.">
					<?php wp_nonce_field( $nonce_action ); ?>
					<input type="hidden" name="openwpsecurity_temporary_ban_action" value="clear_bans">
					<p><button type="submit" class="button button-link-delete">Clear all temporary bans</button></p>
				</form>
			<?php endif; ?>

			<div class="vwfw-record-table-wrap">
				<table class="widefat striped fixed vwfw-analysis-table">
					<thead>
						<tr>
							<th>Started At</th>
							<th>IP Address</th>
							<th>Scope</th>
							<th>Expires At</th>
							<th>Remaining</th>
							<th>Reason</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $rows ) ) : ?>
							<tr><td colspan="7"><?php echo esc_html( $empty_message ); ?></td></tr>
						<?php else : ?>
							<?php foreach ( $rows as $ban ) : ?>
								<tr>
									<td><?php echo esc_html( $this->format_timestamp( $ban->created_at() ) ); ?></td>
									<td><?php echo esc_html( $ban->ip_address() ); ?></td>
									<td><?php echo esc_html( $ban->scope() ); ?></td>
									<td><?php echo esc_html( $this->format_timestamp( $ban->expires_at() ) ); ?></td>
									<td><?php echo esc_html( human_time_diff( time(), $ban->expires_at() ) ); ?></td>
									<td class="vwfw-break">
										<?php echo esc_html( $ban->reason() ); ?>
										<?php if ( '' !== $ban->details() ) : ?>
											<span class="vwfw-muted"><?php echo esc_html( $ban->details() ); ?></span>
										<?php endif; ?>
									</td>
									<td>
										<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . $page_slug ) ); ?>">
											<?php wp_nonce_field( $nonce_action ); ?>
											<input type="hidden" name="openwpsecurity_temporary_ban_action" value="remove_ban">
											<input type="hidden" name="ip_address" value="<?php echo esc_attr( $ban->ip_address() ); ?>">
											<button type="submit" class="button button-small">Remove ban</button>
										</form>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	private function format_timestamp( int $timestamp ): string {
		if ( $timestamp <= 0 ) {
			return '';
		}

		return $this->event_report_formatter->admin_datetime( gmdate( 'Y-m-d H:i:s', $timestamp ) );
	}
}
