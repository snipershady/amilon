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
use Amilon\Enum\OrderStatus;
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
            'TotalRequestedCodes' => '3',
            'PurchaseOrder' => 'PO-42',
            'Vouchers' => [
                [
                    'ProductId' => 'PC-1',
                    'RetailerId' => 'R-1',
                    'RetailerName' => 'Amazon',
                    'RetailerCountry' => 'Italy',
                    'RetailerCountryISOAlpha3' => 'ITA',
                    'VoucherLink' => 'https://voucher.example/abc',
                    'ValidityStartDate' => '2026-03-15T00:00:00+00:00',
                    'ValidityEndDate' => '2027-03-15T00:00:00+00:00',
                    'CardCode' => 'CARD-9',
                    'Pin' => '1234',
                    'Name' => 'Ada',
                    'Surname' => 'Lovelace',
                    'Email' => 'ada@example.test',
                    'Dedication' => 'Happy birthday',
                    'OrderFrom' => 'ACME',
                    'OrderTo' => 'Ada',
                    'Amount' => '25.00',
                    'Deleted' => false,
                ],
            ],
        ]);

        $this->assertSame('ext-1', $confirmation->externalOrderId);
        $this->assertSame('Completed', $confirmation->orderStatus);
        $this->assertSame(OrderStatus::COMPLETED, $confirmation->status());
        $this->assertEqualsWithDelta(12.5, $confirmation->grossAmount, PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(11.0, $confirmation->netAmount, PHP_FLOAT_EPSILON);
        $this->assertSame(3, $confirmation->totalRequestedCodes);
        $this->assertSame('PO-42', $confirmation->purchaseOrder);
        $this->assertInstanceOf(\DateTimeImmutable::class, $confirmation->orderDate);
        $this->assertSame((new \DateTimeImmutable('2026-03-15T10:30:00+00:00'))->getTimestamp(), $confirmation->orderDate->getTimestamp());

        $this->assertCount(1, $confirmation->vouchers);
        $this->assertArrayHasKey(0, $confirmation->vouchers);
        $voucher = $confirmation->vouchers[0];
        $this->assertSame('PC-1', $voucher->productId);
        $this->assertSame('R-1', $voucher->retailerId);
        $this->assertSame('Amazon', $voucher->retailerName);
        $this->assertSame('ITA', $voucher->retailerCountryIsoAlpha3);
        $this->assertSame('https://voucher.example/abc', $voucher->voucherLink);
        $this->assertInstanceOf(\DateTimeImmutable::class, $voucher->validityEndDate);
        $this->assertSame('CARD-9', $voucher->cardCode);
        $this->assertSame('1234', $voucher->pin);
        $this->assertSame('Ada', $voucher->name);
        $this->assertSame('ada@example.test', $voucher->email);
        $this->assertEqualsWithDelta(25.0, $voucher->amount, PHP_FLOAT_EPSILON);
        $this->assertFalse($voucher->deleted);
    }

    public function testMissingKeysYieldEmptyScalarsAndNoVouchers(): void
    {
        $confirmation = $this->mapper->map([]);

        $this->assertSame('', $confirmation->externalOrderId);
        $this->assertSame('', $confirmation->orderStatus);
        $this->assertNull($confirmation->status());
        $this->assertNotInstanceOf(\DateTimeImmutable::class, $confirmation->orderDate);
        $this->assertEqualsWithDelta(0.0, $confirmation->grossAmount, PHP_FLOAT_EPSILON);
        $this->assertSame(0, $confirmation->totalRequestedCodes);
        $this->assertSame('', $confirmation->purchaseOrder);
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

    public function testACancelledVoucherIsFlaggedDeleted(): void
    {
        $confirmation = $this->mapper->map([
            'Vouchers' => [
                ['RetailerId' => 'R-1', 'Deleted' => true],
            ],
        ]);

        $this->assertArrayHasKey(0, $confirmation->vouchers);
        $this->assertTrue($confirmation->vouchers[0]->deleted);
    }

    public function testAnUnmodelledOrderStatusKeepsTheRawStringButHasNoParsedCase(): void
    {
        $confirmation = $this->mapper->map(['OrderStatus' => 'Pending']);

        $this->assertSame('Pending', $confirmation->orderStatus);
        $this->assertNull($confirmation->status());
    }
}
