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

namespace Amilon\Tests\Unit\Dto\Request;

use Amilon\Dto\Request\OrderLineDto;
use Amilon\Exception\AmilonExceptionInterface;
use Amilon\Exception\InvalidOrderRequestException;
use Amilon\Tests\AbstractTestCase;

/**
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class OrderLineDtoTest extends AbstractTestCase
{
    public function testOfBuildsATrimmedPricelessLine(): void
    {
        $line = OrderLineDto::of('  R-1  ', 3);

        $this->assertSame('R-1', $line->retailerId);
        $this->assertSame(3, $line->quantity);
        $this->assertNull($line->price);
    }

    public function testWithPriceCarriesTheFaceValue(): void
    {
        $line = OrderLineDto::withPrice('  R-1  ', 2, 50.0);

        $this->assertSame('R-1', $line->retailerId);
        $this->assertSame(2, $line->quantity);
        $this->assertNotNull($line->price);
        $this->assertEqualsWithDelta(50.0, $line->price, PHP_FLOAT_EPSILON);
    }

    public function testItRejectsABlankRetailerId(): void
    {
        $this->expectException(InvalidOrderRequestException::class);
        $this->expectExceptionMessage('retailer id');

        OrderLineDto::of('   ', 1);
    }

    public function testItRejectsANonPositiveQuantity(): void
    {
        $this->expectException(InvalidOrderRequestException::class);
        $this->expectExceptionMessage('R-1');

        OrderLineDto::of('R-1', 0);
    }

    public function testWithPriceRejectsANonPositivePrice(): void
    {
        $this->expectException(InvalidOrderRequestException::class);
        $this->expectExceptionMessage('price greater than 0');

        OrderLineDto::withPrice('R-1', 1, 0.0);
    }

    public function testTheRejectionIsCatchableThroughTheLibraryMarker(): void
    {
        $this->expectException(AmilonExceptionInterface::class);

        OrderLineDto::of('R-1', -2);
    }
}
