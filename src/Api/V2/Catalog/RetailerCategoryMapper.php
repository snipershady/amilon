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

namespace Amilon\Api\V2\Catalog;

use Amilon\Dto\Response\RetailerCategoryCollectionDto;
use Amilon\Dto\Response\RetailerCategoryDto;
use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierServiceInterface;

/**
 * Maps the V2 `retailers/categories` response — a JSON array of
 * `{"CategoryId": …, "CategoryName": …}` rows — into a
 * {@see RetailerCategoryCollectionDto}.
 *
 * Every field read goes through {@see EffectivePrimitiveTypeIdentifierServiceInterface}
 * so a numeric `CategoryId` or a missing key resolves to a definite string. Rows
 * that are not objects are skipped.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class RetailerCategoryMapper
{
    public function __construct(
        private EffectivePrimitiveTypeIdentifierServiceInterface $types,
    ) {
    }

    /**
     * @param array<array-key, mixed> $payload decoded categories response
     */
    public function mapCollection(array $payload): RetailerCategoryCollectionDto
    {
        $categories = [];

        foreach ($payload as $row) {
            if (is_array($row)) {
                $categories[] = $this->mapRow($row);
            }
        }

        return new RetailerCategoryCollectionDto($categories);
    }

    /**
     * @param array<array-key, mixed> $row
     */
    public function mapRow(array $row): RetailerCategoryDto
    {
        return new RetailerCategoryDto(
            categoryId: $this->types->getStringValueFromArray('CategoryId', $row, trim: true),
            categoryName: $this->types->getStringValueFromArray('CategoryName', $row, trim: true),
        );
    }
}
