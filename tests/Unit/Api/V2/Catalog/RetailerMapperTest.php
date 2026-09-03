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

    public function testItMapsARowCoercingLooseScalars(): void
    {
        $retailer = $this->mapper->mapRow([
            'RetailerId' => 42,
            'Name' => '  Amazon  ',
            'ShortDescription' => 'e-commerce',
            'ImageUrl' => 'https://cdn.example/amazon.png',
            'CodeValidityMonths' => '24',
            'CountryISOAlpha3' => 'ITA',
        ]);

        $this->assertSame('42', $retailer->retailerId);
        $this->assertSame('Amazon', $retailer->name);
        $this->assertSame('e-commerce', $retailer->shortDescription);
        $this->assertSame('https://cdn.example/amazon.png', $retailer->imageUrl);
        $this->assertSame(24, $retailer->codeValidityMonths);
        $this->assertSame('ITA', $retailer->countryIsoAlpha3);
    }

    public function testMissingKeysBecomeEmptyScalars(): void
    {
        $retailer = $this->mapper->mapRow([]);

        $this->assertSame('', $retailer->retailerId);
        $this->assertSame('', $retailer->countryIsoAlpha3);
        $this->assertSame(0, $retailer->codeValidityMonths);
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
