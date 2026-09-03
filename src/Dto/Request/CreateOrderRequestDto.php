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

namespace Amilon\Dto\Request;

use Amilon\Exception\InvalidOrderRequestException;

/**
 * A complete order to place through {@see \Amilon\Service\AmilonClient::makeOrder()}:
 * the caller's own order id plus one {@see OrderLineDto} per merchant with its
 * quantity and chosen face value. It carries everything the order needs — there
 * is no separate product or order entity to pass alongside it.
 *
 * `externalOrderId` is the caller's identifier for the order; Amilon echoes it
 * back on the confirmation and on {@see \Amilon\Service\AmilonClient::getOrderInfo()}.
 *
 * Build it through {@see self::fromLines()}, {@see self::singleLine()} or
 * {@see self::singleLineWithPrice()} so a blank id or an empty line set is
 * rejected before any HTTP call.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class CreateOrderRequestDto
{
    /**
     * @param non-empty-string             $externalOrderId
     * @param non-empty-list<OrderLineDto> $lines
     */
    private function __construct(
        public string $externalOrderId,
        public array $lines,
    ) {
    }

    /**
     * @param list<OrderLineDto> $lines
     *
     * @throws InvalidOrderRequestException when the id is blank or $lines is empty
     */
    public static function fromLines(string $externalOrderId, array $lines): self
    {
        $trimmedExternalOrderId = trim($externalOrderId);

        if ('' === $trimmedExternalOrderId) {
            throw InvalidOrderRequestException::blankExternalOrderId();
        }

        if ([] === $lines) {
            throw InvalidOrderRequestException::noOrderLines();
        }

        return new self($trimmedExternalOrderId, array_values($lines));
    }

    /**
     * A single-merchant order with no explicit price — see {@see OrderLineDto::of()}.
     *
     * @throws InvalidOrderRequestException when any value is invalid
     */
    public static function singleLine(string $externalOrderId, string $retailerId, int $quantity): self
    {
        return self::fromLines($externalOrderId, [OrderLineDto::of($retailerId, $quantity)]);
    }

    /**
     * The common V2 single-merchant order: one retailer, one face value.
     *
     * @throws InvalidOrderRequestException when any value is invalid
     */
    public static function singleLineWithPrice(string $externalOrderId, string $retailerId, int $quantity, float $price): self
    {
        return self::fromLines($externalOrderId, [OrderLineDto::withPrice($retailerId, $quantity, $price)]);
    }
}
