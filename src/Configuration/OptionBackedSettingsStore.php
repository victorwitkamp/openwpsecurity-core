<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Configuration;

abstract class OptionBackedSettingsStore implements SettingsStore {
	public function ensure_defaults(): void {
		$defaults = $this->defaults();
		$current  = get_option( $this->option_name(), null );

		if ( null === $current ) {
			add_option( $this->option_name(), $defaults );
			return;
		}

		if ( ! is_array( $current ) ) {
			update_option( $this->option_name(), $defaults );
			return;
		}

		$merged = wp_parse_args( $current, $defaults );

		if ( $merged !== $current ) {
			update_option( $this->option_name(), $merged );
		}
	}

	public function get(): array {
		$settings = get_option( $this->option_name(), array() );
		$settings = wp_parse_args( is_array( $settings ) ? $settings : array(), $this->defaults() );

		return $this->normalize( $settings );
	}

	public function update( array $settings ): void {
		update_option( $this->option_name(), wp_parse_args( $settings, $this->get() ) );
	}

	protected function defaults(): array {
		$defaults = $this->default_settings();
		$filter   = $this->default_settings_filter();

		if ( '' !== $filter ) {
			/**
			 * Filters plugin default settings.
			 *
			 * @param array<string,mixed> $defaults Default settings.
			 */
			$defaults = (array) apply_filters( $filter, $defaults );
		}

		return $defaults;
	}

	abstract protected function default_settings(): array;

	protected function default_settings_filter(): string {
		return '';
	}

	protected function normalize( array $settings ): array {
		return $settings;
	}
}
