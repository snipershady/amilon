# amilon

PHP client library for the [Amilon](https://www.amilon.eu/) B2B Web API — the
platform welfare agencies, resellers and partner companies use to integrate and
automate the ordering, management and distribution of digital gift cards at scale.

[![CI](https://github.com/snipershady/amilon/actions/workflows/php.yml/badge.svg)](https://github.com/snipershady/amilon/actions/workflows/php.yml)
[![License: GPL v2](https://img.shields.io/badge/License-GPLv2-blue.svg)](./LICENSE)

> **Unofficial integration.** This is a community-maintained, open-source client.
> It is **not** affiliated with, endorsed by, or supported by Amilon S.r.l.; all
> product and company names are the property of their respective owners. Use it at
> your own risk against your own Amilon contract.
>
> Contributions are always welcome — open an issue or a pull request. See
> [Development](#development) for the local setup and the quality gate every
> change must pass.

## Status

**Early development.** The client covers authentication and the first resource
operations — catalogue denominations (with a V1-compatible flat `getProducts()`
view), retailers, order creation, order read-back and contract balance. The
public surface is stable; more operations will be added the same way.

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
    webDomainV2:  'https://b2bstg-webapi.amilon.eu/b2bwebapi/v2/',
    contractId:   '1ab2c3d4-567e-4b0c-b8da-a3ed94ae6392',
    environment:  Environment::STAGING,
));

// browse the catalogue: one entry per merchant, each with a list of denominations
$merchants = $client->getDenominations(CountryEnum::IT);
foreach ($merchants as $merchant) {
    foreach ($merchant->denominations as $denomination) {
        // denomination shape: ->isFixed() / ->isVariable() / ->hasContractPriceOverride()
        $price = $denomination->prices[0]->price ?? $denomination->rangeMin;
        echo $merchant->code, ' ', $merchant->name, ' ', $price, PHP_EOL;
    }
}

// place an order: identify the item by merchant code + chosen face value.
// pick the first denomination that can actually be ordered — one with an
// explicit price or an open range (skip a denomination that has neither)
$merchant = $merchants->all()[0];
$denomination = $merchant->denominations[0];
foreach ($merchants as $candidate) {
    foreach ($candidate->denominations as $option) {
        if ([] !== $option->prices || $option->isVariable()) {
            [$merchant, $denomination] = [$candidate, $option];
            break 2;
        }
    }
}
$price = $denomination->prices[0]->price ?? $denomination->rangeMin;

$order = $client->makeOrder(
    CreateOrderRequestDto::singleLineWithPrice('my-order-001', $merchant->code, 1, $price),
);

foreach ($order->vouchers as $voucher) {
    echo $voucher->voucherLink, PHP_EOL;
}

// read it back later — summary only, or ...Complete() for the vouchers too
$order = $client->getOrderInfoComplete('my-order-001');

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
| `webDomain` | V1 Web API base (keep the `/v1/` segment), normalised to one trailing `/` — still required, kept for rollback |
| `webDomainV2` | V2 Web API base (keep the `/v2/` segment), normalised to one trailing `/` — what the client actually talks to |
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
AMILON_WEB_DOMAIN_V2=https://b2bstg-webapi.amilon.eu/b2bwebapi/v2/
AMILON_CONTRACT_ID=00000000-0000-0000-0000-000000000000
```

`Amilon\Configuration\Configuration::fromEnvironment()` reads `$_SERVER + $_ENV`
and `Configuration::fromArray()` takes an explicit map, if you want to validate
that set on its own; to build a client, map it into a `CredentialDto` as above.

## Operations

Every method is version-less: it returns a DTO from `Amilon\Dto\Response\` that is
the same regardless of which API revision answered. The client currently speaks
**v2** (`ApiVersion::latest()`).

| `AmilonClient` method | HTTP | Returns |
| --- | --- | --- |
| `getToken()` | `POST {authDomain}connect/token` | `AccessTokenDto` |
| `getDenominations(CountryEnum)` | `GET contracts/{id}/{culture}/denominations` | `MerchantDenominationCollectionDto` |
| `getDenominationsComplete(CountryEnum)` | `GET contracts/{id}/{culture}/denominations/complete` | `MerchantDenominationCollectionDto` |
| `getProducts(CountryEnum)` | `GET contracts/{id}/{culture}/denominations` (reshaped) | `ProductCollectionDto` |
| `getRetailers(CountryEnum)` | `GET contracts/{id}/{culture}/retailers` | `RetailerCollectionDto` |
| `getRetailerCategories(?string $categoryId, ?string $categoryName)` | `GET retailers/categories` | `RetailerCategoryCollectionDto` |
| `makeOrder(CreateOrderRequestDto)` | `POST orders/create/{id}` | `OrderDto` |
| `makeOrderPostponed(CreateOrderRequestDto, DateTimeImmutable $codeValidityStartDate)` | `POST orders/createpostponed/{id}` | `OrderDto` |
| `getOrderInfo(string $externalOrderId)` | `GET orders/{externalOrderId}` | `OrderDto` |
| `getOrderInfoComplete(string $externalOrderId)` | `GET orders/{externalOrderId}/complete` | `OrderDto` |
| `getContractInfo()` | `GET contracts/{id}` | `ContractInfoDto` |

Each operation below shows a minimal call and the DTO it returns. The response
blocks are `symfony/var-dumper` dumps of the actual objects (`+` public, `-`
private property); string values are truncated for readability.

### `getToken()`

The OAuth access token for the configured credentials, fetched on first use and
reused until it is near expiry. Resource calls acquire it automatically — call
this only when you need the raw token (e.g. to call Amilon yourself).

```php
$token = $client->getToken();

$token->authorizationHeader();   // "Bearer eyJhbGciOiJSUzI1NiIsImtpZCI6..."
$token->isExpired();             // false
```

**Response — `AccessTokenDto`**

```
Amilon\Dto\Response\AccessTokenDto {
  +accessToken: "eyJhbGciOiJSUzI1NiIsImtpZCI6IkE3..."
  +tokenType: "Bearer"                 // defaulted to "Bearer" when the payload omits it
  +expiresAt: DateTimeImmutable @1773572400 { 2026-03-15 11:00:00.0 UTC (+00:00) }
  +refreshToken: null                  // string when the SSO returns one, else null
}
```

### `getDenominations(CountryEnum)`

The merchants and their gift-card denominations the contract can sell in a
country (`CountryEnum` — `IT`, `ES`, `DE`, `DK`, `FR`, `GB`, `NL`, `NO`, `PL`,
`PT`, `SE`; the case is the country, the value the API `language-COUNTRY`
culture tag). The result is iterable and
countable — `->all()`, `->count()`, `->isEmpty()`. Each merchant `code` is what
you pass to `makeOrder()` as the retailer id.

```php
use Amilon\Enum\CountryEnum;

$merchants = $client->getDenominations(CountryEnum::IT);

foreach ($merchants as $merchant) {
    foreach ($merchant->denominations as $denomination) {
        $amount = $denomination->prices[0]->price ?? $denomination->rangeMin;
        echo $merchant->name, ' ', $amount, ' ', $merchant->currencySymbol, PHP_EOL;
    }
}
```

**Response — `MerchantDenominationCollectionDto`**

```
Amilon\Dto\Response\MerchantDenominationCollectionDto {
  // iterable + countable: ->all() ->count() ->isEmpty()
  -merchants: array:12 [
    0 => Amilon\Dto\Response\MerchantDenominationsDto {
      +code: "f72c8dc7-8feb-4dad-bf66-39c8ed238a2b"   // <- retailerId for makeOrder()
      +country: "Spain"
      +countryIsoAlpha3: "ESP"
      +name: "Carrefour"
      +shortDescription: "Carrefour es una cadena de distribución multinacional..."
      +longDescription: "<p>Carrefour es una cadena de distribuci&oacute;n...</p>"
      +imageUrl: "https://eurob2b.amilon.eu/b2bfiles/retailers/f72c8dc7-.../logo/a6fd150c.png"
      +slug: "carrefour-esp"
      +currency: "Euro"
      +currencySymbol: "€"
      +rebateTypeName: "Sconto fisso per Retailer"
      +vatValue: 0.0
      +vatValueName: "FC IVA art. 6-quater"
      +denominations: array:7 [
        0 => Amilon\Dto\Response\DenominationDto {
          +code: "911d5af7-419b-ed11-b820-005056a53626"
          +activationDate: DateTimeImmutable @1674497920 { 2023-01-23 18:18:40.0 UTC (+00:00) }
          +imageUrl: "https://eurob2b.amilon.eu/b2bfiles/products/8f42058d-.../logo/d1ded420.png"
          +rangeMin: null
          +rangeMax: null
          +step: null
          +discountValue: 0.01
          +prices: array:1 [
            0 => Amilon\Dto\Response\DenominationPriceDto {
              +price: 20.0
              +netPrice: 19.8
            }
          ]
          +image136x86: ""   +image461x292: ""   +image200x200: ""   +image300x190: ""   +image560x292: ""
          // the image* sizes are "" here — only getDenominationsComplete() fills them
          // isFixed() => true   isVariable() => false   hasContractPriceOverride() => false
        }
        // 6 more fixed denominations: 100.0, 10.0, 25.0, 50.0, 5.0, 150.0
      ]
      +extendedContent: null            // only getDenominationsComplete() fills this
    }
    // 11 more merchants
  ]
}
```

A `DenominationDto` comes in one of three shapes — read them with the predicates,
never by guessing from the raw fields:

```
// isVariable() — open span, empty prices; any multiple of step in [rangeMin, rangeMax] is orderable
Amilon\Dto\Response\DenominationDto {
  +code: "68675d82-979f-f011-aa09-005056841cb3"
  +rangeMin: 5.0
  +rangeMax: 500.0
  +step: 5.0
  +discountValue: 0.02
  +prices: []
}

// hasContractPriceOverride() — no range, an explicit contract-fixed set of values
Amilon\Dto\Response\DenominationDto {
  +code: "68675d82-979f-f011-aa09-005056841cb3"
  +rangeMin: null
  +rangeMax: null
  +step: null
  +discountValue: null
  +prices: array:7 [
    0 => Amilon\Dto\Response\DenominationPriceDto { +price: 5.0  +netPrice: 5.0 }
    1 => Amilon\Dto\Response\DenominationPriceDto { +price: 10.0 +netPrice: 10.0 }
    // 25.0, 50.0, 100.0, 250.0, 500.0
  ]
}
```

### `getDenominationsComplete(CountryEnum)`

Identical to `getDenominations()`, but every `MerchantDenominationsDto` also
carries its editorial `->extendedContent` block (long copy, extra logo sizes,
category ids) and every `DenominationDto` fills in its five `image*` artwork
sizes.

```php
use Amilon\Enum\CountryEnum;

$merchants = $client->getDenominationsComplete(CountryEnum::IT);

$content = $merchants->all()[0]->extendedContent;   // MerchantContentDto
echo $content->termsAndConditions, PHP_EOL;
```

**Response — `MerchantDenominationCollectionDto`** (same as above, plus)

```
    0 => Amilon\Dto\Response\MerchantDenominationsDto {
      +code: "875196f7-5e79-4e6d-8f8f-5e27f8fa2146"
      +name: "IdeaShopping"
      // ... same merchant fields as getDenominations() ...
      +denominations: array:1 [
        0 => Amilon\Dto\Response\DenominationDto {
          // ... same denomination fields as getDenominations(), plus:
          +image136x86:  "https://b2bstg-web.amilon.eu/B2BFiles/products/.../136x86.png"
          +image461x292: "https://b2bstg-web.amilon.eu/B2BFiles/products/.../461x292.png"
          +image200x200: "https://b2bstg-web.amilon.eu/B2BFiles/products/.../200x200.png"
          +image300x190: "https://b2bstg-web.amilon.eu/B2BFiles/products/.../300x190.png"
          +image560x292: "https://b2bstg-web.amilon.eu/B2BFiles/products/.../560x292.png"
        }
      ]
      +extendedContent: Amilon\Dto\Response\MerchantContentDto {
        +extraShortDescription: "Idea Shopping è la prima Gift Card digitale..."
        +termsAndConditions: "INFORMAZIONI SULLA GIFT CARD IDEASHOPPING\r\n..."
        +facebookFanPage: "https://www.facebook.com/IdeaShopping?fref=ts"
        +image100x50: "https://b2bstg-web.amilon.eu/B2BFiles/retailers/.../idea_shopping.png"
        +image150x150: "https://b2bstg-web.amilon.eu/B2BFiles/retailers/.../idea_shopping_logo_150x150.png"
        +image180x70: "https://b2bstg-web.amilon.eu/B2BFiles/retailers/.../idea_shopping_logo_180x70.png"
        +category1: "BDA7B640-2031-4F8B-8241-64D2C0B4B9EF"
        +category2: ""                 // "" (never null) when the API omits a field
        +category3: ""
      }
    }
```

### `getProducts(CountryEnum)`

A **backward-compatibility view of `getDenominations()`** for integrations
written against the pre-v2 surface: it makes the same call and flattens the
merchant → denomination → price tree into the old flat `ProductCollectionDto`
(iterable/countable) of `ProductDto`. One row per price point; a variable
(open-range) denomination becomes a single row priced at its `rangeMin` with the
range carried across; a denomination with neither prices nor range is dropped.
`active` / `visible` are always `true` (v2 has no such flags), `productType` is a
constant `"Voucher"`, `art100` is always `false`, and `name` is synthesised
`"{merchant} - {amount} {symbol}"`. Every row also carries the parent merchant
block V1's product row had (`merchant*`, `rebateTypeName`, `vatValue*`), copied
off the owning denomination merchant. **New code should use `getDenominations()`.**

```php
use Amilon\Enum\CountryEnum;

$products = $client->getProducts(CountryEnum::IT);

foreach ($products as $product) {
    echo $product->name, ' — ', $product->price, PHP_EOL;   // "Carrefour - 20,00 € — 20"
}
```

**Response — `ProductCollectionDto`**

```
Amilon\Dto\Response\ProductCollectionDto {
  // iterable + countable: ->all() ->count() ->isEmpty()
  -products: array:84 [
    0 => Amilon\Dto\Response\ProductDto {
      +productCode: "911d5af7-419b-ed11-b820-005056a53626"   // denomination code; NOT unique per value
      +merchantCode: "f72c8dc7-8feb-4dad-bf66-39c8ed238a2b"  // == MerchantDenominationsDto->code
      +name: "Carrefour - 20,00 €"                           // synthesised
      +price: 20.0
      +imageUrl: "https://eurob2b.amilon.eu/b2bfiles/products/8f42058d-.../logo/d1ded420.png" // denomination art, merchant-logo fallback
      +active: true                                          // always true
      +visible: true                                         // always true
      +netPrice: 19.8
      +discountValue: 0.01
      +currency: "Euro"
      +currencySymbol: "€"
      +rangeMin: null
      +rangeMax: null
      +step: null
      +activationDate: DateTimeImmutable @1674497920 { 2023-01-23 18:18:40.0 UTC (+00:00) }
      // ── legacy merchant block: copied onto every row from the parent MerchantDenominationsDto,
      //    the same fields V1's product row carried ──
      +merchantName: "Carrefour"
      +countryIsoAlpha3: "ESP"                               // == MerchantCountryISOAlpha3
      +merchantCountry: "Italy"                              // == MerchantCountry
      +merchantImageUrl: "https://eurob2b.amilon.eu/b2bfiles/retailers/f72c8dc7-.../logo/aeab1a64.png" // pure merchant logo
      +merchantShortDescription: "Carrefour, la spesa quotidiana e molto altro."
      +merchantLongDescription: "<p>...</p>"                  // HTML
      +merchantSlug: "carrefour-ita"
      +rebateTypeName: "Sconto fisso per Retailer"
      +vatValue: 0.0                                         // int % as float
      +vatValueName: "FC IVA art. 6-quater"
      +productType: "Voucher"                                // constant (V2 has no product type)
      +art100: false                                         // always false (V2 dropped the flag)
      // isVariablePriced() => false
    }
    // a variable denomination collapses to ONE row: price == rangeMin, rangeMin/rangeMax/step
    // set, netPrice 0.0, isVariablePriced() => true
  ]
}
```

### `getRetailers(CountryEnum)`

The retailers (brands) available to the contract in a country.

```php
use Amilon\Enum\CountryEnum;

$retailers = $client->getRetailers(CountryEnum::IT);

foreach ($retailers as $retailer) {
    echo $retailer->name, ' (', $retailer->codeValidityMonths, ' months)', PHP_EOL;
}
```

**Response — `RetailerCollectionDto`**

```
Amilon\Dto\Response\RetailerCollectionDto {
  // iterable + countable: ->all() ->count() ->isEmpty()
  -retailers: array:120 [
    0 => Amilon\Dto\Response\RetailerDto {
      +retailerId: "f72c8dc7-8feb-4dad-bf66-39c8ed238a2b"
      +name: "Amazon"
      +country: "Italy"
      +countryIsoAlpha3: "ITA"
      +region: "Lombardia"
      +county: "MI"
      +city: "Milano"
      +address: "Via Example 1"
      +zipCode: "20100"
      +phone: "+39 02 0000000"
      +email: "info@example.test"
      +shortDescription: "e-commerce"
      +longDescription: "<p>The everything store.</p>"
      +termsAndConditions: "See amazon.it for full terms."
      +codeValidityMonths: 24
      +imageUrl: "https://eurob2b.amilon.eu/b2bfiles/retailers/.../amazon.png"
      +slug: "amazon-ita"
      +retailerShopShowDetails: true
      +retailerShopDetailsText: "Spendable online at amazon.it"
      +isCombinable: true               // multiple codes can be combined in one purchase
      +isFractionable: false            // a code must be spent in a single transaction
      +validitySaleDays: 365
      +saleViewTimeUnitId: 2            // Amilon-internal enum id
      +retailerSaleType: "Promotional"
      +vatValue: 22                     // VAT rate as an integer percentage
      +vatValueName: "IVA 22%"
    }
    // 119 more retailers
  ]
}
```

### `getRetailerCategories(?string $categoryId = null, ?string $categoryName = null)`

The platform-wide list of brand categories and their translated names — useful to
build a category filter over `getRetailers()` / `getDenominations()`. Not
contract-scoped; pass `$categoryId` and/or `$categoryName` to narrow it.

```php
$categories = $client->getRetailerCategories();

foreach ($categories as $category) {
    echo $category->categoryId, ' => ', $category->categoryName, PHP_EOL;
}
```

**Response — `RetailerCategoryCollectionDto`**

```
Amilon\Dto\Response\RetailerCategoryCollectionDto {
  // iterable + countable: ->all() ->count() ->isEmpty()
  -categories: array:2 [
    0 => Amilon\Dto\Response\RetailerCategoryDto {
      +categoryId: "3fa85f64-5717-4562-b3fc-2c963f66afa6"
      +categoryName: "Elettronica"
    }
    1 => Amilon\Dto\Response\RetailerCategoryDto {
      +categoryId: "5ba85f64-5717-4562-b3fc-2c963f66afd2"
      +categoryName: "Libri"
    }
  ]
}
```

### `makeOrder(CreateOrderRequestDto)`

Place an order with **immediate** fulfilment. The request is self-contained: your
own `externalOrderId` plus one `OrderLineDto` (`retailerId`, `quantity`, `price`)
per merchant. v2 identifies the item by retailer id **and** face value, so a line
with no price is rejected (`InvalidOrderRequestException`) before any HTTP call.
Spends real money on a `Environment::PRODUCTION` client — gate on
`$client->isProduction()`.

```php
use Amilon\Dto\Request\CreateOrderRequestDto;
use Amilon\Dto\Request\OrderLineDto;

// one merchant, one face value
$order = $client->makeOrder(
    CreateOrderRequestDto::singleLineWithPrice('my-order-001', $merchantCode, 1, 20.0),
);

// or several merchants in a single order
$order = $client->makeOrder(CreateOrderRequestDto::fromLines('my-order-002', [
    OrderLineDto::withPrice($merchantCodeA, 2, 25.0),
    OrderLineDto::withPrice($merchantCodeB, 1, 50.0),
]));

foreach ($order->vouchers as $voucher) {
    echo $voucher->voucherLink, PHP_EOL;
}
```

**Response — `OrderDto`**

```
Amilon\Dto\Response\OrderDto {
  +externalOrderId: "my-order-001"       // echoed back from the request
  +orderStatus: "Completed"              // ->status() parses it to Enum\OrderStatus::COMPLETED
  +orderDate: DateTimeImmutable @1773570600 { 2026-03-15 10:30:00.0 UTC (+00:00) }
  +grossAmount: 20.0
  +netAmount: 19.8
  +totalRequestedCodes: 1
  +purchaseOrder: "PO-2026-014"
  +vouchers: array:1 [
    0 => Amilon\Dto\Response\VoucherDto {
      +productId: "911d5af7-419b-ed11-b820-005056a53626"
      +retailerId: "f72c8dc7-8feb-4dad-bf66-39c8ed238a2b"
      +retailerName: "Amazon"
      +retailerCountry: "Italy"
      +retailerCountryIsoAlpha3: "ITA"
      +voucherLink: "https://voucher.amilon.eu/abc123"
      +validityStartDate: DateTimeImmutable @1773532800 { 2026-03-15 00:00:00.0 UTC (+00:00) }
      +validityEndDate: DateTimeImmutable @1805068800 { 2027-03-15 00:00:00.0 UTC (+00:00) }
      +cardCode: "6039 5000 1234 5678"
      +pin: "4921"
      +name: "Ada"
      +surname: "Lovelace"
      +email: "ada@example.test"
      +dedication: "Happy birthday!"
      +orderFrom: "ACME Welfare"
      +orderTo: "Ada Lovelace"
      +amount: 20.0
      +deleted: false
    }
  ]
}
```

> `vouchers` can be `[]` right after the call while Amilon is still issuing them —
> read the order back with `getOrderInfoComplete()`. `orderDate` and the voucher
> validity dates are `null` when Amilon omits them or sends something unparseable.

### `makeOrderPostponed(CreateOrderRequestDto, DateTimeImmutable $codeValidityStartDate)`

Same order rows and same `OrderDto` back as `makeOrder()`, but fulfilment is
**deferred**: Amilon registers the order now and issues the vouchers
asynchronously, valid from `$codeValidityStartDate`. That date is **mandatory**
and Amilon only accepts it when it is in the future and at most one month out — a
date outside that window is rejected with `InvalidOrderRequestException` before
any HTTP call. `vouchers` is normally empty on the confirmation; collect them
later with `getOrderInfoComplete()`.

```php
$order = $client->makeOrderPostponed(
    CreateOrderRequestDto::singleLineWithPrice('my-order-003', $merchantCode, 1, 20.0),
    new DateTimeImmutable('+7 days'),
);

$order->orderStatus;   // e.g. "Pending"  (->status() is null — not a modelled state)
$order->vouchers;       // [] — issued later
```

**Response — `OrderDto`** (`vouchers` normally empty)

```
Amilon\Dto\Response\OrderDto {
  +externalOrderId: "my-order-003"
  +orderStatus: "Pending"
  +orderDate: DateTimeImmutable @1773570600 { 2026-03-15 10:30:00.0 UTC (+00:00) }
  +grossAmount: 20.0
  +netAmount: 19.8
  +totalRequestedCodes: 1
  +purchaseOrder: ""
  +vouchers: []
}
```

### `getOrderInfo(string $externalOrderId)`

Order **summary** for an order you placed, keyed by the `externalOrderId` you
chose: status and totals, **no vouchers** (`GET orders/{externalOrderId}`). Use
`getOrderInfoComplete()` when you need the issued vouchers too.

```php
$order = $client->getOrderInfo('my-order-003');

echo $order->orderStatus, ' — ', $order->totalRequestedCodes, ' code(s)', PHP_EOL;
```

**Response — `OrderDto`** (`vouchers` is always `[]` from this view)

```
Amilon\Dto\Response\OrderDto {
  +externalOrderId: "my-order-003"
  +orderStatus: "Completed"
  +orderDate: DateTimeImmutable @1773570600 { 2026-03-15 10:30:00.0 UTC (+00:00) }
  +grossAmount: 20.0
  +netAmount: 19.8
  +totalRequestedCodes: 1
  +purchaseOrder: "PO-2026-014"
  +vouchers: []
}
```

### `getOrderInfoComplete(string $externalOrderId)`

The **full** order — status, totals and every issued voucher
(`GET orders/{externalOrderId}/complete`). This is how you pick up the vouchers of
a `makeOrderPostponed()` order once it has been fulfilled.

```php
$order = $client->getOrderInfoComplete('my-order-003');

if ($order->status()?->isCompleted()) {
    foreach ($order->vouchers as $voucher) {
        echo $voucher->voucherLink, PHP_EOL;
    }
}
```

**Response — `OrderDto`** (same shape as `makeOrder()`; `vouchers` populated once fulfilled)

```
Amilon\Dto\Response\OrderDto {
  +externalOrderId: "my-order-003"
  +orderStatus: "Completed"
  +orderDate: DateTimeImmutable @1773570600 { 2026-03-15 10:30:00.0 UTC (+00:00) }
  +grossAmount: 20.0
  +netAmount: 19.8
  +totalRequestedCodes: 1
  +purchaseOrder: "PO-2026-014"
  +vouchers: array:1 [
    0 => Amilon\Dto\Response\VoucherDto {
      +productId: "911d5af7-419b-ed11-b820-005056a53626"
      +retailerId: "f72c8dc7-8feb-4dad-bf66-39c8ed238a2b"
      +retailerName: "Amazon"
      +retailerCountry: "Italy"
      +retailerCountryIsoAlpha3: "ITA"
      +voucherLink: "https://voucher.amilon.eu/abc123"
      +validityStartDate: DateTimeImmutable @1773532800 { 2026-03-15 00:00:00.0 UTC (+00:00) }
      +validityEndDate: DateTimeImmutable @1805068800 { 2027-03-15 00:00:00.0 UTC (+00:00) }
      +cardCode: "6039 5000 1234 5678"
      +pin: "4921"
      +name: "Ada"
      +surname: "Lovelace"
      +email: "ada@example.test"
      +dedication: "Happy birthday!"
      +orderFrom: "ACME Welfare"
      +orderTo: "Ada Lovelace"
      +amount: 20.0
      +deleted: false
    }
  ]
}
```

### `getContractInfo()`

The configured contract's identity, validity window, currency and balances.

```php
$info = $client->getContractInfo();

if ($info->currentAmount < 100.0) {
    // top up before ordering
}

// every denomination in an order must be priced in this currency
$currency = $info->currencyIsoCode;   // e.g. "EUR"
```

**Response — `ContractInfoDto`**

```
Amilon\Dto\Response\ContractInfoDto {
  +contractId: "1ab2c3d4-567e-4b0c-b8da-a3ed94ae6392"
  +contractName: "ACME Welfare 2026"
  +currencyIsoCode: "EUR"             // orders may only buy denominations in this currency
  +currentAmount: 1234.56             // the balance orders draw down (after the last operation)
  +previousAmount: 2000.0             // the balance before the last operation
  +startDate: DateTimeImmutable @1767225600 { 2026-01-01 00:00:00.0 UTC (+00:00) }
  +endDate: DateTimeImmutable @1798761599 { 2026-12-31 23:59:59.0 UTC (+00:00) }
  +lastUpdate: DateTimeImmutable @1773570600 { 2026-03-15 10:30:00.0 UTC (+00:00) }
}
```

`startDate` / `endDate` / `lastUpdate` are `null` when Amilon omits or sends an
unparseable timestamp.

## Errors

Every exception the library throws implements
`Amilon\Exception\AmilonExceptionInterface`, so one `catch` covers the
integration:

| Exception | When |
| --- | --- |
| `InvalidConfigurationException` | a credential is missing, blank or malformed (thrown by `create()`, before any HTTP) |
| `InvalidOrderRequestException` | an order request is malformed — blank id, no lines, blank retailer id, quantity below 1, a v2 line with no price, or a postponed `codeValidityStartDate` that is past / more than a month out (thrown when building the DTO or the request body, before any HTTP) |
| `AuthenticationException` | the SSO endpoint is unreachable, rejects the credentials, or returns an unusable token |
| `ApiRequestException` | a resource call fails — transport error, non-2xx status, or a non-JSON body |

On a non-2xx status `ApiRequestException` parses the error body
(`{"ErrorCode": …, "Message": …}`, plus `CreateOrder`'s `ModelErrors`) and
exposes it:

| Property / method | |
| --- | --- |
| `->httpStatus` | the HTTP status code (`int`), or `null` for a transport failure |
| `->rawErrorCode` | the `ErrorCode` string verbatim (`"0105"`), or `null` — the documented set is **not** exhaustive |
| `->errorCode` | `Amilon\Enum\AmilonErrorCode` when the code is one the client models, else `null` |
| `->validationErrors` | `list<string>` — one `"Property: message; message"` line per `ModelErrors` entry |
| `->isTransient()` | `true` for `0000` / `0500` — the same call can be retried |

```php
use Amilon\Enum\AmilonErrorCode;
use Amilon\Exception\AmilonExceptionInterface;
use Amilon\Exception\ApiRequestException;

try {
    $order = $client->makeOrder($request);
} catch (ApiRequestException $e) {
    if (AmilonErrorCode::INSUFFICIENT_CONTRACT_CREDIT === $e->errorCode) {
        // top up the contract and retry
    }
    if ($e->isTransient()) {
        // 0000 / 0500 — safe to retry with the same input
    }
} catch (AmilonExceptionInterface $e) {
    // any other failure originating from the Amilon integration
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
- **Backward-compatible shims stay in the surface.** When v2 reshaped the
  catalogue (`getProducts()` → the merchant-grouped `getDenominations()`),
  `getProducts()` was kept as an adapter that reprojects the new response into
  the old flat DTO, so an existing integration upgrades without code changes.
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
        #[Autowire('%env(AMILON_WEB_DOMAIN_V2)%')]
        private readonly string $webDomainV2,
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
            webDomainV2: $this->webDomainV2,
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
        $retailerId = (string) $request->request->get('retailerId');
        $quantity = (int) $request->request->get('quantity', 1);
        $price = (float) $request->request->get('price');
        $externalOrderId = 'order-' . bin2hex(random_bytes(8));

        try {
            $order = $this->amilonClient->makeOrder(
                CreateOrderRequestDto::singleLineWithPrice($externalOrderId, $retailerId, $quantity, $price),
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
