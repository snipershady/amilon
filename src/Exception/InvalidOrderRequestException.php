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

namespace Amilon\Exception;

/**
 * Thrown when the caller hands {@see \Amilon\Dto\Request\CreateOrderRequestDto}
 * data that cannot describe a valid order — a blank external id, no order lines,
 * a blank retailer id, a quantity below one, a non-positive price, (for the V2
 * wire shape) a line with no price at all, or a postponed order whose code
 * validity start date is in the past or more than a month out.
 *
 * Like {@see InvalidConfigurationException} this is a caller mistake caught
 * before any HTTP call — at DTO construction, or in
 * {@see \Amilon\Api\V2\Order\OrderRequestMapper} for the price the V2 body
 * requires — so a malformed order can never reach the API.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class InvalidOrderRequestException extends \InvalidArgumentException implements AmilonExceptionInterface
{
    public static function blankExternalOrderId(): self
    {
        return new self('The order request needs a non-blank external order id.');
    }

    public static function noOrderLines(): self
    {
        return new self('The order request needs at least one order line.');
    }

    public static function blankRetailerId(): self
    {
        return new self('An order line needs a non-blank retailer id.');
    }

    public static function nonPositiveQuantity(string $retailerId, int $quantity): self
    {
        return new self(sprintf(
            'The order line for retailer "%s" needs a quantity of at least 1, got %d.',
            $retailerId,
            $quantity,
        ));
    }

    public static function nonPositivePrice(string $retailerId, float $price): self
    {
        return new self(sprintf(
            'The order line for retailer "%s" needs a price greater than 0, got %s.',
            $retailerId,
            $price,
        ));
    }

    public static function missingPrice(string $retailerId): self
    {
        return new self(sprintf(
            'The order line for retailer "%s" needs a price: the V2 order API identifies a denomination by retailer id and price.',
            $retailerId,
        ));
    }

    public static function codeValidityStartDateOutOfRange(\DateTimeInterface $codeValidityStartDate): self
    {
        return new self(sprintf(
            'A postponed order needs a code validity start date in the future and at most one month out, got %s.',
            $codeValidityStartDate->format(\DateTimeInterface::ATOM),
        ));
    }
}
