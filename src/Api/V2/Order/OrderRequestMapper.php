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

namespace Amilon\Api\V2\Order;

use Amilon\Dto\Request\CreateOrderRequestDto;
use Amilon\Exception\InvalidOrderRequestException;

/**
 * Turns a version-neutral {@see CreateOrderRequestDto} into the JSON body the V2
 * `orders/create/{contractId}` endpoint expects:
 * `{"ExternalOrderId": …, "OrderRows": [{"RetailerId": …, "Quantity": …, "Price": …}]}`.
 *
 * The V2 shift from V1 lives here: an order row identifies the denomination by
 * `RetailerId` (the merchant `code`) **plus** `Price`, where V1 sent a single
 * `ProductId`. A line with no price cannot describe a V2 order, so this mapper
 * rejects it with {@see InvalidOrderRequestException::missingPrice()} before any
 * HTTP call.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class OrderRequestMapper
{
    /**
     * @return array{ExternalOrderId: non-empty-string, OrderRows: non-empty-list<array{RetailerId: non-empty-string, Quantity: positive-int, Price: float}>}
     *
     * @throws InvalidOrderRequestException when a line carries no price
     */
    public function toPayload(CreateOrderRequestDto $createOrderRequestDto): array
    {
        $orderRows = [];

        foreach ($createOrderRequestDto->lines as $line) {
            if (null === $line->price) {
                throw InvalidOrderRequestException::missingPrice($line->retailerId);
            }

            $orderRows[] = [
                'RetailerId' => $line->retailerId,
                'Quantity' => $line->quantity,
                'Price' => $line->price,
            ];
        }

        return [
            'ExternalOrderId' => $createOrderRequestDto->externalOrderId,
            'OrderRows' => $orderRows,
        ];
    }
}
