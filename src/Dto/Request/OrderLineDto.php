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
 * One line of a {@see CreateOrderRequestDto}: how many of a given merchant's
 * gift card to order, and — for the V2 API — at which face value.
 *
 * V2 identifies what to buy by `retailerId` (the `code` of a
 * {@see \Amilon\Dto\Response\MerchantDenominationsDto}) plus `price`, not by a
 * per-value product id. Build the line with {@see self::withPrice()} for the V2
 * flow; {@see self::of()} leaves `price` `null` and is only usable against a wire
 * mapper that does not need it.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class OrderLineDto
{
    /**
     * @param non-empty-string $retailerId
     * @param positive-int     $quantity
     * @param float|null       $price      chosen face value; required by the V2 order body
     */
    private function __construct(
        public string $retailerId,
        public int $quantity,
        public ?float $price,
    ) {
    }

    /**
     * A line without a price. Valid to build, but the V2 order mapper rejects it
     * with {@see InvalidOrderRequestException::missingPrice()} — prefer
     * {@see self::withPrice()}.
     *
     * @throws InvalidOrderRequestException when the id is blank or the quantity is below 1
     */
    public static function of(string $retailerId, int $quantity): self
    {
        return new self(self::assertRetailerId($retailerId), self::assertQuantity($retailerId, $quantity), price: null);
    }

    /**
     * The V2 line: a merchant plus the face value to order it at.
     *
     * @throws InvalidOrderRequestException when the id is blank, the quantity is below 1, or the price is not greater than 0
     */
    public static function withPrice(string $retailerId, int $quantity, float $price): self
    {
        $trimmedRetailerId = self::assertRetailerId($retailerId);

        if ($price <= 0.0) {
            throw InvalidOrderRequestException::nonPositivePrice($trimmedRetailerId, $price);
        }

        return new self($trimmedRetailerId, self::assertQuantity($trimmedRetailerId, $quantity), $price);
    }

    /**
     * @return non-empty-string
     *
     * @throws InvalidOrderRequestException
     */
    private static function assertRetailerId(string $retailerId): string
    {
        $trimmed = trim($retailerId);

        if ('' === $trimmed) {
            throw InvalidOrderRequestException::blankRetailerId();
        }

        return $trimmed;
    }

    /**
     * @return positive-int
     *
     * @throws InvalidOrderRequestException
     */
    private static function assertQuantity(string $retailerId, int $quantity): int
    {
        if ($quantity < 1) {
            throw InvalidOrderRequestException::nonPositiveQuantity(trim($retailerId), $quantity);
        }

        return $quantity;
    }
}
