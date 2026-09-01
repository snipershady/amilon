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
 * One line of a {@see CreateOrderRequestDto}: how many of a given product to
 * order. The product is identified by the `productCode` from a
 * {@see \Amilon\Dto\Response\ProductDto} — the library never needs the product
 * object itself.
 *
 * Build it through {@see self::of()} so a blank code or a non-positive quantity
 * is rejected up front.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class OrderLineDto
{
    /**
     * @param non-empty-string $productCode
     * @param positive-int     $quantity
     */
    private function __construct(
        public string $productCode,
        public int $quantity,
    ) {
    }

    /**
     * @throws InvalidOrderRequestException when the code is blank or the quantity is below 1
     */
    public static function of(string $productCode, int $quantity): self
    {
        $trimmedProductCode = trim($productCode);

        if ('' === $trimmedProductCode) {
            throw InvalidOrderRequestException::blankProductCode();
        }

        if ($quantity < 1) {
            throw InvalidOrderRequestException::nonPositiveQuantity($trimmedProductCode, $quantity);
        }

        return new self($trimmedProductCode, $quantity);
    }
}
