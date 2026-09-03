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

namespace Amilon\Tests\Unit\Api\V2\Order;

use Amilon\Api\V2\Order\OrderRequestMapper;
use Amilon\Dto\Request\CreateOrderRequestDto;
use Amilon\Dto\Request\OrderLineDto;
use Amilon\Exception\InvalidOrderRequestException;
use Amilon\Tests\AbstractTestCase;

/**
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class OrderRequestMapperTest extends AbstractTestCase
{
    private OrderRequestMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new OrderRequestMapper();
    }

    public function testItBuildsTheV2PayloadForASingleLineWithRetailerIdAndPrice(): void
    {
        $payload = $this->mapper->toPayload(
            CreateOrderRequestDto::singleLineWithPrice('ext-1', 'R-1', 2, 50.0),
        );

        $this->assertSame([
            'ExternalOrderId' => 'ext-1',
            'OrderRows' => [
                ['RetailerId' => 'R-1', 'Quantity' => 2, 'Price' => 50.0],
            ],
        ], $payload);
    }

    public function testItKeepsOneOrderRowPerLineInOrder(): void
    {
        $payload = $this->mapper->toPayload(CreateOrderRequestDto::fromLines('ext-2', [
            OrderLineDto::withPrice('A', 1, 10.0),
            OrderLineDto::withPrice('B', 5, 25.0),
        ]));

        $this->assertSame([
            ['RetailerId' => 'A', 'Quantity' => 1, 'Price' => 10.0],
            ['RetailerId' => 'B', 'Quantity' => 5, 'Price' => 25.0],
        ], $payload['OrderRows']);
    }

    public function testItRejectsALineWithoutAPriceBeforeAnyHttpCall(): void
    {
        $this->expectException(InvalidOrderRequestException::class);
        $this->expectExceptionMessage('needs a price');

        $this->mapper->toPayload(CreateOrderRequestDto::singleLine('ext-3', 'R-1', 1));
    }
}
