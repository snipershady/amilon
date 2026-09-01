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

namespace Amilon\Tests\Unit\Api\V1\Catalog;

use Amilon\Api\V1\Catalog\ProductMapper;
use Amilon\Dto\Response\ProductDto;
use Amilon\Tests\AbstractTestCase;
use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierService;

/**
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class ProductMapperTest extends AbstractTestCase
{
    private ProductMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new ProductMapper(new EffectivePrimitiveTypeIdentifierService());
    }

    public function testItMapsARowCoercingLooseScalars(): void
    {
        $product = $this->mapper->mapRow([
            'ProductCode' => '  PC-1  ',
            'MerchantCode' => 'MC-9',
            'Name' => 'Amazon.it',
            'Price' => '25.00',
            'ImageUrl' => 'https://cdn.example/pc-1.png',
            'Active' => 1,
            'Visible' => 0,
        ]);

        $this->assertSame('PC-1', $product->productCode);
        $this->assertSame('MC-9', $product->merchantCode);
        $this->assertSame('Amazon.it', $product->name);
        $this->assertEqualsWithDelta(25.0, $product->price, PHP_FLOAT_EPSILON);
        $this->assertSame('https://cdn.example/pc-1.png', $product->imageUrl);
        $this->assertTrue($product->active);
        $this->assertFalse($product->visible);
    }

    public function testMissingKeysBecomeEmptyScalars(): void
    {
        $product = $this->mapper->mapRow([]);

        $this->assertSame('', $product->productCode);
        $this->assertSame('', $product->name);
        $this->assertEqualsWithDelta(0.0, $product->price, PHP_FLOAT_EPSILON);
        $this->assertFalse($product->active);
        $this->assertFalse($product->visible);
    }

    public function testItMapsACollectionAndSkipsNonObjectRows(): void
    {
        $collection = $this->mapper->mapCollection([
            ['ProductCode' => 'A', 'Price' => 10],
            'not-an-object',
            42,
            ['ProductCode' => 'B', 'Price' => 20],
        ]);

        $this->assertCount(2, $collection);
        $this->assertSame(['A', 'B'], array_map(
            static fn (ProductDto $productDto): string => $productDto->productCode,
            $collection->all(),
        ));
    }

    public function testAnEmptyPayloadYieldsAnEmptyCollection(): void
    {
        $collection = $this->mapper->mapCollection([]);

        $this->assertTrue($collection->isEmpty());
        $this->assertCount(0, $collection);
        $this->assertSame([], $collection->all());
    }
}
