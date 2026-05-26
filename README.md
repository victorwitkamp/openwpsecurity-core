# OpenWPSecurity Core

[![CI](https://github.com/victorwitkamp/openwpsecurity-core/actions/workflows/ci.yml/badge.svg)](https://github.com/victorwitkamp/openwpsecurity-core/actions/workflows/ci.yml)
[![Packagist Version](https://img.shields.io/packagist/v/openwpsecurity/core.svg)](https://packagist.org/packages/openwpsecurity/core)
[![License](https://img.shields.io/packagist/l/openwpsecurity/core.svg)](https://packagist.org/packages/openwpsecurity/core)

Composer package: [`openwpsecurity/core`](https://packagist.org/packages/openwpsecurity/core)

OpenWPSecurity Core contains shared PHP infrastructure used by OpenWPSecurity WordPress plugins. It does not define WordPress plugin tables, options, admin pages, or activation hooks.

## Requirements

- PHP 8.2 or newer
- Composer 2
- WordPress runtime for WordPress adapter classes

## Install

```bash
composer require openwpsecurity/core:^0.1
```

## API Surface

`VictorWitkamp\OpenWPSecurity\Core\Http\IpAddressInspector`

Resolves trusted IP headers and normalizes IPv4 and IPv6 candidates.

`VictorWitkamp\OpenWPSecurity\Core\Http\WordPressServerRequestFactory`

Creates a PSR-7 `ServerRequestInterface` from the active WordPress request using `laminas/laminas-diactoros`.

`VictorWitkamp\OpenWPSecurity\Core\Http\Response\ResponseDispatcher`

Emits HTML, JSON, text, and redirect responses using PSR-7 response factories and `laminas/laminas-httphandlerrunner`.

## Runtime Dependencies

- `laminas/laminas-diactoros`
- `laminas/laminas-httphandlerrunner`
- `psr/http-message`
- `psr/http-factory`

The WordPress adapter classes expect WordPress runtime functions such as `get_option()` and `wp_json_encode()` to be available.
