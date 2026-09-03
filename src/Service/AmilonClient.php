<?php

declare(strict_types=1);

/*
 * Copyright (C) 2026  Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor,
 * Boston, MA 02110-1301 USA.
 */

namespace Amilon\Service;

use Amilon\Api\AmilonApiInterface;
use Amilon\Configuration\Configuration;
use Amilon\Dto\Request\CreateOrderRequestDto;
use Amilon\Dto\Response\AccessTokenDto;
use Amilon\Dto\Response\ContractInfoDto;
use Amilon\Dto\Response\MerchantDenominationCollectionDto;
use Amilon\Dto\Response\OrderDto;
use Amilon\Dto\Response\ProductCollectionDto;
use Amilon\Dto\Response\RetailerCollectionDto;
use Amilon\Enum\CountryEnum;
use Amilon\Enum\Environment;
use Amilon\Exception\ApiRequestException;
use Amilon\Exception\AuthenticationException;

/**
 * Entry point of the library: the version-less Amilon Web API surface.
 *
 * Do not instantiate this directly — {@see AmilonClientFactory} owns the wiring
 * (URL scoping, default headers, environment labelling, picking the API
 * revision). Each operation forwards to an {@see AmilonApiInterface} bound to
 * {@see \Amilon\Api\ApiVersion::latest()}, so callers get the newest revision
 * without naming it and always receive the shared response DTOs from
 * {@see \Amilon\Dto\Response}.
 *
 * The instance holds no shared cache: one client owns its transports, and the
 * OAuth token lives in memory for that client's lifetime only.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class AmilonClient
{
    /**
     * @internal use {@see AmilonClientFactory::create()}
     */
    public function __construct(
        private Configuration $configuration,
        private Environment $environment,
        private AmilonApiInterface $api,
    ) {
    }

    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    /**
     * The current OAuth access token for the configured credentials, fetched on
     * first use and reused until it is (near) expired.
     *
     * @throws AuthenticationException when the SSO endpoint is unreachable, rejects
     *                                 the credentials, or answers unusably
     */
    public function getToken(): AccessTokenDto
    {
        return $this->api->getToken();
    }

    /**
     * The merchants and their gift-card denominations the contract can sell in
     * $country. Each {@see \Amilon\Dto\Response\MerchantDenominationsDto} groups a
     * whole span of face values under one merchant `code` — pass that `code` plus
     * a chosen price to {@see self::makeOrder()}.
     *
     * @throws AuthenticationException when the bearer token cannot be obtained
     * @throws ApiRequestException     when the catalogue call itself fails
     */
    public function getDenominations(CountryEnum $countryEnum): MerchantDenominationCollectionDto
    {
        return $this->api->getDenominations($countryEnum);
    }

    /**
     * As {@see self::getDenominations()} but each merchant also carries its
     * extended content block ({@see \Amilon\Dto\Response\MerchantContentDto}):
     * long description, extra logo sizes, category ids.
     *
     * @throws AuthenticationException when the bearer token cannot be obtained
     * @throws ApiRequestException     when the catalogue call itself fails
     */
    public function getDenominationsComplete(CountryEnum $countryEnum): MerchantDenominationCollectionDto
    {
        return $this->api->getDenominationsComplete($countryEnum);
    }

    /**
     * V1-compatible flat catalogue: the same data as {@see self::getDenominations()}
     * reshaped into the {@see ProductCollectionDto} of
     * {@see \Amilon\Dto\Response\ProductDto} that V1's `getProducts()` returned,
     * so an integration written against the V1 surface upgrades without code
     * changes. Each denomination price point becomes one product row; a variable
     * (open-range) denomination becomes a single row priced at its `rangeMin`
     * with the range carried across. `active` / `visible` are always `true` and
     * `name` is synthesised as `"{merchant} - {amount} {symbol}"`.
     *
     * Prefer {@see self::getDenominations()} for new code.
     *
     * @throws AuthenticationException when the bearer token cannot be obtained
     * @throws ApiRequestException     when the catalogue call itself fails
     */
    public function getProducts(CountryEnum $countryEnum): ProductCollectionDto
    {
        return $this->api->getProducts($countryEnum);
    }

    /**
     * The retailers (brands) available to the contract in $country.
     *
     * @throws AuthenticationException when the bearer token cannot be obtained
     * @throws ApiRequestException     when the catalogue call itself fails
     */
    public function getRetailers(CountryEnum $countryEnum): RetailerCollectionDto
    {
        return $this->api->getRetailers($countryEnum);
    }

    /**
     * Place an order for the products and quantities the request carries, with
     * immediate fulfilment.
     *
     * This spends real money when the client was built from
     * {@see Environment::PRODUCTION} credentials — gate the call on
     * {@see self::isProduction()} where that matters.
     *
     * @throws AuthenticationException when the bearer token cannot be obtained
     * @throws ApiRequestException     when Amilon rejects or cannot fulfil the order
     */
    public function makeOrder(CreateOrderRequestDto $createOrderRequestDto): OrderDto
    {
        return $this->api->makeOrder($createOrderRequestDto);
    }

    /**
     * Place an order with **deferred** fulfilment (V2 `createpostponed`): Amilon
     * accepts and registers it now and issues the vouchers asynchronously. The
     * returned {@see OrderDto} confirms the order and echoes its
     * `externalOrderId`, but its `vouchers` list is usually still empty — call
     * {@see self::getOrderInfo()} again later to collect them.
     *
     * Like {@see self::makeOrder()} this spends real money on a
     * {@see Environment::PRODUCTION} client — gate it on {@see self::isProduction()}.
     *
     * @throws AuthenticationException when the bearer token cannot be obtained
     * @throws ApiRequestException     when Amilon rejects the order
     */
    public function makeOrderPostponed(CreateOrderRequestDto $createOrderRequestDto): OrderDto
    {
        return $this->api->makeOrderPostponed($createOrderRequestDto);
    }

    /**
     * Read back an order previously placed under $externalOrderId — its current
     * status and the vouchers issued for it.
     *
     * @throws AuthenticationException when the bearer token cannot be obtained
     * @throws ApiRequestException     when the order is unknown or the call fails
     */
    public function getOrderInfo(string $externalOrderId): OrderDto
    {
        return $this->api->getOrderInfo($externalOrderId);
    }

    /**
     * The configured contract's spendable balance and when Amilon last
     * recomputed it.
     *
     * @throws AuthenticationException when the bearer token cannot be obtained
     * @throws ApiRequestException     when the call fails
     */
    public function getContractInfo(): ContractInfoDto
    {
        return $this->api->getContractInfo();
    }

    /**
     * Guard money-moving calls (order creation) with this: it is only true when
     * the client was built from {@see Environment::PRODUCTION} credentials.
     */
    public function isProduction(): bool
    {
        return $this->environment->isProduction();
    }
}
