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

use Amilon\Api\V2\Catalog\RetailerCategoryMapper;
use Amilon\Dto\Response\RetailerCategoryDto;
use Amilon\Tests\AbstractTestCase;
use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierService;

/**
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class RetailerCategoryMapperTest extends AbstractTestCase
{
    private RetailerCategoryMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new RetailerCategoryMapper(new EffectivePrimitiveTypeIdentifierService());
    }

    public function testItMapsARowCoercingLooseScalars(): void
    {
        $category = $this->mapper->mapRow([
            'CategoryId' => 5001,
            'CategoryName' => '  Elettronica  ',
        ]);

        $this->assertSame('5001', $category->categoryId);
        $this->assertSame('Elettronica', $category->categoryName);
    }

    public function testMissingKeysBecomeEmptyStrings(): void
    {
        $category = $this->mapper->mapRow([]);

        $this->assertSame('', $category->categoryId);
        $this->assertSame('', $category->categoryName);
    }

    public function testItMapsACollectionAndSkipsNonObjectRows(): void
    {
        $collection = $this->mapper->mapCollection([
            ['CategoryId' => 'C1', 'CategoryName' => 'Books'],
            null,
            'nope',
            ['CategoryId' => 'C2', 'CategoryName' => 'Music'],
        ]);

        $this->assertCount(2, $collection);
        $this->assertSame(['C1', 'C2'], array_map(
            static fn (RetailerCategoryDto $retailerCategoryDto): string => $retailerCategoryDto->categoryId,
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
