# Amilon client — HTTP requests

Every request this library sends, with its headers and body. Generated from the
transport layer (`src/Http/AmilonHttpExecutor.php`,
`src/Service/AmilonClientFactory.php`, `src/Auth/TokenProvider.php`) and the V2
resource classes under `src/Api/V2/`.

## How a request is assembled

`AmilonClientFactory::create()` builds **two** `symfony/http-client` transports
from one `HttpClient::create()`, each scoped with `withOptions()`:

| Transport | `base_uri` | Default header | Used by |
| --- | --- | --- | --- |
| SSO | `authDomain` (`AMILON_AUTH_DOMAIN`, forced to one trailing `/`) | `Accept: application/json` | `getToken()` only |
| Web API v2 | `webDomainV2` (`AMILON_WEB_DOMAIN_V2`, forced to one trailing `/`) | `Accept: application/json` | every other operation |

All paths below are **relative** to that `base_uri` — the library never builds an
absolute URL.

- **Auth:** every Web API call gets `Authorization: Bearer <access_token>` added
  by `AmilonHttpExecutor::send()`. The token comes from `getToken()` (below),
  fetched on first use and cached in memory until ~30 s before expiry. The token
  request itself carries **no** `Authorization` header.
- **Bodies:** writes use the Symfony `json` option → the body is JSON-encoded and
  `Content-Type: application/json` is set. The token request uses the `body`
  option with an array → `application/x-www-form-urlencoded`. GET calls have no
  body.
- **Transport-added headers:** `HttpClient::create()` also adds `Accept-Encoding:
  gzip` and a `User-Agent` (e.g. `Symfony HttpClient (Curl)`) on its own; the
  library does not set or control these.

### Path parameter values

| Placeholder | Source | Normalisation | Example |
| --- | --- | --- | --- |
| `{contractId}` | `AMILON_CONTRACT_ID` | lower-cased, must be a UUID | `1ab2c3d4-567e-4b0c-b8da-a3ed94ae6392` |
| `{culture}` | `CountryEnum` argument | enum value | `it-IT` (IT) · `da-DK` (DK) · `de-DE` (DE) · `en-GB` (GB) · `es-ES` (ES) · `fr-FR` (FR) · `nl-NL` (NL) · `nn-NO` (NO) · `pl-PL` (PL) · `pt-PT` (PT) · `sv-SE` (SE) |
| `{externalOrderId}` | caller argument | `rawurlencode()` | `my-order-001` |

The examples below use the STAGING hosts and a Carrefour merchant code
(`f72c8dc7-8feb-4dad-bf66-39c8ed238a2b`) for concreteness.

---

## 1. `getToken()` — OAuth token

```
POST https://b2bstg-sso.amilon.eu/connect/token
```

**Headers**

```
Accept: application/json
Content-Type: application/x-www-form-urlencoded
```

*(no `Authorization`)*

**Body** — `application/x-www-form-urlencoded`

| Field | Value |
| --- | --- |
| `grant_type` | `password` (constant) |
| `client_id` | `AMILON_CLIENT_ID` |
| `client_secret` | `AMILON_CLIENT_SECRET` |
| `username` | `AMILON_USERNAME` |
| `password` | `AMILON_PASSWORD` |

```
grant_type=password&client_id=your-client-id&client_secret=your-client-secret&username=your-username&password=your-password
```

```bash
curl -X POST 'https://b2bstg-sso.amilon.eu/connect/token' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  --data-urlencode 'grant_type=password' \
  --data-urlencode 'client_id=your-client-id' \
  --data-urlencode 'client_secret=your-client-secret' \
  --data-urlencode 'username=your-username' \
  --data-urlencode 'password=your-password'
```

---

## 2. `getDenominations(CountryEnum)` — catalogue

```
GET https://b2bstg-webapi.amilon.eu/b2bwebapi/v2/contracts/{contractId}/{culture}/denominations
```

**Headers**

```
Accept: application/json
Authorization: Bearer <access_token>
```

**Body** — none.

```bash
curl 'https://b2bstg-webapi.amilon.eu/b2bwebapi/v2/contracts/1ab2c3d4-567e-4b0c-b8da-a3ed94ae6392/it-IT/denominations' \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer eyJhbGciOiJSUzI1NiIsImtpZCI6...'
```

---

## 3. `getDenominationsComplete(CountryEnum)` — catalogue + editorial content

```
GET https://b2bstg-webapi.amilon.eu/b2bwebapi/v2/contracts/{contractId}/{culture}/denominations/complete
```

**Headers**

```
Accept: application/json
Authorization: Bearer <access_token>
```

**Body** — none.

```bash
curl 'https://b2bstg-webapi.amilon.eu/b2bwebapi/v2/contracts/1ab2c3d4-567e-4b0c-b8da-a3ed94ae6392/it-IT/denominations/complete' \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer eyJhbGciOiJSUzI1NiIsImtpZCI6...'
```

