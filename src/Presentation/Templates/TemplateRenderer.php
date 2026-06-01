<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Presentation\Templates;

class TemplateRenderer {
	private string $template_directory;
	private string $template_not_found_message;
	private string $style_handle;
	private string $style_url;
	private string $script_handle;
	private string $script_url;
	private string $version;

	public function __construct( string $template_directory, string $template_not_found_message, string $style_handle, string $style_url, string $script_handle, string $script_url, string $version ) {
		$this->template_directory         = rtrim( $template_directory, '/\\' ) . DIRECTORY_SEPARATOR;
		$this->template_not_found_message = $template_not_found_message;
		$this->style_handle               = $style_handle;
		$this->style_url                  = $style_url;
		$this->script_handle              = $script_handle;
		$this->script_url                 = $script_url;
		$this->version                    = $version;
	}

	public function render( string $template_name, array $variables = array() ): string {
		$template_path = $this->template_directory . $template_name;

		if ( ! file_exists( $template_path ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages are not rendered directly.
			throw new \RuntimeException( $this->template_not_found_message );
		}

		$this->enqueue_runtime_assets();

		ob_start();

		foreach ( $variables as $key => $value ) {
			${$key} = $value;
		}

		include $template_path;

		return (string) ob_get_clean();
	}

	private function enqueue_runtime_assets(): void {
		wp_enqueue_style(
			$this->style_handle,
			$this->style_url,
			array(),
			$this->version
		);

		wp_enqueue_script(
			$this->script_handle,
			$this->script_url,
			array(),
			$this->version,
			true
		);
	}
}
