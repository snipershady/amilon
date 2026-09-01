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

namespace Amilon\Api\V1;

use Amilon\Api\AmilonApiInterface;
use Amilon\Api\V1\Catalog\ProductApi;
use Amilon\Api\V1\Catalog\RetailerApi;
use Amilon\Api\V1\Contract\ContractApi;
use Amilon\Api\V1\Order\OrderApi;
use Amilon\Auth\TokenProvider;
use Amilon\Dto\Request\CreateOrderRequestDto;
use Amilon\Dto\Response\AccessTokenDto;
use Amilon\Dto\Response\ContractInfoDto;
use Amilon\Dto\Response\OrderDto;
use Amilon\Dto\Response\ProductCollectionDto;
use Amilon\Dto\Response\RetailerCollectionDto;
use Amilon\Enum\CountryEnum;

/**
 * {@see AmilonApiInterface} implementation for revision 1 of the Amilon Web API
 * (`.../b2bwebapi/v1/`).
 *
 * A thin façade: it owns one collaborator per resource area ({@see ProductApi},
 * {@see RetailerApi}, …) plus the {@see TokenProvider}, and forwards each
 * operation to the right one. Endpoint knowledge — paths, verbs, payload shapes —
 * lives in those collaborators. It is selected by
 * {@see \Amilon\Service\AmilonClientFactory} while
 * {@see \Amilon\Api\ApiVersion::latest()} points at
 * {@see \Amilon\Api\ApiVersion::V1}; callers never name it.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class V1Api implements AmilonApiInterface
{
    public function __construct(
        private TokenProvider $tokenProvider,
        private ProductApi $productApi,
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
    public function getProducts(CountryEnum $countryEnum): ProductCollectionDto
    {
        return $this->productApi->list($countryEnum);
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
