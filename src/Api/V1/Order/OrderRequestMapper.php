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

namespace Amilon\Api\V1\Order;

use Amilon\Dto\Request\CreateOrderRequestDto;

/**
 * Turns a version-neutral {@see CreateOrderRequestDto} into the JSON body the V1
 * `orders/create/{contractId}` endpoint expects
 * (`{"ExternalOrderId": …, "OrderRows": [{"ProductId": …, "Quantity": …}]}`).
 *
 * Keeping the PascalCase wire shape here — not on the DTO — is what lets a later
 * revision send a different body from the same request object.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class OrderRequestMapper
{
    /**
     * @return array{ExternalOrderId: non-empty-string, OrderRows: non-empty-list<array{ProductId: non-empty-string, Quantity: positive-int}>}
     */
    public function toPayload(CreateOrderRequestDto $createOrderRequestDto): array
    {
        $orderRows = [];

        foreach ($createOrderRequestDto->lines as $line) {
            $orderRows[] = [
                'ProductId' => $line->productCode,
                'Quantity' => $line->quantity,
            ];
        }

        return [
            'ExternalOrderId' => $createOrderRequestDto->externalOrderId,
            'OrderRows' => $orderRows,
        ];
    }
}
