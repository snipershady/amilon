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

namespace Amilon\Enum;

/**
 * The `OrderStatus` string an order carries in the responses of `getOrderInfo`,
 * `getOrderInfoComplete`, `CreateOrder` and `CreateOrderPostponed`, as listed in
 * the "Order Statuses" section of the Web API v2 documentation.
 *
 * The doc tabulates only the two terminal states below. A postponed order sits
 * in an intermediate state ("Pending", referenced by error codes `0401` / `0402`)
 * until fulfilment runs, and the online API may expose further values, so
 * `tryFrom()` returning `null` means "a status we do not model" — never "no
 * status". {@see \Amilon\Dto\Response\OrderDto} keeps the raw string and exposes
 * {@see \Amilon\Dto\Response\OrderDto::status()} for the parsed case.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
enum OrderStatus: string
{
    /** The order is completed. */
    case COMPLETED = 'Completed';

    /** The order is cancelled. */
    case DELETED = 'Deleted';

    /**
     * The English description the documentation gives for this status.
     */
    public function description(): string
    {
        return match ($this) {
            self::COMPLETED => 'The order is completed.',
            self::DELETED => 'The order is cancelled.',
        };
    }

    public function isCompleted(): bool
    {
        return self::COMPLETED === $this;
    }

    public function isCancelled(): bool
    {
        return self::DELETED === $this;
    }
}
