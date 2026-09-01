# amilon

PHP client library for the [Amilon](https://www.amilon.eu/) API — the platform that lets
welfare agencies, resellers and partner companies integrate and automate the ordering,
management and distribution of digital gift cards at scale.

## Badges

[![CI](https://github.com/snipershady/amilon/actions/workflows/php.yml/badge.svg)](https://github.com/snipershady/amilon/actions/workflows/php.yml)
[![License: GPL v2](https://img.shields.io/badge/License-GPLv2-blue.svg)](./LICENSE)

## Status

🚧 **Early development.** The tooling scaffold is in place; the API surface is being built out.

## Requirements

- **PHP 8.3 or higher** (tested on 8.3 and 8.4)
- [`symfony/dotenv`](https://symfony.com/doc/current/components/dotenv.html) `^7.1` (runtime dependency, used to load credentials from `.env` files)

## Installation

```bash
composer require snipershady/amilon
```

## Configuration

Credentials are read from environment variables. `symfony/dotenv` loads them
from two files at the project root:

| File | Tracked | Purpose |
| --- | --- | --- |
| `.env` | ✅ committed | placeholder values, documents every recognised key |
| `.env.local` | 🚫 git-ignored | the real credentials — overrides `.env`, never committed |

Recognised keys:

```dotenv
AMILON_USERNAME=...
AMILON_PASSWORD=...
AMILON_CLIENT_ID=...
AMILON_CLIENT_SECRET=...
AMILON_AUTH_DOMAIN=https://b2bstg-sso.amilon.eu/
AMILON_WEB_DOMAIN=https://b2bstg-webapi.amilon.eu/b2bwebapi/v1/
AMILON_CONTRACT_ID=00000000-0000-0000-0000-000000000000
```

```php
use Amilon\Configuration\Configuration;

// reads AMILON_* from the process environment ($_SERVER + $_ENV)
$config = Configuration::fromEnvironment();

// or pass an explicit map (keyed by argument name)
$config = Configuration::fromArray([
    'username' => '...', 'password' => '...', 'clientId' => '...',
    'clientSecret' => '...', 'authDomain' => '...', 'webDomain' => '...',
    'contractId' => '...',
]);
```

`Configuration` is a `final readonly` value object: it trims every value,
rejects anything missing or blank, normalises the endpoint URLs to a single
trailing slash and checks that the contract id is a UUID — a misconfigured
deployment throws `Amilon\Exception\InvalidConfigurationException` up front.

## Quality tooling

The library is developed against the strictest reasonable configuration of the
PHP ecosystem tooling. Every change must keep all of the following green:

| Tool | Configuration | Command |
| --- | --- | --- |
| [PHPUnit](https://phpunit.de/) | `failOnWarning`/`failOnRisky`/`failOnNotice`/`failOnDeprecation` | `composer test` |
| [PHPStan](https://phpstan.org/) | `level: max`, strict-rules, bleeding edge, zero suppressions in `src/` | `composer phpstan` |
| [PHP-CS-Fixer](https://cs.symfony.com/) | `@Symfony` + `@Symfony:risky` + strict-types rules | `composer cs-check` |
| [Rector](https://getrector.com/) | dead code, code quality, coding style, type declarations, privatization, naming, early return, PHPUnit sets | `composer rector-dry` |

### Composer scripts

| Script | Description |
| --- | --- |
| `composer test` | Run the whole PHPUnit suite |
| `composer test:unit` | Run the unit suite only (no I/O) |
| `composer test:integration` | Run the integration suite against the Amilon sandbox (needs `AMILON_*` env vars) |
| `composer quality` | Apply Rector + PHP-CS-Fixer fixes |
| `composer quality-check` | Dry-run Rector + PHP-CS-Fixer + PHPStan (what CI runs) |

## License

GPL-2.0-only. See [LICENSE](./LICENSE).
