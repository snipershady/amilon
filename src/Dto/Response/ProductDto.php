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
 * One purchasable gift-card value, in the flat shape V1's `getProducts()` used.
 *
 * V2 has no per-value product endpoint any more — the catalogue arrives grouped
 * by merchant as {@see MerchantDenominationsDto} / {@see DenominationDto}. This
 * DTO is the **backward-compatibility projection**
 * {@see \Amilon\Api\V2\Catalog\ProductCompatMapper} rebuilds from that tree so an
 * integration written against the V1 surface keeps working unchanged:
 *
 *  - the first seven properties (`productCode` … `visible`) carry their V1
 *    meaning and types verbatim;
 *  - the remaining properties are extra signal — the merchant block V1's product
 *    row also carried (`merchant*`, `currency`, `rebateTypeName`, `vatValue*`,
 *    `productType`), copied onto every row from the parent
 *    {@see MerchantDenominationsDto}, plus the V2-only denomination fields
 *    (`netPrice`, `discountValue`, `range*`, …). A V1-era caller keeps reading
 *    whichever it used; the rest default and are harmless.
 *
 * Notes on the reconstruction:
 *
 *  - `productCode` is the V2 denomination `Code`. V2 no longer guarantees it is a
 *    unique per-value SKU: rows produced from a contract-price-override
 *    denomination share the same `productCode`, one per price point.
 *  - `name` is synthesised (`"{merchant} - {amount} {symbol}"`); the raw merchant
 *    name is in {@see self::$merchantName}.
 *  - `imageUrl` is the denomination artwork, falling back to the merchant logo
 *    when the denomination has none; the pure merchant logo is always in
 *    {@see self::$merchantImageUrl}.
 *  - `productType` is a constant `'Voucher'` — the only product type this API
 *    sells — and `art100` is always `false`: V2 dropped both flags.
 *  - `active` / `visible` are always `true`: V2 exposes no such flags and only
 *    lists sellable denominations.
 *  - a variable (open-range) denomination yields a single row with `price` set to
 *    {@see self::$rangeMin} and {@see self::$rangeMin} / {@see self::$rangeMax} /
 *    {@see self::$step} populated — the caller picks the actual amount.
 *
 * A flat, version-shared projection: it carries no behaviour beyond the
 * {@see self::isVariablePriced()} predicate and does not validate.
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
        public float $netPrice = 0.0,
        public ?float $discountValue = null,
        public string $currency = '',
        public string $currencySymbol = '',
        public ?float $rangeMin = null,
        public ?float $rangeMax = null,
        public ?float $step = null,
        public ?\DateTimeImmutable $activationDate = null,
        public string $merchantName = '',
        public string $countryIsoAlpha3 = '',
        public string $merchantCountry = '',
        public string $merchantImageUrl = '',
        public string $merchantShortDescription = '',
        public string $merchantLongDescription = '',
        public string $merchantSlug = '',
        public string $rebateTypeName = '',
        public float $vatValue = 0.0,
        public string $vatValueName = '',
        public string $productType = 'Voucher',
        public bool $art100 = false,
    ) {
    }

    /**
     * Whether this row stands in for a whole open range rather than a single
     * fixed value: {@see self::$price} is only the lower bound and any multiple of
     * {@see self::$step} up to {@see self::$rangeMax} is orderable.
     */
    public function isVariablePriced(): bool
    {
        return null !== $this->step;
    }
}
