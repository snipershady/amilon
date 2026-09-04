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

namespace Amilon\Tests\Unit\Api\V2\Catalog;

use Amilon\Api\V2\Catalog\ProductCompatMapper;
use Amilon\Dto\Response\DenominationDto;
use Amilon\Dto\Response\DenominationPriceDto;
use Amilon\Dto\Response\MerchantDenominationCollectionDto;
use Amilon\Dto\Response\MerchantDenominationsDto;
use Amilon\Dto\Response\ProductDto;
use Amilon\Tests\AbstractTestCase;

/**
 * Flattens the three migration-guide denomination shapes into the V1-compatible
 * flat {@see ProductDto} list.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class ProductCompatMapperTest extends AbstractTestCase
{
    private ProductCompatMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new ProductCompatMapper();
    }

    public function testFixedDenominationsBecomeOneProductPerPrice(): void
    {
        $products = $this->mapper->flatten($this->collectionOf(
            $this->merchant('M-1', denominations: [
                $this->denomination('D-70', prices: [new DenominationPriceDto(70.0, 69.3)]),
                $this->denomination('D-865', prices: [new DenominationPriceDto(865.0, 856.35)]),
            ]),
        ))->all();

        $this->assertCount(2, $products);

        $first = $products[0];
        $this->assertSame('D-70', $first->productCode);
        $this->assertSame('M-1', $first->merchantCode);
        $this->assertEqualsWithDelta(70.0, $first->price, PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(69.3, $first->netPrice, PHP_FLOAT_EPSILON);
        $this->assertTrue($first->active);
        $this->assertTrue($first->visible);
        $this->assertFalse($first->isVariablePriced());
        $this->assertNull($first->step);
        $this->assertSame('IdeaShopping - 70,00 €', $first->name);
        $this->assertSame('IdeaShopping', $first->merchantName);
    }

    public function testTheParentMerchantBlockIsCopiedOntoEveryRow(): void
    {
        $products = $this->mapper->flatten($this->collectionOf(
            $this->merchant('M-1', imageUrl: 'https://cdn.example/logo.png', denominations: [
                $this->denomination('D-5', prices: [new DenominationPriceDto(5.0, 4.95)]),
                $this->denomination('D-10', prices: [new DenominationPriceDto(10.0, 9.9)]),
            ]),
        ))->all();

        foreach ($products as $product) {
            $this->assertSame('M-1', $product->merchantCode);
            $this->assertSame('Italy', $product->merchantCountry);
            $this->assertSame('ITA', $product->countryIsoAlpha3);
            $this->assertSame('https://cdn.example/logo.png', $product->merchantImageUrl);
            $this->assertSame('short', $product->merchantShortDescription);
            $this->assertSame('long', $product->merchantLongDescription);
            $this->assertSame('ideashopping-ita', $product->merchantSlug);
            $this->assertSame('Euro', $product->currency);
            $this->assertSame('Sconto fisso per Retailer', $product->rebateTypeName);
            $this->assertEqualsWithDelta(0.0, $product->vatValue, PHP_FLOAT_EPSILON);
            $this->assertSame('FC IVA art. 6-quater', $product->vatValueName);
            $this->assertSame('Voucher', $product->productType);
            $this->assertFalse($product->art100);
        }
    }

    public function testVariableDenominationBecomesOneRangedProduct(): void
    {
        $products = $this->mapper->flatten($this->collectionOf(
            $this->merchant('M-2', denominations: [
                $this->denomination('D-var', rangeMin: 5.0, rangeMax: 500.0, step: 5.0),
            ]),
        ))->all();

        $this->assertCount(1, $products);

        $product = $products[0];
        $this->assertTrue($product->isVariablePriced());
        $this->assertEqualsWithDelta(5.0, $product->price, PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(5.0, $product->rangeMin, PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(500.0, $product->rangeMax, PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(5.0, $product->step, PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(0.0, $product->netPrice, PHP_FLOAT_EPSILON);
    }

    public function testContractPriceOverrideBecomesOneProductPerPriceSharingTheCode(): void
    {
        $products = $this->mapper->flatten($this->collectionOf(
            $this->merchant('M-3', denominations: [
                $this->denomination('D-override', discountValue: null, prices: [
                    new DenominationPriceDto(5.0, 5.0),
                    new DenominationPriceDto(10.0, 10.0),
                    new DenominationPriceDto(25.0, 25.0),
                ]),
            ]),
        ))->all();

        $this->assertCount(3, $products);
        $this->assertSame(['D-override', 'D-override', 'D-override'], array_map(
            static fn (ProductDto $productDto): string => $productDto->productCode,
            $products,
        ));
        $this->assertSame([5.0, 10.0, 25.0], array_map(
            static fn (ProductDto $productDto): float => $productDto->price,
            $products,
        ));
        $this->assertNull($products[0]->discountValue);
    }

    public function testDenominationImageFallsBackToTheMerchantImage(): void
    {
        $products = $this->mapper->flatten($this->collectionOf(
            $this->merchant('M-4', imageUrl: 'https://cdn.example/merchant.png', denominations: [
                $this->denomination('D-noimg', imageUrl: '', prices: [new DenominationPriceDto(15.0, 15.0)]),
                $this->denomination('D-img', imageUrl: 'https://cdn.example/denom.png', prices: [new DenominationPriceDto(20.0, 20.0)]),
            ]),
        ))->all();

        $this->assertSame('https://cdn.example/merchant.png', $products[0]->imageUrl);
        $this->assertSame('https://cdn.example/denom.png', $products[1]->imageUrl);
    }

    public function testDegenerateDenominationWithNoPricesAndNoRangeIsSkipped(): void
    {
        $collection = $this->mapper->flatten($this->collectionOf(
            $this->merchant('M-5', denominations: [
                $this->denomination('D-empty'),
                $this->denomination('D-ok', prices: [new DenominationPriceDto(30.0, 30.0)]),
            ]),
        ));

        $this->assertCount(1, $collection);
        $this->assertSame('D-ok', $collection->all()[0]->productCode);
    }

    public function testAnEmptyCollectionYieldsAnEmptyProductCollection(): void
    {
        $collection = $this->mapper->flatten(new MerchantDenominationCollectionDto([]));

        $this->assertTrue($collection->isEmpty());
        $this->assertCount(0, $collection);
        $this->assertSame([], $collection->all());
    }

    public function testTheResultIsIterableAndCountable(): void
    {
        $collection = $this->mapper->flatten($this->collectionOf(
            $this->merchant('M-6', denominations: [
                $this->denomination('D-1', prices: [new DenominationPriceDto(1.0, 1.0)]),
                $this->denomination('D-2', prices: [new DenominationPriceDto(2.0, 2.0)]),
            ]),
        ));

        $this->assertCount($collection->count(), $collection->all());

        $codes = [];
        foreach ($collection as $product) {
            $codes[] = $product->productCode;
        }

        $this->assertSame(['D-1', 'D-2'], $codes);
    }

    private function collectionOf(MerchantDenominationsDto ...$merchants): MerchantDenominationCollectionDto
    {
        return new MerchantDenominationCollectionDto(array_values($merchants));
    }

    /**
     * @param list<DenominationDto> $denominations
     */
    private function merchant(
        string $code,
        string $imageUrl = 'https://cdn.example/logo.png',
        array $denominations = [],
    ): MerchantDenominationsDto {
        return new MerchantDenominationsDto(
            code: $code,
            country: 'Italy',
            countryIsoAlpha3: 'ITA',
            name: 'IdeaShopping',
            shortDescription: 'short',
            longDescription: 'long',
            imageUrl: $imageUrl,
            slug: 'ideashopping-ita',
            currency: 'Euro',
            currencySymbol: '€ ',
            rebateTypeName: 'Sconto fisso per Retailer',
            vatValue: 0.0,
            vatValueName: 'FC IVA art. 6-quater',
            denominations: $denominations,
        );
    }

    /**
     * @param list<DenominationPriceDto> $prices
     */
    private function denomination(
        string $code,
        string $imageUrl = 'https://cdn.example/denom.png',
        ?float $rangeMin = null,
        ?float $rangeMax = null,
        ?float $step = null,
        ?float $discountValue = 0.02,
        array $prices = [],
    ): DenominationDto {
        return new DenominationDto(
            code: $code,
            activationDate: null,
            imageUrl: $imageUrl,
            rangeMin: $rangeMin,
            rangeMax: $rangeMax,
            step: $step,
            discountValue: $discountValue,
            prices: $prices,
        );
    }
}
