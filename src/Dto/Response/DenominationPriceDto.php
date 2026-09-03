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
 * One concrete price point inside a {@see DenominationDto}: the face value the
 * buyer pays (`price`) and what it costs the contract after its rebate
 * (`netPrice`).
 *
 * A flat, version-shared projection of an entry in a denomination's `Prices`
 * array. Behaviour-free and unvalidated;
 * {@see \Amilon\Api\V2\Catalog\DenominationMapper} produces it from the raw API
 * row.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class DenominationPriceDto
{
    public function __construct(
        public float $price,
        public float $netPrice,
    ) {
    }
}
