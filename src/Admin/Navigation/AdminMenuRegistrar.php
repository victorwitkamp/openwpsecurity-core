<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Admin\Navigation;

final class AdminMenuRegistrar {
	private string $page_title;
	private string $menu_title;
	private string $capability;
	private string $root_slug;
	private array $root_callback;
	private string $icon;
	private int $position;
	private array $submenu_pages;
	private string $hook_match;
	private string $style_handle;
	private string $style_url;
	private string $script_handle;
	private string $script_url;
	private string $asset_version;

	public function __construct(
		string $page_title,
		string $menu_title,
		string $capability,
		string $root_slug,
		array $root_callback,
		string $icon,
		int $position,
		array $submenu_pages,
		string $hook_match,
		string $style_handle,
		string $style_url,
		string $script_handle,
		string $script_url,
		string $asset_version
	) {
		$this->page_title    = $page_title;
		$this->menu_title    = $menu_title;
		$this->capability    = $capability;
		$this->root_slug     = $root_slug;
		$this->root_callback = $root_callback;
		$this->icon          = $icon;
		$this->position      = $position;
		$this->submenu_pages = $submenu_pages;
		$this->hook_match    = $hook_match;
		$this->style_handle  = $style_handle;
		$this->style_url     = $style_url;
		$this->script_handle = $script_handle;
		$this->script_url    = $script_url;
		$this->asset_version = $asset_version;
	}

	public function register_hooks(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function register_menu(): void {
		add_menu_page(
			$this->page_title,
			$this->menu_title,
			$this->capability,
			$this->root_slug,
			$this->root_callback,
			$this->icon,
			$this->position
		);

		foreach ( $this->submenu_pages as $page ) {
			add_submenu_page(
				$this->root_slug,
				(string) $page['page_title'],
				(string) $page['menu_title'],
				$this->capability,
				(string) $page['slug'],
				(array) $page['callback']
			);
		}
	}

	public function enqueue_assets( string $hook_suffix ): void {
		if ( strpos( $hook_suffix, $this->hook_match ) === false ) {
			return;
		}

		wp_enqueue_style(
			$this->style_handle,
			$this->style_url,
			array(),
			$this->asset_version
		);

		wp_enqueue_script(
			$this->script_handle,
			$this->script_url,
			array(),
			$this->asset_version,
			true
		);
	}
}
