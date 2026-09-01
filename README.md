# amilon

PHP client library for the [Amilon](https://www.amilon.eu/) B2B Web API — the
platform welfare agencies, resellers and partner companies use to integrate and
automate the ordering, management and distribution of digital gift cards at scale.

[![CI](https://github.com/snipershady/amilon/actions/workflows/php.yml/badge.svg)](https://github.com/snipershady/amilon/actions/workflows/php.yml)
[![License: GPL v2](https://img.shields.io/badge/License-GPLv2-blue.svg)](./LICENSE)

## Status

**Early development.** The client covers authentication and the first resource
operations — products, retailers, order creation, order read-back and contract
balance. The public surface is stable; more operations will be added the same way.

## Requirements

- **PHP 8.3+** (CI runs 8.3 and 8.4)
- [`symfony/http-client`](https://symfony.com/doc/current/http_client.html) `^7.4` — transport for the HTTP calls
- [`symfony/dotenv`](https://symfony.com/doc/current/components/dotenv.html) `^7.1` — load credentials from `.env` files
- [`snipershady/typeidentifier`](https://packagist.org/packages/snipershady/typeidentifier) `^2.0` — typed reads of the decoded API responses

## Installation

```bash
composer require snipershady/amilon
```

Namespace: `Amilon\` → `src/` (PSR-4).

## Quick start

```php
use Amilon\Dto\CredentialDto;
use Amilon\Dto\Request\CreateOrderRequestDto;
use Amilon\Enum\CountryEnum;
use Amilon\Enum\Environment;
use Amilon\Service\AmilonClientFactory;

$client = AmilonClientFactory::create(new CredentialDto(
    username:     $secrets->get('amilon_username'),
    password:     $secrets->get('amilon_password'),
    clientId:     $secrets->get('amilon_client_id'),
    clientSecret: $secrets->get('amilon_client_secret'),
    authDomain:   'https://b2bstg-sso.amilon.eu/',
    webDomain:    'https://b2bstg-webapi.amilon.eu/b2bwebapi/v1/',
    contractId:   '1ab2c3d4-567e-4b0c-b8da-a3ed94ae6392',
    environment:  Environment::STAGING,
));

// browse the catalogue
$products = $client->getProducts(CountryEnum::IT);
foreach ($products as $product) {
    echo $product->productCode, ' ', $product->name, ' ', $product->price, PHP_EOL;
}

// place an order for one of them
$order = $client->makeOrder(
    CreateOrderRequestDto::singleLine('my-order-001', $products->all()[0]->productCode, 1),
);

foreach ($order->vouchers as $voucher) {
    echo $voucher->voucherLink, PHP_EOL;
}

// read it back later (status may have advanced, more vouchers may be present)
$order = $client->getOrderInfo('my-order-001');

// check the spendable balance
echo $client->getContractInfo()->currentAmount, PHP_EOL;
```

## Credentials

The library never reads the environment on its own. The **caller** decides where
credentials live (a secrets manager, a Symfony parameter bag, `$_ENV`, a database
row), packs one environment's worth into an immutable `CredentialDto` tagged with
an `Environment`, and hands it to `AmilonClientFactory::create()` (a static
method). `CredentialDto` does no validation — every value is trimmed, checked and
normalised inside `create()`, which throws
`Amilon\Exception\InvalidConfigurationException` on anything missing, blank, not a
valid absolute URL, or (for the contract id) not a UUID.

| `CredentialDto` field | Notes |
| --- | --- |
| `username` / `password` | resource-owner credentials |
| `clientId` / `clientSecret` | OAuth client |
| `authDomain` | SSO host, normalised to one trailing `/` |
| `webDomain` | Web API base (keep the `/v1/` segment), normalised to one trailing `/` |
| `contractId` | contract UUID, lower-cased |
| `environment` | `Environment::STAGING` or `Environment::PRODUCTION` — labels the client; `$client->isProduction()` gates money-moving calls |

STAGING and PRODUCTION are isolated Amilon deployments with their own hosts,
credentials and contract ids.

### Reading credentials from `.env`

`symfony/dotenv` can load the `AMILON_*` variables from `.env` (committed,
placeholders) overridden by `.env.local` (git-ignored, real values):

```dotenv
AMILON_USERNAME=...
AMILON_PASSWORD=...
AMILON_CLIENT_ID=...
AMILON_CLIENT_SECRET=...
AMILON_AUTH_DOMAIN=https://b2bstg-sso.amilon.eu/
AMILON_WEB_DOMAIN=https://b2bstg-webapi.amilon.eu/b2bwebapi/v1/
AMILON_CONTRACT_ID=00000000-0000-0000-0000-000000000000
```

`Amilon\Configuration\Configuration::fromEnvironment()` reads `$_SERVER + $_ENV`
and `Configuration::fromArray()` takes an explicit map, if you want to validate
that set on its own; to build a client, map it into a `CredentialDto` as above.

## Operations

Every method is version-less: it returns a DTO from `Amilon\Dto\Response\` that is
the same regardless of which API revision answered.

| `AmilonClient` method | HTTP | Returns |
| --- | --- | --- |
| `getToken()` | `POST {authDomain}connect/token` | `AccessTokenDto` |
| `getProducts(CountryEnum)` | `GET contracts/{id}/{country}/products` | `ProductCollectionDto` |
| `getRetailers(CountryEnum)` | `GET contracts/{id}/{country}/retailers` | `RetailerCollectionDto` |
| `makeOrder(CreateOrderRequestDto)` | `POST orders/create/{id}` | `OrderDto` |
| `getOrderInfo(string $externalOrderId)` | `GET orders/{externalOrderId}/complete` | `OrderDto` |
| `getContractInfo()` | `GET contracts/{id}` | `ContractInfoDto` |

- **`getToken()`** — the OAuth access token, fetched on first use and reused until
  it is near expiry. Resource calls acquire it automatically; call this only when
  you need the raw token. `AccessTokenDto` carries `accessToken`, `tokenType`,
  `expiresAt` and an optional `refreshToken`, plus `isExpired()` and
  `authorizationHeader()`.
- **`getProducts()` / `getRetailers()`** — take a `CountryEnum` (`IT`, `ES`). The
  collections are iterable and countable and expose `all()`, `count()`,
  `isEmpty()`. A `ProductDto` has `productCode`, `merchantCode`, `name`, `price`,
  `imageUrl`, `active`, `visible`; a `RetailerDto` has `retailerId`, `name`,
  `shortDescription`, `imageUrl`, `codeValidityMonths`, `countryIsoAlpha3`.
- **`makeOrder()`** — takes a self-contained `CreateOrderRequestDto`: your own
  `externalOrderId` plus one `OrderLineDto` (`productCode`, `quantity`) per
  product. Build it with `CreateOrderRequestDto::singleLine($id, $code, $qty)` or
  `::fromLines($id, [OrderLineDto::of(...), ...])`. Returns an `OrderDto`:
  `externalOrderId`, `orderStatus`, `orderDate`, `grossAmount`, `netAmount`, and
  `vouchers` (a list of `VoucherDto` — `voucherLink`, validity window, product and
  retailer ids). `vouchers` may be empty right after the call while the order is
  processing.
- **`getOrderInfo()`** — the current state of an order you placed, keyed by the
  `externalOrderId` you chose. Same `OrderDto` shape as `makeOrder()`.
- **`getContractInfo()`** — `ContractInfoDto` with `contractId`, `currentAmount`
  (the spendable balance orders draw down) and `lastUpdate`.

## Errors

Every exception the library throws implements
`Amilon\Exception\AmilonExceptionInterface`, so one `catch` covers the
integration:

| Exception | When |
| --- | --- |
| `InvalidConfigurationException` | a credential is missing, blank or malformed (thrown by `create()`, before any HTTP) |
| `InvalidOrderRequestException` | an order request is malformed — blank id, no lines, blank product code, quantity below 1 (thrown when building the DTO) |
| `AuthenticationException` | the SSO endpoint is unreachable, rejects the credentials, or returns an unusable token |
| `ApiRequestException` | a resource call fails — transport error, non-2xx status, or a non-JSON body; the message carries a short reason lifted from the error body |

```php
use Amilon\Exception\AmilonExceptionInterface;

try {
    $order = $client->makeOrder($request);
} catch (AmilonExceptionInterface $e) {
    // any failure originating from the Amilon integration
}
```

## How it works

- **Version-less surface, internal versioning.** `AmilonClient` forwards each call
  to an implementation of `Amilon\Api\AmilonApiInterface` chosen by
  `Amilon\Api\ApiVersion::latest()`. A future API revision is a new implementation
  under `Amilon\Api\V{n}\` plus one enum line — callers and DTOs do not change.
- **Shared response DTOs.** Each revision's mapper absorbs its own wire quirks
  (PascalCase keys, numbers as strings, `0`/`1` booleans) and produces the same
  `Amilon\Dto\Response\` types, so callers never see a version-specific shape.
- **No shared state.** Each `create()` call returns an independent client with its
  own HTTP transports; the OAuth token lives in memory for that client's lifetime
  only — there is no external cache.

## Using it from Symfony

The library has no bundle and touches no DI container itself — by design (see
[Credentials](#credentials)), it never reaches for `$_ENV` or a container on its
own. Wire it up with a small factory service that turns your app's own
configuration into a `CredentialDto` and calls the library's factory; register
the result under the library's own class so it can be type-hinted anywhere.

> The `.env` / `.env.local` files at the root of *this* repository only feed its
> own test suite (see [Tests](#tests)) — they are not read by a host application.
> A Symfony app that depends on this library keeps its own `AMILON_*`
> configuration (its `.env`, a vault, whatever it already uses for secrets).

```php
// src/Amilon/AmilonClientFactory.php
namespace App\Amilon;

use Amilon\Dto\CredentialDto;
use Amilon\Enum\Environment;
use Amilon\Service\AmilonClient;
use Amilon\Service\AmilonClientFactory as LibraryAmilonClientFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class AmilonClientFactory
{
    public function __construct(
        #[Autowire('%env(AMILON_USERNAME)%')]
        private readonly string $username,
        #[Autowire('%env(AMILON_PASSWORD)%')]
        private readonly string $password,
        #[Autowire('%env(AMILON_CLIENT_ID)%')]
        private readonly string $clientId,
        #[Autowire('%env(AMILON_CLIENT_SECRET)%')]
        private readonly string $clientSecret,
        #[Autowire('%env(AMILON_AUTH_DOMAIN)%')]
        private readonly string $authDomain,
        #[Autowire('%env(AMILON_WEB_DOMAIN)%')]
        private readonly string $webDomain,
        #[Autowire('%env(AMILON_CONTRACT_ID)%')]
        private readonly string $contractId,
        #[Autowire('%env(AMILON_ENVIRONMENT)%')] // "staging" or "production"
        private readonly string $environment,
    ) {
    }

    public function create(): AmilonClient
    {
        return LibraryAmilonClientFactory::create(new CredentialDto(
            username: $this->username,
            password: $this->password,
            clientId: $this->clientId,
            clientSecret: $this->clientSecret,
            authDomain: $this->authDomain,
            webDomain: $this->webDomain,
            contractId: $this->contractId,
            environment: Environment::from($this->environment),
        ));
    }
}
```

```yaml
# config/services.yaml
services:
    App\Amilon\AmilonClientFactory: ~

    Amilon\Service\AmilonClient:
        factory: ['@App\Amilon\AmilonClientFactory', 'create']
```

`AmilonClient` is now an ordinary autowireable service — Symfony builds one per
request (its default, non-shared-across-requests service lifetime), which
matches the library's own "one client, one transport, one in-memory token"
design: each request gets a fresh token lifecycle, nothing leaks between requests.

```php
// src/Controller/GiftCardOrderController.php
namespace App\Controller;

use Amilon\Dto\Request\CreateOrderRequestDto;
use Amilon\Exception\AmilonExceptionInterface;
use Amilon\Service\AmilonClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class GiftCardOrderController
{
    public function __construct(
        private readonly AmilonClient $amilonClient,
    ) {
    }

    #[Route('/gift-cards/orders', name: 'gift_card_order_create', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $productCode = (string) $request->request->get('productCode');
        $quantity = (int) $request->request->get('quantity', 1);
        $externalOrderId = 'order-' . bin2hex(random_bytes(8));

        try {
            $order = $this->amilonClient->makeOrder(
                CreateOrderRequestDto::singleLine($externalOrderId, $productCode, $quantity),
            );
        } catch (AmilonExceptionInterface $amilonExceptionInterface) {
            // InvalidOrderRequestException -> bad input (400);
            // AuthenticationException / ApiRequestException -> upstream failure (502)
            return new JsonResponse(['error' => $amilonExceptionInterface->getMessage()], 502);
        }

        return new JsonResponse([
            'externalOrderId' => $order->externalOrderId,
            'status' => $order->orderStatus,
            'vouchers' => array_map(
                static fn ($voucher): string => $voucher->voucherLink,
                $order->vouchers,
            ),
        ]);
    }
}
```

## Development

Clone the repo and run `composer install`. Every change must keep all of the
following green:

| Tool | Configuration | Command |
| --- | --- | --- |
| [PHPUnit](https://phpunit.de/) | `failOnWarning` / `failOnRisky` / `failOnNotice` / `failOnDeprecation` | `composer test` |
| [PHPStan](https://phpstan.org/) | `level: max`, strict-rules, bleeding edge, zero suppressions in `src/` | `composer phpstan` |
| [PHP-CS-Fixer](https://cs.symfony.com/) | `@Symfony` + `@Symfony:risky` + strict-types rules | `composer cs-check` |
| [Rector](https://getrector.com/) | dead code, code quality, coding style, type declarations, privatization, naming, early return, PHPUnit sets — PHP 8.3 target | `composer rector-dry` |

| Script | Description |
| --- | --- |
| `composer test` | run the whole PHPUnit suite |
| `composer test:unit` | unit suite only — no network, always runnable |
| `composer test:integration` | integration suite against the Amilon sandbox — skips itself while `AMILON_*` are unset |
| `composer quality` | apply the Rector + PHP-CS-Fixer autofixes |
| `composer quality-check` | dry-run Rector + PHP-CS-Fixer + PHPStan |

### Tests

- **Unit tests** cover the pure logic (mappers, DTOs, validation) with no I/O.
- **Integration tests** hit the real Amilon STAGING API and skip themselves until
  the `AMILON_*` credentials are present in the environment.
- Integration tests that need more than the standard credential set — they place a
  real sandbox order, or need a known order id — are in the `opt-in` group,
  excluded from the CI run. Run them deliberately:

  ```bash
  AMILON_RUN_ORDER_TESTS=1 vendor/bin/phpunit --testsuite integration --group opt-in
  ```

## License

GPL-2.0-only. See [LICENSE](./LICENSE).
