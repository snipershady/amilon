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
 * an {@see OrderDto}. Every order endpoint answers with this shape:
 * `orders/create/{contractId}`, `orders/createpostponed/{contractId}`,
 * `orders/{externalOrderId}` (summary, no `Vouchers`) and
 * `orders/{externalOrderId}/complete`.
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
            totalRequestedCodes: $this->types->getIntValueFromArray('TotalRequestedCodes', $payload),
            purchaseOrder: $this->types->getStringValueFromArray('PurchaseOrder', $payload, trim: true),
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
            retailerName: $this->types->getStringValueFromArray('RetailerName', $row, trim: true),
            retailerCountry: $this->types->getStringValueFromArray('RetailerCountry', $row, trim: true),
            retailerCountryIsoAlpha3: $this->types->getStringValueFromArray('RetailerCountryISOAlpha3', $row, trim: true),
            voucherLink: $this->types->getStringValueFromArray('VoucherLink', $row, trim: true),
            validityStartDate: DateParser::nullable(
                $this->types->getStringValueFromArray('ValidityStartDate', $row, trim: true),
            ),
            validityEndDate: DateParser::nullable(
                $this->types->getStringValueFromArray('ValidityEndDate', $row, trim: true),
            ),
            cardCode: $this->types->getStringValueFromArray('CardCode', $row, trim: true),
            pin: $this->types->getStringValueFromArray('Pin', $row, trim: true),
            name: $this->types->getStringValueFromArray('Name', $row, trim: true),
            surname: $this->types->getStringValueFromArray('Surname', $row, trim: true),
            email: $this->types->getStringValueFromArray('Email', $row, trim: true),
            dedication: $this->types->getStringValueFromArray('Dedication', $row, trim: true),
            orderFrom: $this->types->getStringValueFromArray('OrderFrom', $row, trim: true),
            orderTo: $this->types->getStringValueFromArray('OrderTo', $row, trim: true),
            amount: $this->types->getFloatValueFromArray('Amount', $row),
            deleted: $this->types->getBoolValueFromArray('Deleted', $row),
        );
    }
}
