<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Admin\Presentation;

final class RecordTablePanel {
	public function render( string $title, string $description, int $total_items, string $pagination_html, array $columns, array $rows, string $empty_message, string $table_class, callable $render_cells, bool $show_total = true ): void {
		?>
		<div class="vwfw-panel vwfw-record-panel">
			<div class="vwfw-record-header">
				<div>
					<h2><?php echo esc_html( $title ); ?></h2>
					<p class="description"><?php echo esc_html( $description ); ?></p>
				</div>
				<?php if ( $show_total ) : ?>
					<div class="vwfw-record-total">
						<span class="vwfw-record-total-label">Total matching rows</span>
						<strong><?php echo esc_html( number_format_i18n( $total_items ) ); ?></strong>
					</div>
				<?php endif; ?>
			</div>
			<?php echo wp_kses_post( $pagination_html ); ?>
			<div class="vwfw-record-table-wrap">
				<table class="<?php echo esc_attr( $table_class ); ?>">
					<thead>
						<tr>
							<?php foreach ( $columns as $column ) : ?>
								<th><?php echo esc_html( (string) $column ); ?></th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $rows ) ) : ?>
							<tr>
								<td colspan="<?php echo esc_attr( (string) count( $columns ) ); ?>"><?php echo esc_html( $empty_message ); ?></td>
							</tr>
						<?php else : ?>
							<?php foreach ( $rows as $row ) : ?>
								<tr>
									<?php $render_cells( $row ); ?>
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
