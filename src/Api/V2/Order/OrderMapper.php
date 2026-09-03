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

use Amilon\Dto\Response\OrderDto;
use Amilon\Dto\Response\VoucherDto;
use Amilon\Support\DateParser;
use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierServiceInterface;

/**
 * Maps the V2 order response — PascalCase, with a nested `Vouchers` array — into
 * an {@see OrderDto}. Both `orders/create/{contractId}` and
 * `orders/{externalOrderId}/complete` answer with this shape, unchanged from V1.
 *
 * Scalars go through {@see EffectivePrimitiveTypeIdentifierServiceInterface};
 * the `Vouchers` list is walked structurally with per-row `is_array()` guards;
 * dates go through {@see DateParser} (unparseable → `null`).
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class OrderMapper
{
    public function __construct(
        private EffectivePrimitiveTypeIdentifierServiceInterface $types,
    ) {
    }

    /**
     * @param array<array-key, mixed> $payload decoded order response
     */
    public function map(array $payload): OrderDto
    {
        return new OrderDto(
            externalOrderId: $this->types->getStringValueFromArray('ExternalOrderId', $payload, trim: true),
            orderStatus: $this->types->getStringValueFromArray('OrderStatus', $payload, trim: true),
            orderDate: DateParser::nullable(
                $this->types->getStringValueFromArray('OrderDate', $payload, trim: true),
            ),
            grossAmount: $this->types->getFloatValueFromArray('GrossAmount', $payload),
            netAmount: $this->types->getFloatValueFromArray('NetAmount', $payload),
            vouchers: $this->mapVouchers($payload['Vouchers'] ?? null),
        );
    }

    /**
     * @return list<VoucherDto>
     */
    private function mapVouchers(mixed $rawVouchers): array
    {
        if (!is_array($rawVouchers)) {
            return [];
        }

        $vouchers = [];

        foreach ($rawVouchers as $rawVoucher) {
            if (is_array($rawVoucher)) {
                $vouchers[] = $this->mapVoucher($rawVoucher);
            }
        }

        return $vouchers;
    }

    /**
     * @param array<array-key, mixed> $row
     */
    private function mapVoucher(array $row): VoucherDto
    {
        return new VoucherDto(
            productId: $this->types->getStringValueFromArray('ProductId', $row, trim: true),
            retailerId: $this->types->getStringValueFromArray('RetailerId', $row, trim: true),
            voucherLink: $this->types->getStringValueFromArray('VoucherLink', $row, trim: true),
            validityStartDate: DateParser::nullable(
                $this->types->getStringValueFromArray('ValidityStartDate', $row, trim: true),
            ),
            validityEndDate: DateParser::nullable(
                $this->types->getStringValueFromArray('ValidityEndDate', $row, trim: true),
            ),
        );
    }
}
