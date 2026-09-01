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

namespace Amilon\Tests\Unit\Api\V1\Order;

use Amilon\Api\V1\Order\OrderRequestMapper;
use Amilon\Dto\Request\CreateOrderRequestDto;
use Amilon\Dto\Request\OrderLineDto;
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

    public function testItBuildsTheV1PayloadForASingleLine(): void
    {
        $payload = $this->mapper->toPayload(
            CreateOrderRequestDto::singleLine('ext-1', 'PC-1', 2),
        );

        $this->assertSame([
            'ExternalOrderId' => 'ext-1',
            'OrderRows' => [
                ['ProductId' => 'PC-1', 'Quantity' => 2],
            ],
        ], $payload);
    }

    public function testItKeepsOneOrderRowPerLineInOrder(): void
    {
        $payload = $this->mapper->toPayload(CreateOrderRequestDto::fromLines('ext-2', [
            OrderLineDto::of('A', 1),
            OrderLineDto::of('B', 5),
        ]));

        $this->assertSame([
            ['ProductId' => 'A', 'Quantity' => 1],
            ['ProductId' => 'B', 'Quantity' => 5],
        ], $payload['OrderRows']);
    }
}
