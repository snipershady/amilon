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

use Amilon\Dto\Request\CreateOrderRequestDto;
use Amilon\Dto\Request\OrderLineDto;
use Amilon\Exception\InvalidOrderRequestException;
use Amilon\Tests\AbstractTestCase;

/**
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class CreateOrderRequestDtoTest extends AbstractTestCase
{
    public function testFromLinesTrimsTheIdAndKeepsEveryLine(): void
    {
        $request = CreateOrderRequestDto::fromLines('  ext-9  ', [
            OrderLineDto::withPrice('A', 1, 10.0),
            OrderLineDto::withPrice('B', 5, 25.0),
        ]);

        $this->assertSame('ext-9', $request->externalOrderId);
        $this->assertSame(['A', 'B'], array_map(
            static fn (OrderLineDto $orderLineDto): string => $orderLineDto->retailerId,
            $request->lines,
        ));
        $this->assertSame([1, 5], array_map(
            static fn (OrderLineDto $orderLineDto): int => $orderLineDto->quantity,
            $request->lines,
        ));
    }

    public function testFromLinesRejectsABlankExternalOrderId(): void
    {
        $this->expectException(InvalidOrderRequestException::class);
        $this->expectExceptionMessage('external order id');

        CreateOrderRequestDto::fromLines('   ', [OrderLineDto::withPrice('A', 1, 10.0)]);
    }

    public function testFromLinesRejectsAnEmptyLineSet(): void
    {
        $this->expectException(InvalidOrderRequestException::class);
        $this->expectExceptionMessage('at least one order line');

        CreateOrderRequestDto::fromLines('ext-1', []);
    }

    public function testSingleLineWithPriceIsAShortcutForOneMerchant(): void
    {
        $request = CreateOrderRequestDto::singleLineWithPrice('ext-1', '  R-1  ', 2, 50.0);

        $this->assertSame('ext-1', $request->externalOrderId);
        $this->assertCount(1, $request->lines);
        $this->assertSame('R-1', $request->lines[0]->retailerId);
        $this->assertSame(2, $request->lines[0]->quantity);
        $this->assertNotNull($request->lines[0]->price);
        $this->assertEqualsWithDelta(50.0, $request->lines[0]->price, PHP_FLOAT_EPSILON);
    }

    public function testSingleLinePropagatesLineValidation(): void
    {
        $this->expectException(InvalidOrderRequestException::class);

        CreateOrderRequestDto::singleLine('ext-1', '', 1);
    }
}
