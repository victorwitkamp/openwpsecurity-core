<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Runtime;

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;

final class WordPressPluginIntegration {
	private string $plugin_class;
	private string $plugin_label;
	private array $plugin_definitions;
	private ?ContainerInterface $container               = null;
	private ?ContainerDefinitions $container_definitions = null;

	public function __construct( string $plugin_class, string $plugin_label, array $plugin_definitions = array() ) {
		$this->plugin_class       = $plugin_class;
		$this->plugin_label       = $plugin_label;
		$this->plugin_definitions = $plugin_definitions;
	}

	public function activate(): void {
		$this->plugin()->activate();
	}

	public function deactivate(): void {
		$this->plugin()->deactivate();
	}

	public function initialize_runtime(): void {
		$this->plugin()->initialize_runtime();
	}

	private function plugin(): PluginLifecycle {
		$plugin = $this->container()->get( $this->plugin_class );

		if ( ! $plugin instanceof PluginLifecycle ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages are not rendered directly.
			throw new \RuntimeException( $this->plugin_label . ' plugin service did not resolve correctly.' );
		}

		return $plugin;
	}

	private function container(): ContainerInterface {
		if ( null !== $this->container ) {
			return $this->container;
		}

		$builder = new ContainerBuilder();
		$builder->useAutowiring( true );
		$builder->useAttributes( false );
		$builder->addDefinitions( $this->container_definitions()->definitions() );
		$builder->addDefinitions( $this->plugin_definitions );
		$this->container = $builder->build();

		return $this->container;
	}

	private function container_definitions(): ContainerDefinitions {
		if ( null === $this->container_definitions ) {
			$this->container_definitions = new ContainerDefinitions();
		}

		return $this->container_definitions;
	}
}
