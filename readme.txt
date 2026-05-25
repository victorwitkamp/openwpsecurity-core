OpenWPSecurity Core
===================

Composer package: openwpsecurity/core
PHP: 8.2+
License: GPL-2.0-or-later

OpenWPSecurity Core contains shared PHP infrastructure used by OpenWPSecurity WordPress plugins. It does not define WordPress plugin tables, options, admin pages, or activation hooks.

Current API surface:

* VictorWitkamp\OpenWPSecurity\Core\Http\IpAddressInspector
  Resolves trusted IP headers and normalizes IPv4 and IPv6 candidates.

* VictorWitkamp\OpenWPSecurity\Core\Http\WordPressServerRequestFactory
  Creates a PSR-7 ServerRequestInterface from the active WordPress request using laminas/laminas-diactoros.

* VictorWitkamp\OpenWPSecurity\Core\Http\Response\ResponseDispatcher
  Emits HTML, JSON, text, and redirect responses using PSR-7 response factories and laminas/laminas-httphandlerrunner.

Runtime dependencies:

* laminas/laminas-diactoros
* laminas/laminas-httphandlerrunner
* psr/http-message
* psr/http-factory

Install:

	composer require openwpsecurity/core:^0.1

The WordPress adapter classes expect WordPress runtime functions such as get_option() and wp_json_encode() to be available.
