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

namespace Amilon\Tests\Unit\Enum;

use Amilon\Enum\OrderStatus;
use Amilon\Tests\AbstractTestCase;

/**
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class OrderStatusTest extends AbstractTestCase
{
    public function testItModelsTheTwoDocumentedStatuses(): void
    {
        $this->assertSame('Completed', OrderStatus::COMPLETED->value);
        $this->assertSame('Deleted', OrderStatus::DELETED->value);
        $this->assertCount(2, OrderStatus::cases());
    }

    public function testCompletedPredicates(): void
    {
        $this->assertTrue(OrderStatus::COMPLETED->isCompleted());
        $this->assertFalse(OrderStatus::COMPLETED->isCancelled());
        $this->assertSame('The order is completed.', OrderStatus::COMPLETED->description());
    }

    public function testDeletedPredicates(): void
    {
        $this->assertTrue(OrderStatus::DELETED->isCancelled());
        $this->assertFalse(OrderStatus::DELETED->isCompleted());
        $this->assertSame('The order is cancelled.', OrderStatus::DELETED->description());
    }

    public function testAnIntermediateStatusDoesNotResolve(): void
    {
        $this->assertNull(OrderStatus::tryFrom('Pending'));
    }
}
