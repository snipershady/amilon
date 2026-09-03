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

use Amilon\Api\V2\Catalog\RetailerMapper;
use Amilon\Dto\Response\RetailerDto;
use Amilon\Tests\AbstractTestCase;
use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierService;

/**
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class RetailerMapperTest extends AbstractTestCase
{
    private RetailerMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new RetailerMapper(new EffectivePrimitiveTypeIdentifierService());
    }

    public function testItMapsTheFullRowCoercingLooseScalars(): void
    {
        $retailer = $this->mapper->mapRow([
            'RetailerId' => 42,
            'Name' => '  Amazon  ',
            'Country' => 'Italy',
            'CountryISOAlpha3' => 'ITA',
            'Region' => 'Lombardia',
            'County' => 'MI',
            'City' => 'Milano',
            'Address' => 'Via Roma 1',
            'ZipCode' => '20100',
            'Phone' => '+39 02 1234567',
            'Email' => 'brand@example.test',
            'ShortDescription' => 'e-commerce',
            'LongDescription' => '<p>everything store</p>',
            'TermsAndConditions' => 'see website',
            'CodeValidityMonths' => '24',
            'ImageUrl' => 'https://cdn.example/amazon.png',
            'Slug' => 'amazon-ita',
            'RetailerShopShowDetails' => 1,
            'RetailerShopDetailsText' => 'shop details',
            'IsCombinable' => 'true',
            'IsFractionable' => 0,
            'ValiditySaleDays' => '365',
            'SaleViewTimeUnitId' => '2',
            'RetailerSaleType' => 'Promotional',
            'VatValue' => '22',
            'VatValueName' => 'IVA 22%',
        ]);

        $this->assertSame('42', $retailer->retailerId);
        $this->assertSame('Amazon', $retailer->name);
        $this->assertSame('Italy', $retailer->country);
        $this->assertSame('ITA', $retailer->countryIsoAlpha3);
        $this->assertSame('Lombardia', $retailer->region);
        $this->assertSame('MI', $retailer->county);
        $this->assertSame('Milano', $retailer->city);
        $this->assertSame('Via Roma 1', $retailer->address);
        $this->assertSame('20100', $retailer->zipCode);
        $this->assertSame('+39 02 1234567', $retailer->phone);
        $this->assertSame('brand@example.test', $retailer->email);
        $this->assertSame('e-commerce', $retailer->shortDescription);
        $this->assertSame('<p>everything store</p>', $retailer->longDescription);
        $this->assertSame('see website', $retailer->termsAndConditions);
        $this->assertSame(24, $retailer->codeValidityMonths);
        $this->assertSame('https://cdn.example/amazon.png', $retailer->imageUrl);
        $this->assertSame('amazon-ita', $retailer->slug);
        $this->assertTrue($retailer->retailerShopShowDetails);
        $this->assertSame('shop details', $retailer->retailerShopDetailsText);
        $this->assertTrue($retailer->isCombinable);
        $this->assertFalse($retailer->isFractionable);
        $this->assertSame(365, $retailer->validitySaleDays);
        $this->assertSame(2, $retailer->saleViewTimeUnitId);
        $this->assertSame('Promotional', $retailer->retailerSaleType);
        $this->assertSame(22, $retailer->vatValue);
        $this->assertSame('IVA 22%', $retailer->vatValueName);
    }

    public function testMissingKeysBecomeEmptyScalars(): void
    {
        $retailer = $this->mapper->mapRow([]);

        $this->assertSame('', $retailer->retailerId);
        $this->assertSame('', $retailer->countryIsoAlpha3);
        $this->assertSame('', $retailer->slug);
        $this->assertSame(0, $retailer->codeValidityMonths);
        $this->assertSame(0, $retailer->vatValue);
        $this->assertFalse($retailer->isCombinable);
        $this->assertFalse($retailer->isFractionable);
        $this->assertFalse($retailer->retailerShopShowDetails);
    }

    public function testItMapsACollectionAndSkipsNonObjectRows(): void
    {
        $collection = $this->mapper->mapCollection([
            ['RetailerId' => 'R1'],
            null,
            'nope',
            ['RetailerId' => 'R2'],
        ]);

        $this->assertCount(2, $collection);
        $this->assertSame(['R1', 'R2'], array_map(
            static fn (RetailerDto $retailerDto): string => $retailerDto->retailerId,
            $collection->all(),
        ));
    }

    public function testAnEmptyPayloadYieldsAnEmptyCollection(): void
    {
        $collection = $this->mapper->mapCollection([]);

        $this->assertTrue($collection->isEmpty());
        $this->assertCount(0, $collection);
    }
}
