<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Configuration;

interface SettingsStore {
	public function ensure_defaults(): void;

	public function get(): array;

	public function update( array $settings ): void;

	public function option_name(): string;
}
