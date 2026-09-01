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
 * One retailer (brand) whose gift cards a contract can sell in a given country.
 *
 * A flat, version-shared projection of an entry in the
 * `contracts/{id}/{country}/retailers` response. Behaviour-free and unvalidated;
 * {@see \Amilon\Api\V1\Catalog\RetailerMapper} produces it from the raw API row.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class RetailerDto
{
    public function __construct(
        public string $retailerId,
        public string $name,
        public string $shortDescription,
        public string $imageUrl,
        public int $codeValidityMonths,
        public string $countryIsoAlpha3,
    ) {
    }
}
