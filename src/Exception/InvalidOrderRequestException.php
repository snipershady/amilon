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
 * a blank product code or a quantity below one.
 *
 * Like {@see InvalidConfigurationException} this is a caller mistake caught at
 * construction time, before any HTTP call, so a malformed order can never reach
 * the API.
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

    public static function blankProductCode(): self
    {
        return new self('An order line needs a non-blank product code.');
    }

    public static function nonPositiveQuantity(string $productCode, int $quantity): self
    {
        return new self(sprintf(
            'The order line for product "%s" needs a quantity of at least 1, got %d.',
            $productCode,
            $quantity,
        ));
    }
}
