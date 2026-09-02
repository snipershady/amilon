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

use Amilon\Api\V2\Catalog\DenominationMapper;
use Amilon\Dto\Response\MerchantContentDto;
use Amilon\Dto\Response\MerchantDenominationsDto;
use Amilon\Tests\AbstractTestCase;
use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierService;

/**
 * Feeds the mapper the three denomination shapes described in the V1→V2
 * migration guide plus the `denominations/complete` extra block.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class DenominationMapperTest extends AbstractTestCase
{
    private DenominationMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new DenominationMapper(new EffectivePrimitiveTypeIdentifierService());
    }

    public function testItMapsTheMerchantWrapperCoercingLooseScalars(): void
    {
        $merchant = $this->mapper->mapMerchant([
            'Code' => '  875196f7-5e79-4e6d-8f8f-5e27f8fa2146  ',
            'Country' => 'Italy',
            'CountryISOAlpha3' => 'ITA',
            'Name' => 'IdeaShopping',
            'ShortDescription' => 'Idea Shopping',
            'LongDescription' => '<p>info</p>',
            'ImageUrl' => 'https://cdn.example/logo.png',
            'Slug' => 'ideashopping-ita',
            'Currency' => 'Euro',
            'CurrencySymbol' => '€ ',
            'RebateTypeName' => 'Sconto fisso per Retailer',
            'VatValue' => '0.0000',
            'VatValueName' => 'FC IVA art. 6-quater',
            'Denominations' => [],
        ]);

        $this->assertSame('875196f7-5e79-4e6d-8f8f-5e27f8fa2146', $merchant->code);
        $this->assertSame('ITA', $merchant->countryIsoAlpha3);
        $this->assertSame('Euro', $merchant->currency);
        $this->assertEqualsWithDelta(0.0, $merchant->vatValue, PHP_FLOAT_EPSILON);
        $this->assertSame([], $merchant->denominations);
        $this->assertNotInstanceOf(MerchantContentDto::class, $merchant->extendedContent);
    }

    public function testFixedDenominationsScenario(): void
    {
        $merchant = $this->mapper->mapMerchant([
            'Code' => 'M-1',
            'Denominations' => [
                [
                    'Code' => '4b3cb6ad-eebb-49a9-973c-003821c39d1d',
                    'ActivationDate' => '2017-03-27T18:20:55.71',
                    'ImageUrl' => 'https://cdn.example/70.png',
                    'RangeMin' => null,
                    'RangeMax' => null,
                    'Step' => null,
                    'DiscountValue' => '0.0100',
                    'Prices' => [
                        ['Price' => '70.0000', 'NetPrice' => '69.30000000'],
                    ],
                ],
                [
                    'Code' => 'a14c9d26-b5a7-41a2-b23e-007ea91d6c55',
                    'ActivationDate' => '2017-03-27T18:22:00.09',
                    'ImageUrl' => 'https://cdn.example/865.png',
                    'RangeMin' => null,
                    'RangeMax' => null,
                    'Step' => null,
                    'DiscountValue' => '0.0100',
                    'Prices' => [
                        ['Price' => 865.0, 'NetPrice' => 856.35],
                    ],
                ],
            ],
        ]);

        $this->assertCount(2, $merchant->denominations);

        $first = $merchant->denominations[0];
        $this->assertTrue($first->isFixed());
        $this->assertFalse($first->isVariable());
        $this->assertFalse($first->hasContractPriceOverride());
        $this->assertNull($first->rangeMin);
        $this->assertNull($first->step);
        $this->assertInstanceOf(\DateTimeImmutable::class, $first->activationDate);
        $this->assertCount(1, $first->prices);
        $this->assertEqualsWithDelta(70.0, $first->prices[0]->price, PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(69.3, $first->prices[0]->netPrice, PHP_FLOAT_EPSILON);
    }

    public function testVariableDenominationsScenario(): void
    {
        $merchant = $this->mapper->mapMerchant([
            'Code' => 'M-2',
            'Denominations' => [
                [
                    'Code' => '68675d82-979f-f011-aa09-005056841cb3',
                    'ActivationDate' => '2025-10-02T15:55:53.45',
                    'ImageUrl' => 'https://cdn.example/var.png',
                    'RangeMin' => '5.00',
                    'RangeMax' => '500.00',
                    'Step' => '5.00',
                    'DiscountValue' => '0.0200',
                    'Prices' => [],
                ],
            ],
        ]);

        $this->assertCount(1, $merchant->denominations);
        $denomination = $merchant->denominations[0];

        $this->assertTrue($denomination->isVariable());
        $this->assertFalse($denomination->isFixed());
        $this->assertFalse($denomination->hasContractPriceOverride());
        $this->assertEqualsWithDelta(5.0, $denomination->rangeMin, PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(500.0, $denomination->rangeMax, PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(5.0, $denomination->step, PHP_FLOAT_EPSILON);
        $this->assertSame([], $denomination->prices);
    }

    public function testVariableWithFixedContractOverrideScenario(): void
    {
        $merchant = $this->mapper->mapMerchant([
            'Code' => 'M-3',
            'Denominations' => [
                [
                    'Code' => '68675d82-979f-f011-aa09-005056841cb3',
                    'ActivationDate' => '2025-10-02T15:55:53.45',
                    'ImageUrl' => 'https://cdn.example/override.png',
                    'RangeMin' => null,
                    'RangeMax' => null,
                    'Step' => null,
                    'DiscountValue' => null,
                    'Prices' => [
                        ['Price' => 5.0, 'NetPrice' => 5.0],
                        ['Price' => 10.0, 'NetPrice' => 10.0],
                        ['Price' => 25.0, 'NetPrice' => 25.0],
                    ],
                ],
            ],
        ]);

        $denomination = $merchant->denominations[0];

        $this->assertFalse($denomination->isVariable());
        $this->assertFalse($denomination->isFixed());
        $this->assertTrue($denomination->hasContractPriceOverride());
        $this->assertNull($denomination->discountValue);
        $this->assertCount(3, $denomination->prices);
    }

    public function testCompleteModePopulatesTheExtendedContentBlock(): void
    {
        $collection = $this->mapper->mapCollection([
            [
                'Code' => 'M-1',
                'Denominations' => [],
                'ExtraShortDescription' => 'short',
                'TermsAndConditions' => 'terms',
                'FacebookFanPage' => 'https://facebook.example/idea',
                'Image100x50' => 'https://cdn.example/100x50.png',
                'Image150x150' => 'https://cdn.example/150.png',
                'Image180x70' => 'https://cdn.example/180x70.png',
                'Category1' => 'BDA7B640-2031-4F8B-8241-64D2C0B4B9EF',
                'Category2' => null,
                'Category3' => null,
            ],
        ], withExtendedContent: true);

        $this->assertCount(1, $collection);
        $merchant = $collection->all()[0];
        $this->assertInstanceOf(MerchantDenominationsDto::class, $merchant);
        $this->assertInstanceOf(MerchantContentDto::class, $merchant->extendedContent);
        $this->assertSame('terms', $merchant->extendedContent->termsAndConditions);
        $this->assertSame('BDA7B640-2031-4F8B-8241-64D2C0B4B9EF', $merchant->extendedContent->category1);
        $this->assertSame('', $merchant->extendedContent->category2);
    }

    public function testItSkipsNonObjectMerchantAndDenominationRows(): void
    {
        $collection = $this->mapper->mapCollection([
            ['Code' => 'A', 'Denominations' => [['Code' => 'A-1', 'Prices' => []], 'nope', 42]],
            'not-an-object',
            ['Code' => 'B', 'Denominations' => 'unexpected'],
        ]);

        $this->assertCount(2, $collection);
        $this->assertCount(1, $collection->all()[0]->denominations);
        $this->assertSame([], $collection->all()[1]->denominations);
    }
}
