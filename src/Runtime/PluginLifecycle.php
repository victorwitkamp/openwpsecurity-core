<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Runtime;

interface PluginLifecycle {
	public function activate(): void;

	public function deactivate(): void;

	public function initialize_runtime(): void;
}
