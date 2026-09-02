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

namespace Amilon\Api\V2;

use Amilon\Api\AmilonApiInterface;
use Amilon\Api\V2\Catalog\DenominationApi;
use Amilon\Api\V2\Catalog\RetailerApi;
use Amilon\Api\V2\Contract\ContractApi;
use Amilon\Api\V2\Order\OrderApi;
use Amilon\Auth\TokenProvider;
use Amilon\Dto\Request\CreateOrderRequestDto;
use Amilon\Dto\Response\AccessTokenDto;
use Amilon\Dto\Response\ContractInfoDto;
use Amilon\Dto\Response\MerchantDenominationCollectionDto;
use Amilon\Dto\Response\OrderDto;
use Amilon\Dto\Response\RetailerCollectionDto;
use Amilon\Enum\CountryEnum;

/**
 * {@see AmilonApiInterface} implementation for revision 2 of the Amilon Web API
 * (`.../b2bwebapi/v2/`).
 *
 * A thin façade: it owns one collaborator per resource area
 * ({@see DenominationApi}, {@see RetailerApi}, {@see OrderApi},
 * {@see ContractApi}) plus the {@see TokenProvider}, and forwards each operation
 * to the right one. Endpoint knowledge — paths, verbs, payload shapes — lives in
 * those collaborators. It is selected by
 * {@see \Amilon\Service\AmilonClientFactory} while
 * {@see \Amilon\Api\ApiVersion::latest()} points at
 * {@see \Amilon\Api\ApiVersion::V2}; callers never name it.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class V2Api implements AmilonApiInterface
{
    public function __construct(
        private TokenProvider $tokenProvider,
        private DenominationApi $denominationApi,
        private RetailerApi $retailerApi,
        private OrderApi $orderApi,
        private ContractApi $contractApi,
    ) {
    }

    #[\Override]
    public function getToken(): AccessTokenDto
    {
        return $this->tokenProvider->currentToken();
    }

    #[\Override]
    public function getDenominations(CountryEnum $countryEnum): MerchantDenominationCollectionDto
    {
        return $this->denominationApi->list($countryEnum);
    }

    #[\Override]
    public function getDenominationsComplete(CountryEnum $countryEnum): MerchantDenominationCollectionDto
    {
        return $this->denominationApi->listComplete($countryEnum);
    }

    #[\Override]
    public function getRetailers(CountryEnum $countryEnum): RetailerCollectionDto
    {
        return $this->retailerApi->list($countryEnum);
    }

    #[\Override]
    public function makeOrder(CreateOrderRequestDto $createOrderRequestDto): OrderDto
    {
        return $this->orderApi->create($createOrderRequestDto);
    }

    #[\Override]
    public function getOrderInfo(string $externalOrderId): OrderDto
    {
        return $this->orderApi->complete($externalOrderId);
    }

    #[\Override]
    public function getContractInfo(): ContractInfoDto
    {
        return $this->contractApi->info();
    }
}
