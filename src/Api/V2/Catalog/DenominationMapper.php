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

use Amilon\Dto\Response\DenominationDto;
use Amilon\Dto\Response\DenominationPriceDto;
use Amilon\Dto\Response\MerchantContentDto;
use Amilon\Dto\Response\MerchantDenominationCollectionDto;
use Amilon\Dto\Response\MerchantDenominationsDto;
use Amilon\Support\DateParser;
use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierServiceInterface;

/**
 * Maps the V2 `denominations` / `denominations/complete` response — a JSON array
 * of PascalCase merchant rows, each with a nested `Denominations` list and, per
 * denomination, a nested `Prices` list — into a
 * {@see MerchantDenominationCollectionDto}.
 *
 * Scalars go through {@see EffectivePrimitiveTypeIdentifierServiceInterface};
 * `RangeMin`/`RangeMax`/`Step`/`DiscountValue` are read as **nullable** floats so
 * the three denomination shapes of the migration guide survive the mapping
 * (see {@see DenominationDto}); `ActivationDate` goes through {@see DateParser}
 * (absent or unparseable → `null`). Rows that are not objects are skipped rather
 * than faked.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class DenominationMapper
{
    public function __construct(
        private EffectivePrimitiveTypeIdentifierServiceInterface $types,
    ) {
    }

    /**
     * @param array<array-key, mixed> $payload             decoded denominations response
     * @param bool                    $withExtendedContent read the `complete`-only merchant content block
     */
    public function mapCollection(array $payload, bool $withExtendedContent = false): MerchantDenominationCollectionDto
    {
        $merchants = [];

        foreach ($payload as $row) {
            if (is_array($row)) {
                $merchants[] = $this->mapMerchant($row, $withExtendedContent);
            }
        }

        return new MerchantDenominationCollectionDto($merchants);
    }

    /**
     * @param array<array-key, mixed> $row
     */
    public function mapMerchant(array $row, bool $withExtendedContent = false): MerchantDenominationsDto
    {
        return new MerchantDenominationsDto(
            code: $this->types->getStringValueFromArray('Code', $row, trim: true),
            country: $this->types->getStringValueFromArray('Country', $row, trim: true),
            countryIsoAlpha3: $this->types->getStringValueFromArray('CountryISOAlpha3', $row, trim: true),
            name: $this->types->getStringValueFromArray('Name', $row, trim: true),
            shortDescription: $this->types->getStringValueFromArray('ShortDescription', $row, trim: true),
            longDescription: $this->types->getStringValueFromArray('LongDescription', $row, trim: true),
            imageUrl: $this->types->getStringValueFromArray('ImageUrl', $row, trim: true),
            slug: $this->types->getStringValueFromArray('Slug', $row, trim: true),
            currency: $this->types->getStringValueFromArray('Currency', $row, trim: true),
            currencySymbol: $this->types->getStringValueFromArray('CurrencySymbol', $row, trim: true),
            rebateTypeName: $this->types->getStringValueFromArray('RebateTypeName', $row, trim: true),
            vatValue: $this->types->getFloatValueFromArray('VatValue', $row),
            vatValueName: $this->types->getStringValueFromArray('VatValueName', $row, trim: true),
            denominations: $this->mapDenominations($row['Denominations'] ?? null),
            extendedContent: $withExtendedContent ? $this->mapContent($row) : null,
        );
    }

    /**
     * @return list<DenominationDto>
     */
    private function mapDenominations(mixed $rawDenominations): array
    {
        if (!is_array($rawDenominations)) {
            return [];
        }

        $denominations = [];

        foreach ($rawDenominations as $rawDenomination) {
            if (is_array($rawDenomination)) {
                $denominations[] = $this->mapDenomination($rawDenomination);
            }
        }

        return $denominations;
    }

    /**
     * @param array<array-key, mixed> $row
     */
    private function mapDenomination(array $row): DenominationDto
    {
        return new DenominationDto(
            code: $this->types->getStringValueFromArray('Code', $row, trim: true),
            activationDate: DateParser::nullable(
                $this->types->getStringValueFromArray('ActivationDate', $row, trim: true),
            ),
            imageUrl: $this->types->getStringValueFromArray('ImageUrl', $row, trim: true),
            rangeMin: $this->nullableFloat($row, 'RangeMin'),
            rangeMax: $this->nullableFloat($row, 'RangeMax'),
            step: $this->nullableFloat($row, 'Step'),
            discountValue: $this->nullableFloat($row, 'DiscountValue'),
            prices: $this->mapPrices($row['Prices'] ?? null),
        );
    }

    /**
     * @return list<DenominationPriceDto>
     */
    private function mapPrices(mixed $rawPrices): array
    {
        if (!is_array($rawPrices)) {
            return [];
        }

        $prices = [];

        foreach ($rawPrices as $rawPrice) {
            if (is_array($rawPrice)) {
                $prices[] = new DenominationPriceDto(
                    price: $this->types->getFloatValueFromArray('Price', $rawPrice),
                    netPrice: $this->types->getFloatValueFromArray('NetPrice', $rawPrice),
                );
            }
        }

        return $prices;
    }

    /**
     * @param array<array-key, mixed> $row
     */
    private function mapContent(array $row): MerchantContentDto
    {
        return new MerchantContentDto(
            extraShortDescription: $this->types->getStringValueFromArray('ExtraShortDescription', $row, trim: true),
            termsAndConditions: $this->types->getStringValueFromArray('TermsAndConditions', $row, trim: true),
            facebookFanPage: $this->types->getStringValueFromArray('FacebookFanPage', $row, trim: true),
            image100x50: $this->types->getStringValueFromArray('Image100x50', $row, trim: true),
            image150x150: $this->types->getStringValueFromArray('Image150x150', $row, trim: true),
            image180x70: $this->types->getStringValueFromArray('Image180x70', $row, trim: true),
            category1: $this->types->getStringValueFromArray('Category1', $row, trim: true),
            category2: $this->types->getStringValueFromArray('Category2', $row, trim: true),
            category3: $this->types->getStringValueFromArray('Category3', $row, trim: true),
        );
    }

    /**
     * A float field that Amilon may send as JSON `null` (the range/step/discount
     * columns that are only populated for some denomination shapes). An absent
     * key or an explicit `null` both map to `null`; anything else is coerced.
     *
     * @param array<array-key, mixed> $row
     */
    private function nullableFloat(array $row, string $key): ?float
    {
        if (!array_key_exists($key, $row) || null === $row[$key]) {
            return null;
        }

        return $this->types->getFloatValueFromArray($key, $row);
    }
}
