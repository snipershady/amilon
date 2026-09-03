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
use Amilon\Dto\Response\MerchantDenominationCollectionDto;
use Amilon\Dto\Response\MerchantDenominationsDto;
use Amilon\Dto\Response\ProductCollectionDto;
use Amilon\Dto\Response\ProductDto;

/**
 * Flattens the V2 merchant → denomination → price tree
 * ({@see MerchantDenominationCollectionDto}) back into the flat
 * {@see ProductCollectionDto} of {@see ProductDto} that V1's `getProducts()`
 * returned, so an integration written against the V1 surface keeps working after
 * the move to V2.
 *
 * It works on the already-mapped response DTOs, not the raw wire array — the
 * scalar coercion has happened upstream in {@see DenominationMapper} — so it
 * needs no `typeidentifier` collaborator.
 *
 * One denomination becomes:
 *
 *  - one {@see ProductDto} per {@see \Amilon\Dto\Response\DenominationPriceDto}
 *    when the price list is populated (the migration guide's *fixed* and
 *    *contract-price-override* shapes);
 *  - a single {@see ProductDto} priced at {@see DenominationDto::$rangeMin}, with
 *    the range/step carried across, when it is a *variable* open range
 *    ({@see DenominationDto::isVariable()});
 *  - nothing, when it is neither (no prices, no range).
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class ProductCompatMapper
{
    public function flatten(MerchantDenominationCollectionDto $merchantDenominationCollectionDto): ProductCollectionDto
    {
        $products = [];

        foreach ($merchantDenominationCollectionDto as $merchant) {
            foreach ($this->flattenMerchant($merchant) as $product) {
                $products[] = $product;
            }
        }

        return new ProductCollectionDto($products);
    }

    /**
     * @return list<ProductDto>
     */
    private function flattenMerchant(MerchantDenominationsDto $merchantDenominationsDto): array
    {
        $products = [];

        foreach ($merchantDenominationsDto->denominations as $denomination) {
            foreach ($this->flattenDenomination($merchantDenominationsDto, $denomination) as $product) {
                $products[] = $product;
            }
        }

        return $products;
    }

    /**
     * @return list<ProductDto>
     */
    private function flattenDenomination(MerchantDenominationsDto $merchantDenominationsDto, DenominationDto $denominationDto): array
    {
        if ([] !== $denominationDto->prices) {
            $products = [];
            foreach ($denominationDto->prices as $price) {
                $products[] = $this->buildRow($merchantDenominationsDto, $denominationDto, $price->price, $price->netPrice);
            }

            return $products;
        }

        if ($denominationDto->isVariable()) {
            return [$this->buildRow($merchantDenominationsDto, $denominationDto, $denominationDto->rangeMin ?? 0.0, 0.0)];
        }

        return [];
    }

    private function buildRow(
        MerchantDenominationsDto $merchantDenominationsDto,
        DenominationDto $denominationDto,
        float $price,
        float $netPrice,
    ): ProductDto {
        return new ProductDto(
            productCode: $denominationDto->code,
            merchantCode: $merchantDenominationsDto->code,
            name: $this->synthesizeName($merchantDenominationsDto, $price),
            price: $price,
            imageUrl: '' !== $denominationDto->imageUrl ? $denominationDto->imageUrl : $merchantDenominationsDto->imageUrl,
            active: true,
            visible: true,
            netPrice: $netPrice,
            discountValue: $denominationDto->discountValue,
            currency: $merchantDenominationsDto->currency,
            currencySymbol: $merchantDenominationsDto->currencySymbol,
            rangeMin: $denominationDto->rangeMin,
            rangeMax: $denominationDto->rangeMax,
            step: $denominationDto->step,
            activationDate: $denominationDto->activationDate,
            merchantName: $merchantDenominationsDto->name,
            countryIsoAlpha3: $merchantDenominationsDto->countryIsoAlpha3,
        );
    }

    /**
     * Best-effort rebuild of V1's product name, e.g. `"IdeaShopping - 5,00 €"`.
     * V2 only exposes the bare merchant name, so the amount and currency are
     * appended here; the raw merchant name stays available on
     * {@see ProductDto::$merchantName}.
     */
    private function synthesizeName(MerchantDenominationsDto $merchantDenominationsDto, float $price): string
    {
        $amount = number_format($price, 2, ',', '.');
        $symbol = trim($merchantDenominationsDto->currencySymbol);

        if ('' === $symbol) {
            $symbol = $merchantDenominationsDto->currency;
        }

        if ('' === $symbol) {
            return sprintf('%s - %s', $merchantDenominationsDto->name, $amount);
        }

        return sprintf('%s - %s %s', $merchantDenominationsDto->name, $amount, $symbol);
    }
}
