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

namespace Amilon\Api;

use Amilon\Dto\Request\CreateOrderRequestDto;
use Amilon\Dto\Response\AccessTokenDto;
use Amilon\Dto\Response\ContractInfoDto;
use Amilon\Dto\Response\MerchantDenominationCollectionDto;
use Amilon\Dto\Response\OrderDto;
use Amilon\Dto\Response\ProductCollectionDto;
use Amilon\Dto\Response\RetailerCategoryCollectionDto;
use Amilon\Dto\Response\RetailerCollectionDto;
use Amilon\Enum\CountryEnum;
use Amilon\Exception\ApiRequestException;
use Amilon\Exception\AuthenticationException;
use Amilon\Exception\InvalidOrderRequestException;

/**
 * The version-agnostic set of Amilon Web API operations.
 *
 * One implementation exists per API revision under {@see \Amilon\Api}\V*;
 * {@see \Amilon\Service\AmilonClientFactory} wires up the one named by
 * {@see ApiVersion::latest()} and {@see \Amilon\Service\AmilonClient} forwards to
 * it. Every operation returns a response DTO from {@see \Amilon\Dto\Response} that
 * is shared across revisions, so callers are insulated from which revision
 * answered.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
interface AmilonApiInterface
{
    /**
     * Obtain an OAuth access token for the configured credentials, reusing a
     * still-valid one when the implementation caches it.
     *
     * @throws AuthenticationException when the SSO endpoint is unreachable, rejects
     *                                 the credentials, or answers unusably
     */
    public function getToken(): AccessTokenDto;

    /**
     * The merchants and their gift-card denominations the contract can sell in
     * $country (V2 `denominations`).
     *
     * @throws AuthenticationException when the bearer token cannot be obtained
     * @throws ApiRequestException     when the catalogue call itself fails
     */
    public function getDenominations(CountryEnum $countryEnum): MerchantDenominationCollectionDto;

    /**
     * As {@see self::getDenominations()} but via V2 `denominations/complete`, so
     * each merchant also carries a {@see \Amilon\Dto\Response\MerchantContentDto}.
     *
     * @throws AuthenticationException when the bearer token cannot be obtained
     * @throws ApiRequestException     when the catalogue call itself fails
     */
    public function getDenominationsComplete(CountryEnum $countryEnum): MerchantDenominationCollectionDto;

    /**
     * V1-compatible view of {@see self::getDenominations()}: the merchant →
     * denomination → price tree flattened back into the flat
     * {@see ProductCollectionDto} of {@see \Amilon\Dto\Response\ProductDto} that
     * V1's `getProducts()` returned, so an integration written against the V1
     * surface keeps working. Not a distinct endpoint — it makes the same
     * `denominations` call and reshapes the result.
     *
     * @throws AuthenticationException when the bearer token cannot be obtained
     * @throws ApiRequestException     when the catalogue call itself fails
     */
    public function getProducts(CountryEnum $countryEnum): ProductCollectionDto;

    /**
     * The retailers (brands) available to the contract in $country.
     *
     * @throws AuthenticationException when the bearer token cannot be obtained
     * @throws ApiRequestException     when the catalogue call itself fails
     */
    public function getRetailers(CountryEnum $countryEnum): RetailerCollectionDto;

    /**
     * The platform-wide list of brand categories, optionally narrowed by
     * category id and/or name (V2 `retailers/categories`).
     *
     * @throws AuthenticationException when the bearer token cannot be obtained
     * @throws ApiRequestException     when the catalogue call itself fails
     */
    public function getRetailerCategories(?string $categoryId = null, ?string $categoryName = null): RetailerCategoryCollectionDto;

    /**
     * Place an order for the products and quantities the request carries, with
     * immediate fulfilment.
     *
     * @throws AuthenticationException when the bearer token cannot be obtained
     * @throws ApiRequestException     when Amilon rejects or cannot fulfil the order
     */
    public function makeOrder(CreateOrderRequestDto $createOrderRequestDto): OrderDto;

    /**
     * Place an order with **deferred** fulfilment (`createpostponed`): Amilon
     * registers it now and issues the vouchers later, valid from
     * $codeValidityStartDate (which it accepts only when in the future and at
     * most a month out). Same order rows as {@see self::makeOrder()} and the same
     * {@see OrderDto} back, but its `vouchers` list is usually still empty — read
     * it again with {@see self::getOrderInfoComplete()} once fulfilment has run.
     *
     * @throws InvalidOrderRequestException when the request or the date is malformed
     * @throws AuthenticationException      when the bearer token cannot be obtained
     * @throws ApiRequestException          when Amilon rejects the order
     */
    public function makeOrderPostponed(CreateOrderRequestDto $createOrderRequestDto, \DateTimeImmutable $codeValidityStartDate): OrderDto;

    /**
     * Order summary for $externalOrderId — status and totals, no vouchers (V2
     * `orders/{externalOrderId}`). Use {@see self::getOrderInfoComplete()} to get
     * the issued vouchers too.
     *
     * @throws AuthenticationException when the bearer token cannot be obtained
     * @throws ApiRequestException     when the order is unknown or the call fails
     */
    public function getOrderInfo(string $externalOrderId): OrderDto;

    /**
     * The full order for $externalOrderId — status, totals and every issued
     * voucher (V2 `orders/{externalOrderId}/complete`).
     *
     * @throws AuthenticationException when the bearer token cannot be obtained
     * @throws ApiRequestException     when the order is unknown or the call fails
     */
    public function getOrderInfoComplete(string $externalOrderId): OrderDto;

    /**
     * The configured contract's balance and last-update time.
     *
     * @throws AuthenticationException when the bearer token cannot be obtained
     * @throws ApiRequestException     when the call fails
     */
    public function getContractInfo(): ContractInfoDto;
}
