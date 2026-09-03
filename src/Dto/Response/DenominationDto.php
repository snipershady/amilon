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
 * One denomination (variant) offered by a {@see MerchantDenominationsDto}.
 *
 * V2 replaced the per-value "product" of V1 with a denomination attached to a
 * merchant, so a single `code` (the merchant/retailer id) covers a whole span of
 * face values. Which of the three shapes the migration guide describes this
 * denomination is in can be read from the predicates:
 *
 *  - {@see self::isFixed()} — a discrete face value: `range*`/`step` are `null`
 *    and `prices` holds exactly one entry (a merchant with fixed denominations
 *    exposes several of these `DenominationDto`s).
 *  - {@see self::isVariable()} — a free span: `rangeMin`/`rangeMax`/`step` are
 *    set, `prices` is empty, and every multiple of `step` within the range is a
 *    valid face value.
 *  - {@see self::hasContractPriceOverride()} — a fixed set fixed by the contract:
 *    `range*`/`step` are `null` and `prices` holds one or more explicit entries.
 *
 * A flat, version-shared projection of an entry in the `Denominations` array.
 * Behaviour beyond those pure predicates is absent;
 * {@see \Amilon\Api\V2\Catalog\DenominationMapper} produces it from the raw API
 * row (dates through {@see \Amilon\Support\DateParser}, so an absent or
 * unparseable `ActivationDate` is `null`).
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class DenominationDto
{
    /**
     * @param list<DenominationPriceDto> $prices
     */
    public function __construct(
        public string $code,
        public ?\DateTimeImmutable $activationDate,
        public string $imageUrl,
        public ?float $rangeMin,
        public ?float $rangeMax,
        public ?float $step,
        public ?float $discountValue,
        public array $prices,
    ) {
    }

    /**
     * Whether this denomination spans a free range (min/max/step set, no explicit
     * price list): any multiple of {@see self::$step} between {@see self::$rangeMin}
     * and {@see self::$rangeMax} is orderable.
     */
    public function isVariable(): bool
    {
        return !in_array(needle: null, haystack: [$this->rangeMin, $this->rangeMax, $this->step], strict: true);
    }

    /**
     * Whether this denomination is a single discrete face value: no range and
     * exactly one price.
     */
    public function isFixed(): bool
    {
        return !$this->isVariable() && 1 === count($this->prices);
    }

    /**
     * Whether the contract pins this (otherwise variable) merchant to an explicit
     * set of face values: no range, two or more prices.
     */
    public function hasContractPriceOverride(): bool
    {
        return !$this->isVariable() && count($this->prices) > 1;
    }
}
