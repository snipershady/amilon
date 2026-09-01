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
    public function testItBuildsATrimmedLine(): void
    {
        $line = OrderLineDto::of('  PC-1  ', 3);

        $this->assertSame('PC-1', $line->productCode);
        $this->assertSame(3, $line->quantity);
    }

    public function testItRejectsABlankProductCode(): void
    {
        $this->expectException(InvalidOrderRequestException::class);
        $this->expectExceptionMessage('product code');

        OrderLineDto::of('   ', 1);
    }

    public function testItRejectsANonPositiveQuantity(): void
    {
        $this->expectException(InvalidOrderRequestException::class);
        $this->expectExceptionMessage('PC-1');

        OrderLineDto::of('PC-1', 0);
    }

    public function testTheRejectionIsCatchableThroughTheLibraryMarker(): void
    {
        $this->expectException(AmilonExceptionInterface::class);

        OrderLineDto::of('PC-1', -2);
    }
}
