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

namespace Amilon\Tests\Integration\Api\V2;

use Amilon\Tests\Integration\AbstractIntegrationTestCase;
use PHPUnit\Framework\Attributes\Group;
use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierService;

/**
 * Exercises {@see \Amilon\Service\AmilonClient::getOrderInfo()} against the real
 * Amilon STAGING API.
 *
 * Read-only, but it needs an order that already exists, so it is in the `opt-in`
 * group (excluded from the CI integration run). Point it at an order with
 * `AMILON_KNOWN_ORDER_ID=<externalOrderId>` — create a throwaway one via
 * {@see MakeOrderIntegrationTest}:
 *
 *     AMILON_KNOWN_ORDER_ID=… vendor/bin/phpunit --testsuite integration --group opt-in
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
#[Group('opt-in')]
final class GetOrderInfoIntegrationTest extends AbstractIntegrationTestCase
{
    public function testItReadsBackAKnownOrder(): void
    {
        $externalOrderId = (new EffectivePrimitiveTypeIdentifierService())
            ->getStringValueFromArray('AMILON_KNOWN_ORDER_ID', $_SERVER + $_ENV, trim: true);

        if ('' === $externalOrderId) {
            self::markTestSkipped('Set AMILON_KNOWN_ORDER_ID to an existing sandbox order to run this test.');
        }

        $order = $this->liveStagingClient()->getOrderInfo($externalOrderId);

        $this->assertSame($externalOrderId, $order->externalOrderId);
        $this->assertNotSame('', $order->orderStatus);
    }
}
