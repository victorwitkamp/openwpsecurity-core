<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Admin\Presentation;

use VictorWitkamp\OpenWPSecurity\Core\Admin\Reporting\EventReportFormatter;
use VictorWitkamp\OpenWPSecurity\Core\Security\Ban\PermanentBanStore;

final class PermanentBansPanel {
	private PermanentBanStore $ban_store;
	private EventReportFormatter $event_report_formatter;

	public function __construct( PermanentBanStore $ban_store, EventReportFormatter $event_report_formatter ) {
		$this->ban_store              = $ban_store;
		$this->event_report_formatter = $event_report_formatter;
	}

	public function handle_actions( string $nonce_action ): array {
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			return array();
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Presence check only; nonce validation runs before action data is used.
		if ( ! isset( $_POST['openwpsecurity_ban_action'] ) ) {
			return array();
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions.' );
		}

		check_admin_referer( $nonce_action );

		$post   = wp_unslash( $_POST );
		$action = sanitize_key( (string) $post['openwpsecurity_ban_action'] );

		if ( 'remove_ban' === $action ) {
			$ip      = isset( $post['ip_address'] ) ? sanitize_text_field( (string) $post['ip_address'] ) : '';
			$removed = '' !== $ip && $this->ban_store->remove_ban( $ip );

			return array(
				'type'    => $removed ? 'success' : 'warning',
				'message' => $removed ? 'Permanent ban removed.' : 'Permanent ban was not found.',
			);
		}

		if ( 'clear_bans' === $action ) {
			$count = $this->ban_store->clear_bans();

			return array(
				'type'    => 'success',
				'message' => sprintf( '%d permanent ban(s) removed.', $count ),
			);
		}

		return array(
			'type'    => 'error',
			'message' => 'Unknown permanent-ban action.',
		);
	}

	public function count_bans(): int {
		return $this->ban_store->count_bans();
	}

	public function get_bans( int $limit, int $offset ): array {
		return $this->ban_store->get_bans( $limit, $offset );
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

	public function render( string $page_slug, string $nonce_action, string $title, string $description, int $total_items, array $rows, string $pagination_html, string $empty_message ): void {
		?>
		<div class="vwfw-panel vwfw-record-panel">
			<div class="vwfw-record-header">
				<div>
					<h2><?php echo esc_html( $title ); ?></h2>
					<p class="description"><?php echo esc_html( $description ); ?></p>
				</div>
				<div class="vwfw-record-total">
					<span class="vwfw-record-total-label">Current permanent bans</span>
					<strong><?php echo esc_html( number_format_i18n( $total_items ) ); ?></strong>
				</div>
			</div>

			<?php if ( $total_items > 0 ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . $page_slug ) ); ?>" data-confirm="Clear all permanent bans? This cannot be undone.">
					<?php wp_nonce_field( $nonce_action ); ?>
					<input type="hidden" name="openwpsecurity_ban_action" value="clear_bans">
					<p>
						<button type="submit" class="button button-link-delete">Clear all permanent bans</button>
					</p>
				</form>
			<?php endif; ?>

			<?php echo wp_kses_post( $pagination_html ); ?>
			<div class="vwfw-record-table-wrap">
				<table class="widefat striped fixed vwfw-analysis-table">
					<thead>
						<tr>
							<th>Banned At</th>
							<th>IP Address</th>
							<th>Source</th>
							<th>Reason</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $rows ) ) : ?>
							<tr>
								<td colspan="5"><?php echo esc_html( $empty_message ); ?></td>
							</tr>
						<?php else : ?>
							<?php foreach ( $rows as $row ) : ?>
								<?php $ip_address = (string) ( $row['ip_address'] ?? '' ); ?>
								<tr>
									<td><?php echo esc_html( $this->event_report_formatter->admin_datetime( (string) ( $row['banned_at'] ?? '' ) ) ); ?></td>
									<td><?php echo esc_html( $ip_address ); ?></td>
									<td><?php echo esc_html( $this->event_report_formatter->ban_source_label( (string) ( $row['source'] ?? '' ) ) ); ?></td>
									<td class="vwfw-break"><?php echo esc_html( (string) ( $row['reason'] ?? '' ) ); ?></td>
									<td>
										<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . $page_slug ) ); ?>">
											<?php wp_nonce_field( $nonce_action ); ?>
											<input type="hidden" name="openwpsecurity_ban_action" value="remove_ban">
											<input type="hidden" name="ip_address" value="<?php echo esc_attr( $ip_address ); ?>">
											<button type="submit" class="button button-small">Unban</button>
										</form>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
			<?php echo wp_kses_post( $pagination_html ); ?>
		</div>
		<?php
	}
}
