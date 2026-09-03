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

namespace Amilon\Dto\Response;

use Amilon\Enum\OrderStatus;

/**
 * An Amilon order: what {@see \Amilon\Service\AmilonClient::makeOrder()} /
 * {@see \Amilon\Service\AmilonClient::makeOrderPostponed()} return for a
 * just-placed order and what {@see \Amilon\Service\AmilonClient::getOrderInfo()}
 * (summary) / {@see \Amilon\Service\AmilonClient::getOrderInfoComplete()}
 * (with vouchers) return for an existing one — every order endpoint answers with
 * this shape, so they share this DTO.
 *
 * The caller's `externalOrderId` is echoed back. `vouchers` is empty from the
 * summary `getOrderInfo()` and typically still empty right after
 * `makeOrderPostponed()` while fulfilment is deferred — call
 * `getOrderInfoComplete()` for the final set. `orderStatus` is the raw string;
 * {@see self::status()} is the parsed {@see OrderStatus} when it is one this
 * client models.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class OrderDto
{
    /**
     * @param list<VoucherDto> $vouchers
     */
    public function __construct(
        public string $externalOrderId,
        public string $orderStatus,
        public ?\DateTimeImmutable $orderDate,
        public float $grossAmount,
        public float $netAmount,
        public int $totalRequestedCodes,
        public string $purchaseOrder,
        public array $vouchers,
    ) {
    }

    /**
     * The parsed {@see OrderStatus}, or `null` when Amilon reported a status
     * this client does not model (e.g. an intermediate "Pending" state).
     */
    public function status(): ?OrderStatus
    {
        return OrderStatus::tryFrom($this->orderStatus);
    }
}
