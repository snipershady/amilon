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

/**
 * An Amilon order: what {@see \Amilon\Service\AmilonClient::makeOrder()} returns
 * for a just-placed order and what {@see \Amilon\Service\AmilonClient::getOrderInfo()}
 * returns for an existing one — the two endpoints answer with the same shape, so
 * they share this DTO.
 *
 * The caller's `externalOrderId` is echoed back. `vouchers` may be empty right
 * after `makeOrder()` while the order is still processing; call `getOrderInfo()`
 * again for the final set.
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
        public array $vouchers,
    ) {
    }
}
