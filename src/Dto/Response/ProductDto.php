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

namespace Amilon\Dto\Response;

/**
 * One purchasable gift-card product in an Amilon catalogue.
 *
 * A flat, version-shared projection of an entry in the `contracts/{id}/{country}/products`
 * response. It carries no behaviour and does not validate: the per-revision
 * mapper ({@see \Amilon\Api\V1\Catalog\ProductMapper}) is what turns the
 * loosely-typed API row into these guaranteed scalars.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class ProductDto
{
    public function __construct(
        public string $productCode,
        public string $merchantCode,
        public string $name,
        public float $price,
        public string $imageUrl,
        public bool $active,
        public bool $visible,
    ) {
    }
}
