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

use Amilon\Dto\Response\RetailerCollectionDto;
use Amilon\Dto\Response\RetailerDto;
use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierServiceInterface;

/**
 * Maps the V2 `contracts/{id}/{culture}/retailers` response — a JSON array of
 * PascalCase rows — into a {@see RetailerCollectionDto}, reading the full
 * documented field set.
 *
 * Every field read goes through {@see EffectivePrimitiveTypeIdentifierServiceInterface}
 * so a string `CodeValidityMonths`, a `0`/`1` `IsCombinable` or a missing key
 * resolves to a definite scalar. Rows that are not objects are skipped.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class RetailerMapper
{
    public function __construct(
        private EffectivePrimitiveTypeIdentifierServiceInterface $types,
    ) {
    }

    /**
     * @param array<array-key, mixed> $payload decoded retailers response
     */
    public function mapCollection(array $payload): RetailerCollectionDto
    {
        $retailers = [];

        foreach ($payload as $row) {
            if (is_array($row)) {
                $retailers[] = $this->mapRow($row);
            }
        }

        return new RetailerCollectionDto($retailers);
    }

    /**
     * @param array<array-key, mixed> $row
     */
    public function mapRow(array $row): RetailerDto
    {
        return new RetailerDto(
            retailerId: $this->types->getStringValueFromArray('RetailerId', $row, trim: true),
            name: $this->types->getStringValueFromArray('Name', $row, trim: true),
            country: $this->types->getStringValueFromArray('Country', $row, trim: true),
            countryIsoAlpha3: $this->types->getStringValueFromArray('CountryISOAlpha3', $row, trim: true),
            region: $this->types->getStringValueFromArray('Region', $row, trim: true),
            county: $this->types->getStringValueFromArray('County', $row, trim: true),
            city: $this->types->getStringValueFromArray('City', $row, trim: true),
            address: $this->types->getStringValueFromArray('Address', $row, trim: true),
            zipCode: $this->types->getStringValueFromArray('ZipCode', $row, trim: true),
            phone: $this->types->getStringValueFromArray('Phone', $row, trim: true),
            email: $this->types->getStringValueFromArray('Email', $row, trim: true),
            shortDescription: $this->types->getStringValueFromArray('ShortDescription', $row, trim: true),
            longDescription: $this->types->getStringValueFromArray('LongDescription', $row, trim: true),
            termsAndConditions: $this->types->getStringValueFromArray('TermsAndConditions', $row, trim: true),
            codeValidityMonths: $this->types->getIntValueFromArray('CodeValidityMonths', $row),
            imageUrl: $this->types->getStringValueFromArray('ImageUrl', $row, trim: true),
            slug: $this->types->getStringValueFromArray('Slug', $row, trim: true),
            retailerShopShowDetails: $this->types->getBoolValueFromArray('RetailerShopShowDetails', $row),
            retailerShopDetailsText: $this->types->getStringValueFromArray('RetailerShopDetailsText', $row, trim: true),
            isCombinable: $this->types->getBoolValueFromArray('IsCombinable', $row),
            isFractionable: $this->types->getBoolValueFromArray('IsFractionable', $row),
            validitySaleDays: $this->types->getIntValueFromArray('ValiditySaleDays', $row),
            saleViewTimeUnitId: $this->types->getIntValueFromArray('SaleViewTimeUnitId', $row),
            retailerSaleType: $this->types->getStringValueFromArray('RetailerSaleType', $row, trim: true),
            vatValue: $this->types->getIntValueFromArray('VatValue', $row),
            vatValueName: $this->types->getStringValueFromArray('VatValueName', $row, trim: true),
        );
    }
}
