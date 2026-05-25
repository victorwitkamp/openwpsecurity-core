<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Http\Response;

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class ResponseDispatcher {
	private ResponseFactoryInterface $response_factory;
	private StreamFactoryInterface $stream_factory;
	private SapiEmitter $emitter;

	public function __construct( ResponseFactoryInterface $response_factory, StreamFactoryInterface $stream_factory ) {
		$this->response_factory = $response_factory;
		$this->stream_factory   = $stream_factory;
		$this->emitter          = new SapiEmitter();
	}

	public function html( int $status_code, string $html, array $headers = array() ): void {
		$response = $this->create_response(
			$status_code,
			$html,
			array_merge(
				array(
					'Content-Type' => 'text/html; charset=' . get_option( 'blog_charset' ),
				),
				$headers
			)
		);

		$this->emit( $response );
	}

	public function json( int $status_code, array $payload, array $headers = array() ): void {
		$json = wp_json_encode( $payload );
		$json = is_string( $json ) ? $json : '{}';

		$response = $this->create_response(
			$status_code,
			$json,
			array_merge(
				array(
					'Content-Type' => 'application/json; charset=' . get_option( 'blog_charset' ),
				),
				$headers
			)
		);

		$this->emit( $response );
	}

	public function text( int $status_code, string $message, array $headers = array() ): void {
		$response = $this->create_response(
			$status_code,
			$message,
			array_merge(
				array(
					'Content-Type' => 'text/plain; charset=' . get_option( 'blog_charset' ),
				),
				$headers
			)
		);

		$this->emit( $response );
	}

	public function redirect( string $location, int $status_code = 302 ): void {
		$response = $this->response_factory->createResponse( $status_code )->withHeader( 'Location', $location );
		$this->emit( $this->with_default_headers( $response ) );
	}

	private function create_response( int $status_code, string $body, array $headers ): ResponseInterface {
		$response = $this->response_factory->createResponse( $status_code );
		$stream   = $this->stream_factory->createStream( $body );
		$response = $response->withBody( $stream );

		foreach ( $headers as $name => $value ) {
			$response = $response->withHeader( $name, (string) $value );
		}

		return $this->with_default_headers( $response );
	}

	private function with_default_headers( ResponseInterface $response ): ResponseInterface {
		return $response
			->withHeader( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' )
			->withHeader( 'Pragma', 'no-cache' )
			->withHeader( 'Expires', 'Wed, 11 Jan 1984 05:00:00 GMT' );
	}

	private function emit( ResponseInterface $response ): void {
		$this->emitter->emit( $response );
		exit;
	}
}