---

## 4. `getProducts(CountryEnum)` — V1-compat flat catalogue

**No dedicated endpoint.** It issues the *same* request as `getDenominations()`
(section 2) and reshapes the response client-side.

```
GET https://b2bstg-webapi.amilon.eu/b2bwebapi/v2/contracts/{contractId}/{culture}/denominations
```

**Headers**

```
Accept: application/json
Authorization: Bearer <access_token>
```

**Body** — none.

---

## 5. `getRetailers(CountryEnum)` — retailers

```
GET https://b2bstg-webapi.amilon.eu/b2bwebapi/v2/contracts/{contractId}/{culture}/retailers
```

**Headers**

```
Accept: application/json
Authorization: Bearer <access_token>
```

**Body** — none.

```bash
curl 'https://b2bstg-webapi.amilon.eu/b2bwebapi/v2/contracts/1ab2c3d4-567e-4b0c-b8da-a3ed94ae6392/it-IT/retailers' \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer eyJhbGciOiJSUzI1NiIsImtpZCI6...'
```

---

## 6. `getRetailerCategories(?string $categoryId, ?string $categoryName)` — brand categories

```
GET https://b2bstg-webapi.amilon.eu/b2bwebapi/v2/retailers/categories
```

Platform-wide, **not** contract-scoped. `$categoryId` / `$categoryName`, when
given, are appended as a `http_build_query()` query string (`CategoryId=…`,
`CategoryName=…`); with neither, the path has no query string.

**Headers**

```
Accept: application/json
Authorization: Bearer <access_token>
```

**Body** — none.

```bash
curl 'https://b2bstg-webapi.amilon.eu/b2bwebapi/v2/retailers/categories' \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer eyJhbGciOiJSUzI1NiIsImtpZCI6...'

curl 'https://b2bstg-webapi.amilon.eu/b2bwebapi/v2/retailers/categories?CategoryName=Libri' \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer eyJhbGciOiJSUzI1NiIsImtpZCI6...'
```

---

## 7. `makeOrder(CreateOrderRequestDto)` — place an order (immediate)

```
POST https://b2bstg-webapi.amilon.eu/b2bwebapi/v2/orders/create/{contractId}
```

**Headers**

```
Accept: application/json
Authorization: Bearer <access_token>
Content-Type: application/json
```

**Body** — `application/json`. Built by `OrderRequestMapper` — **only** these
fields are sent (one `OrderRows` entry per `OrderLineDto`):

| Field | Type | Source |
| --- | --- | --- |
| `ExternalOrderId` | string | `CreateOrderRequestDto::$externalOrderId` |
| `OrderRows[].RetailerId` | string | `OrderLineDto::$retailerId` (a merchant `code`) |
| `OrderRows[].Quantity` | int ≥ 1 | `OrderLineDto::$quantity` |
| `OrderRows[].Price` | float > 0 | `OrderLineDto::$price` — **required**, a priceless line throws `InvalidOrderRequestException` before any HTTP |

```json
{
  "ExternalOrderId": "my-order-001",
  "OrderRows": [
    { "RetailerId": "f72c8dc7-8feb-4dad-bf66-39c8ed238a2b", "Quantity": 1, "Price": 20.0 }
  ]
}
```

Multi-merchant order (`CreateOrderRequestDto::fromLines()`):

```json
{
  "ExternalOrderId": "my-order-002",
  "OrderRows": [
    { "RetailerId": "875196f7-5e79-4e6d-8f8f-5e27f8fa2146", "Quantity": 2, "Price": 25.0 },
    { "RetailerId": "f72c8dc7-8feb-4dad-bf66-39c8ed238a2b", "Quantity": 1, "Price": 50.0 }
  ]
}
```

```bash
curl -X POST 'https://b2bstg-webapi.amilon.eu/b2bwebapi/v2/orders/create/1ab2c3d4-567e-4b0c-b8da-a3ed94ae6392' \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer eyJhbGciOiJSUzI1NiIsImtpZCI6...' \
  -H 'Content-Type: application/json' \
  -d '{"ExternalOrderId":"my-order-001","OrderRows":[{"RetailerId":"f72c8dc7-8feb-4dad-bf66-39c8ed238a2b","Quantity":1,"Price":20.0}]}'
```

> The Amilon API also accepts `PurchaseOrder` and per-row `Name` / `Surname` /
> `Email` / `Dedication` / `OrderFrom` / `OrderTo` (`api_doc.pdf`). This client
> does **not** send them.

---

## 8. `makeOrderPostponed(CreateOrderRequestDto, DateTimeImmutable $codeValidityStartDate)` — place an order (deferred)

