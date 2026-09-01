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

namespace Amilon\Api\V1\Catalog;

use Amilon\Dto\Response\ProductCollectionDto;
use Amilon\Dto\Response\ProductDto;
use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierServiceInterface;

/**
 * Maps the V1 `contracts/{id}/{country}/products` response — a JSON array of
 * PascalCase rows — into a {@see ProductCollectionDto}.
 *
 * Every field read goes through {@see EffectivePrimitiveTypeIdentifierServiceInterface}
 * so string prices, `0`/`1`/`"true"` booleans and missing keys resolve to
 * definite scalars. Rows that are not objects are skipped rather than faked.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class ProductMapper
{
    public function __construct(
        private EffectivePrimitiveTypeIdentifierServiceInterface $types,
    ) {
    }

    /**
     * @param array<array-key, mixed> $payload decoded products response
     */
    public function mapCollection(array $payload): ProductCollectionDto
    {
        $products = [];

        foreach ($payload as $row) {
            if (is_array($row)) {
                $products[] = $this->mapRow($row);
            }
        }

        return new ProductCollectionDto($products);
    }

    /**
     * @param array<array-key, mixed> $row
     */
    public function mapRow(array $row): ProductDto
    {
        return new ProductDto(
            productCode: $this->types->getStringValueFromArray('ProductCode', $row, trim: true),
            merchantCode: $this->types->getStringValueFromArray('MerchantCode', $row, trim: true),
            name: $this->types->getStringValueFromArray('Name', $row, trim: true),
            price: $this->types->getFloatValueFromArray('Price', $row),
            imageUrl: $this->types->getStringValueFromArray('ImageUrl', $row, trim: true),
            active: $this->types->getBoolValueFromArray('Active', $row),
            visible: $this->types->getBoolValueFromArray('Visible', $row),
        );
    }
}
