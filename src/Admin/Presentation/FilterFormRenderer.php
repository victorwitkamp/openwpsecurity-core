<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Admin\Presentation;

final class FilterFormRenderer {
	public function render( string $page_slug, string $period, array $fields, string $reset_url ): void {
		?>
		<form class="vwfw-record-filters vwfw-panel" method="get">
			<input type="hidden" name="page" value="<?php echo esc_attr( $page_slug ); ?>">
			<input type="hidden" name="period" value="<?php echo esc_attr( $period ); ?>">
			<div class="vwfw-filter-grid">
				<?php foreach ( $fields as $field ) : ?>
					<?php $this->render_field( $field ); ?>
				<?php endforeach; ?>
				<div class="vwfw-filter-actions">
					<button type="submit" class="button button-primary">Apply Filters</button>
					<a class="button" href="<?php echo esc_url( $reset_url ); ?>">Reset</a>
				</div>
			</div>
		</form>
		<?php
	}

	private function render_field( array $field ): void {
		$type = (string) ( $field['type'] ?? 'text' );

		if ( 'checkboxes' === $type ) {
			$this->render_checkboxes( $field );
			return;
		}

		?>
		<div>
			<label for="<?php echo esc_attr( (string) $field['id'] ); ?>"><strong><?php echo esc_html( (string) $field['label'] ); ?></strong></label>
			<?php if ( 'select' === $type ) : ?>
				<select id="<?php echo esc_attr( (string) $field['id'] ); ?>" name="<?php echo esc_attr( (string) $field['name'] ); ?>">
					<?php foreach ( (array) ( $field['options'] ?? array() ) as $value => $label ) : ?>
						<option value="<?php echo esc_attr( (string) $value ); ?>" <?php selected( (string) ( $field['value'] ?? '' ), (string) $value ); ?>>
							<?php echo esc_html( (string) $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			<?php else : ?>
				<input id="<?php echo esc_attr( (string) $field['id'] ); ?>" type="text" name="<?php echo esc_attr( (string) $field['name'] ); ?>" value="<?php echo esc_attr( (string) ( $field['value'] ?? '' ) ); ?>">
			<?php endif; ?>
		</div>
		<?php
	}

	private function render_checkboxes( array $field ): void {
		?>
		<div class="vwfw-filter-flags">
			<?php foreach ( (array) ( $field['choices'] ?? array() ) as $choice ) : ?>
				<label>
					<input type="checkbox" name="<?php echo esc_attr( (string) $choice['name'] ); ?>" value="1" <?php checked( ! empty( $choice['checked'] ) ); ?>>
					<?php echo esc_html( (string) $choice['label'] ); ?>
				</label>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