Like section 7 but the path segment is `createpostponed` and the body carries one
extra **required** top-level field, `CodeValidityStartDate` (`OrderRequestMapper::
toPostponedPayload()`, ISO-8601 / `DateTimeInterface::ATOM`). The client rejects a
date that is not in the future or is more than one month out with
`InvalidOrderRequestException` before any HTTP call.

```
POST https://b2bstg-webapi.amilon.eu/b2bwebapi/v2/orders/createpostponed/{contractId}
```

**Headers**

```
Accept: application/json
Authorization: Bearer <access_token>
Content-Type: application/json
```

**Body** — `application/json`, `makeOrder()` shape plus `CodeValidityStartDate`:

```json
{
  "ExternalOrderId": "my-order-003",
  "OrderRows": [
    { "RetailerId": "f72c8dc7-8feb-4dad-bf66-39c8ed238a2b", "Quantity": 1, "Price": 20.0 }
  ],
  "CodeValidityStartDate": "2026-09-10T12:00:00+00:00"
}
```

```bash
curl -X POST 'https://b2bstg-webapi.amilon.eu/b2bwebapi/v2/orders/createpostponed/1ab2c3d4-567e-4b0c-b8da-a3ed94ae6392' \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer eyJhbGciOiJSUzI1NiIsImtpZCI6...' \
  -H 'Content-Type: application/json' \
  -d '{"ExternalOrderId":"my-order-003","OrderRows":[{"RetailerId":"f72c8dc7-8feb-4dad-bf66-39c8ed238a2b","Quantity":1,"Price":20.0}],"CodeValidityStartDate":"2026-09-10T12:00:00+00:00"}'
```

---

## 9. `getOrderInfo(string $externalOrderId)` — order summary

```
GET https://b2bstg-webapi.amilon.eu/b2bwebapi/v2/orders/{externalOrderId}
```

`{externalOrderId}` is `rawurlencode()`d into the path. Status and totals only —
no vouchers.

**Headers**

```
Accept: application/json
Authorization: Bearer <access_token>
```

**Body** — none.

```bash
curl 'https://b2bstg-webapi.amilon.eu/b2bwebapi/v2/orders/my-order-003' \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer eyJhbGciOiJSUzI1NiIsImtpZCI6...'
```

---

## 10. `getOrderInfoComplete(string $externalOrderId)` — order + vouchers

```
GET https://b2bstg-webapi.amilon.eu/b2bwebapi/v2/orders/{externalOrderId}/complete
```

`{externalOrderId}` is `rawurlencode()`d into the path.

**Headers**

```
Accept: application/json
Authorization: Bearer <access_token>
```

**Body** — none.

```bash
curl 'https://b2bstg-webapi.amilon.eu/b2bwebapi/v2/orders/my-order-003/complete' \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer eyJhbGciOiJSUzI1NiIsImtpZCI6...'
```

---

## 11. `getContractInfo()` — contract balance

```
GET https://b2bstg-webapi.amilon.eu/b2bwebapi/v2/contracts/{contractId}
```

**Headers**

```
Accept: application/json
Authorization: Bearer <access_token>
```

**Body** — none.

```bash
curl 'https://b2bstg-webapi.amilon.eu/b2bwebapi/v2/contracts/1ab2c3d4-567e-4b0c-b8da-a3ed94ae6392' \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer eyJhbGciOiJSUzI1NiIsImtpZCI6...'
```

---

## Summary

| # | Client method | Method | Path (relative to `base_uri`) | Body |
| --- | --- | --- | --- | --- |
| 1 | `getToken()` | POST | `connect/token` *(SSO host)* | form: `grant_type`, `client_id`, `client_secret`, `username`, `password` |
| 2 | `getDenominations()` | GET | `contracts/{contractId}/{culture}/denominations` | — |
| 3 | `getDenominationsComplete()` | GET | `contracts/{contractId}/{culture}/denominations/complete` | — |
| 4 | `getProducts()` | GET | `contracts/{contractId}/{culture}/denominations` *(reshaped client-side)* | — |
| 5 | `getRetailers()` | GET | `contracts/{contractId}/{culture}/retailers` | — |
| 6 | `getRetailerCategories()` | GET | `retailers/categories` *(+ optional `?CategoryId=…&CategoryName=…`)* | — |
| 7 | `makeOrder()` | POST | `orders/create/{contractId}` | JSON: `ExternalOrderId`, `OrderRows[]{RetailerId,Quantity,Price}` |
| 8 | `makeOrderPostponed()` | POST | `orders/createpostponed/{contractId}` | JSON: #7 + `CodeValidityStartDate` |
| 9 | `getOrderInfo()` | GET | `orders/{externalOrderId}` | — |
| 10 | `getOrderInfoComplete()` | GET | `orders/{externalOrderId}/complete` | — |
| 11 | `getContractInfo()` | GET | `contracts/{contractId}` | — |

Common to 2–11: `Accept: application/json` + `Authorization: Bearer <access_token>`
(POSTs add `Content-Type: application/json`).
