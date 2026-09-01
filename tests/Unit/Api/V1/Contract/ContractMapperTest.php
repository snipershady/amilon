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

namespace Amilon\Tests\Unit\Api\V1\Contract;

use Amilon\Api\V1\Contract\ContractMapper;
use Amilon\Tests\AbstractTestCase;
use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierService;

/**
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class ContractMapperTest extends AbstractTestCase
{
    private ContractMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new ContractMapper(new EffectivePrimitiveTypeIdentifierService());
    }

    public function testItMapsTheContractInfoCoercingAStringAmount(): void
    {
        $info = $this->mapper->map([
            'ContractId' => '7fb1c5d3-423e-4b0c-b8da-a3ed94ae6392',
            'CurrentAmount' => '1234.56',
            'LastUpdate' => '2026-03-15T10:30:00+00:00',
        ]);

        $this->assertSame('7fb1c5d3-423e-4b0c-b8da-a3ed94ae6392', $info->contractId);
        $this->assertEqualsWithDelta(1234.56, $info->currentAmount, PHP_FLOAT_EPSILON);
        $this->assertInstanceOf(\DateTimeImmutable::class, $info->lastUpdate);
        $this->assertSame((new \DateTimeImmutable('2026-03-15T10:30:00+00:00'))->getTimestamp(), $info->lastUpdate->getTimestamp());
    }

    public function testMissingKeysYieldEmptyScalarsAndANullDate(): void
    {
        $info = $this->mapper->map([]);

        $this->assertSame('', $info->contractId);
        $this->assertEqualsWithDelta(0.0, $info->currentAmount, PHP_FLOAT_EPSILON);
        $this->assertNotInstanceOf(\DateTimeImmutable::class, $info->lastUpdate);
    }
}
