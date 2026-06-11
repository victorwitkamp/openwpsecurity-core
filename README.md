# OpenWPSecurity Core

<img src="assets/banner-772x250.png" alt="OpenWPSecurity" width="772">

[![CI](https://github.com/victorwitkamp/openwpsecurity-core/actions/workflows/ci.yml/badge.svg)](https://github.com/victorwitkamp/openwpsecurity-core/actions/workflows/ci.yml)
[![Packagist](https://img.shields.io/badge/packagist-openwpsecurity%2Fcore-blue)](https://packagist.org/packages/openwpsecurity/core)
[![License](https://img.shields.io/badge/license-GPL--2.0--or--later-blue)](LICENSE)

Composer package: [`openwpsecurity/core`](https://packagist.org/packages/openwpsecurity/core)

OpenWPSecurity Core contains shared PHP infrastructure used by OpenWPSecurity WordPress plugins. It does not define WordPress plugin tables, options, admin pages, or activation hooks.

## Requirements

- PHP 8.2 or newer
- Composer 2
- WordPress runtime for WordPress adapter classes

## Install

```bash
composer require openwpsecurity/core:^0.3
```

## API Surface

`VictorWitkamp\OpenWPSecurity\Core\Http\IpAddressInspector`

Resolves trusted IP headers and normalizes IPv4 and IPv6 candidates.

`VictorWitkamp\OpenWPSecurity\Core\Http\WordPressServerRequestFactory`

Creates a PSR-7 `ServerRequestInterface` from the active WordPress request using `laminas/laminas-diactoros`.

`VictorWitkamp\OpenWPSecurity\Core\Http\Response\ResponseDispatcher`

Emits HTML, JSON, text, and redirect responses using PSR-7 response factories and `laminas/laminas-httphandlerrunner`.

`VictorWitkamp\OpenWPSecurity\Core\Configuration\SettingsInputSanitizer`

Normalizes line-based settings, trusted IP header lists, and IP address lists.

`VictorWitkamp\OpenWPSecurity\Core\Location\GeoIpLookup`

Classifies local/private IP addresses and resolves country metadata through the PHP GeoIP extension or optional remote lookup.

`VictorWitkamp\OpenWPSecurity\Core\Admin\Reporting\ReportPeriod`

Maps supported report periods to labels and durations.

`VictorWitkamp\OpenWPSecurity\Core\Admin\Pagination\AdminPaginator`

Builds WordPress admin pagination links for report tables.

`VictorWitkamp\OpenWPSecurity\Core\Admin\Presentation\CountryDistributionPanel`

Renders the shared country distribution panel used by OpenWPSecurity report pages.

`VictorWitkamp\OpenWPSecurity\Core\Security\Ban\TemporaryBanRepository`

Defines the shared active temporary-ban repository contract.

`VictorWitkamp\OpenWPSecurity\Core\Security\Ban\AbstractTemporaryBanRepository`

Provides indexed table storage, upserts, expiry cleanup, and WordPress object caching for plugin-owned temporary-ban repositories.

`VictorWitkamp\OpenWPSecurity\Core\Security\Ban\AbstractTemporaryBanCounterStore`

Provides atomic per-IP temporary-ban recurrence counters for plugin-owned tables.

`VictorWitkamp\OpenWPSecurity\Core\Security\Ban\TemporaryBanCleanup`

Schedules daily removal of expired temporary-ban rows.

## Runtime Dependencies

- `laminas/laminas-diactoros`
- `laminas/laminas-httphandlerrunner`
- `psr/http-message`
- `psr/http-factory`

The WordPress adapter classes expect WordPress runtime functions such as `get_option()` and `wp_json_encode()` to be available.

## Support

OpenWPSecurity Core is maintained as open-source software. Funding helps cover maintenance, testing, documentation, and continued development.

- GitHub Sponsors: https://github.com/sponsors/victorwitkamp
