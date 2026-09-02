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

use Amilon\Api\V2\Order\OrderMapper;
use Amilon\Tests\AbstractTestCase;
use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierService;

/**
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class OrderMapperTest extends AbstractTestCase
{
    private OrderMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new OrderMapper(new EffectivePrimitiveTypeIdentifierService());
    }

    public function testItMapsAFullConfirmation(): void
    {
        $confirmation = $this->mapper->map([
            'ExternalOrderId' => 'ext-1',
            'OrderDate' => '2026-03-15T10:30:00+00:00',
            'OrderStatus' => 'Completed',
            'GrossAmount' => '12.50',
            'NetAmount' => 11,
            'Vouchers' => [
                [
                    'ProductId' => 'PC-1',
                    'RetailerId' => 'R-1',
                    'VoucherLink' => 'https://voucher.example/abc',
                    'ValidityStartDate' => '2026-03-15T00:00:00+00:00',
                    'ValidityEndDate' => '2027-03-15T00:00:00+00:00',
                ],
            ],
        ]);

        $this->assertSame('ext-1', $confirmation->externalOrderId);
        $this->assertSame('Completed', $confirmation->orderStatus);
        $this->assertEqualsWithDelta(12.5, $confirmation->grossAmount, PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(11.0, $confirmation->netAmount, PHP_FLOAT_EPSILON);
        $this->assertInstanceOf(\DateTimeImmutable::class, $confirmation->orderDate);
        $this->assertSame((new \DateTimeImmutable('2026-03-15T10:30:00+00:00'))->getTimestamp(), $confirmation->orderDate->getTimestamp());

        $this->assertCount(1, $confirmation->vouchers);
        $this->assertArrayHasKey(0, $confirmation->vouchers);
        $voucher = $confirmation->vouchers[0];
        $this->assertSame('PC-1', $voucher->productId);
        $this->assertSame('R-1', $voucher->retailerId);
        $this->assertSame('https://voucher.example/abc', $voucher->voucherLink);
        $this->assertInstanceOf(\DateTimeImmutable::class, $voucher->validityEndDate);
    }

    public function testMissingKeysYieldEmptyScalarsAndNoVouchers(): void
    {
        $confirmation = $this->mapper->map([]);

        $this->assertSame('', $confirmation->externalOrderId);
        $this->assertSame('', $confirmation->orderStatus);
        $this->assertNotInstanceOf(\DateTimeImmutable::class, $confirmation->orderDate);
        $this->assertEqualsWithDelta(0.0, $confirmation->grossAmount, PHP_FLOAT_EPSILON);
        $this->assertSame([], $confirmation->vouchers);
    }

    public function testNonObjectVoucherRowsAreSkipped(): void
    {
        $confirmation = $this->mapper->map([
            'Vouchers' => [
                ['ProductId' => 'A'],
                'nope',
                42,
                ['ProductId' => 'B'],
            ],
        ]);

        $this->assertCount(2, $confirmation->vouchers);
    }

    public function testAnUnparseableDateBecomesNull(): void
    {
        $confirmation = $this->mapper->map([
            'ExternalOrderId' => 'ext-1',
            'OrderDate' => 'not-a-date',
        ]);

        $this->assertNotInstanceOf(\DateTimeImmutable::class, $confirmation->orderDate);
    }

    public function testVouchersThatAreNotAListYieldAnEmptySet(): void
    {
        $confirmation = $this->mapper->map(['Vouchers' => 'unexpected']);

        $this->assertSame([], $confirmation->vouchers);
    }
}
