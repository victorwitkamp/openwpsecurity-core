<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Http;

use Laminas\Diactoros\ServerRequestFactory as LaminasServerRequestFactory;
use Psr\Http\Message\ServerRequestInterface;

final class WordPressServerRequestFactory {
	public function create(): ServerRequestInterface {
		// Raw HTTP input must be captured before WordPress-specific nonce handling.
		return LaminasServerRequestFactory::fromGlobals(
			$_SERVER,
			$_GET,
			$_POST,
			$_COOKIE,
			$_FILES
		);
	}
}
