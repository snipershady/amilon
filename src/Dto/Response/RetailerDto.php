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
 * One retailer (brand) whose gift cards a contract can sell — the full documented
 * `contracts/{id}/{culture}/retailers` row: identity, HQ location and contacts,
 * localized copy, the code-spending rules (`isCombinable` / `isFractionable` /
 * `codeValidityMonths` / `validitySaleDays`), the shop-details block, sale type,
 * VAT and terms.
 *
 * A flat, version-shared projection built by
 * {@see \Amilon\Api\V2\Catalog\RetailerMapper}; behaviour-free and unvalidated.
 * `saleViewTimeUnitId` is an Amilon-internal enum id for the sale view time
 * unit; `vatValue` is the VAT rate as an integer percentage (e.g. `20` for 20%).
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class RetailerDto
{
    public function __construct(
        public string $retailerId,
        public string $name,
        public string $country,
        public string $countryIsoAlpha3,
        public string $region,
        public string $county,
        public string $city,
        public string $address,
        public string $zipCode,
        public string $phone,
        public string $email,
        public string $shortDescription,
        public string $longDescription,
        public string $termsAndConditions,
        public int $codeValidityMonths,
        public string $imageUrl,
        public string $slug,
        public bool $retailerShopShowDetails,
        public string $retailerShopDetailsText,
        public bool $isCombinable,
        public bool $isFractionable,
        public int $validitySaleDays,
        public int $saleViewTimeUnitId,
        public string $retailerSaleType,
        public int $vatValue,
        public string $vatValueName,
    ) {
    }
}
