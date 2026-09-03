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

use Amilon\Dto\Request\CreateOrderRequestDto;
use Amilon\Enum\CountryEnum;
use Amilon\Service\AmilonClient;
use Amilon\Tests\Integration\AbstractIntegrationTestCase;
use PHPUnit\Framework\Attributes\Group;
use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierService;

/**
 * Exercises {@see AmilonClient::makeOrderPostponed()} — the V2
 * `orders/createpostponed/{contractId}` endpoint — against the real Amilon
 * STAGING API.
 *
 * This places an actual sandbox order **and** the postponed route is not
 * exercised by CI, so on top of the usual `AMILON_*` + `AMILON_RUN_ORDER_TESTS=1`
 * it needs its own opt-in flag, `AMILON_RUN_POSTPONED_ORDER_TESTS=1`:
 *
 *     AMILON_RUN_ORDER_TESTS=1 AMILON_RUN_POSTPONED_ORDER_TESTS=1 \
 *         vendor/bin/phpunit --testsuite integration --group opt-in
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
#[Group('opt-in')]
final class MakeOrderPostponedIntegrationTest extends AbstractIntegrationTestCase
{
    public function testItRegistersADeferredOrderAndEchoesTheExternalId(): void
    {
        $client = $this->postponedOrderClient();

        [$merchant, $denomination] = $this->firstOrderableDenomination($client->getDenominations(CountryEnum::IT));

        $externalOrderId = 'amilon-lib-it-postponed-' . bin2hex(random_bytes(8));
        $confirmation = $client->makeOrderPostponed(
            CreateOrderRequestDto::singleLineWithPrice(
                $externalOrderId,
                $merchant->code,
                1,
                $this->pickOrderablePrice($denomination),
            ),
            new \DateTimeImmutable('+7 days'),
        );

        $this->assertSame($externalOrderId, $confirmation->externalOrderId);
        $this->assertNotSame('', $confirmation->orderStatus);
        $this->assertGreaterThanOrEqual(0.0, $confirmation->grossAmount);
    }

    public function testTheDeferredOrderCanBeReadBack(): void
    {
        $client = $this->postponedOrderClient();

        [$merchant, $denomination] = $this->firstOrderableDenomination($client->getDenominations(CountryEnum::IT));

        $externalOrderId = 'amilon-lib-it-postponed-' . bin2hex(random_bytes(8));
        $client->makeOrderPostponed(
            CreateOrderRequestDto::singleLineWithPrice(
                $externalOrderId,
                $merchant->code,
                1,
                $this->pickOrderablePrice($denomination),
            ),
            new \DateTimeImmutable('+7 days'),
        );

        $readBack = $client->getOrderInfo($externalOrderId);

        $this->assertSame($externalOrderId, $readBack->externalOrderId);
        $this->assertNotSame('', $readBack->orderStatus);
    }

    private function postponedOrderClient(): AmilonClient
    {
        $this->requireOrderPlacementEnabled();

        $enabled = (new EffectivePrimitiveTypeIdentifierService())
            ->getBoolValueFromArray('AMILON_RUN_POSTPONED_ORDER_TESTS', $_SERVER + $_ENV, trim: true);

        if (!$enabled) {
            self::markTestSkipped(
                'Set AMILON_RUN_POSTPONED_ORDER_TESTS=1 to run the postponed-order tests against the sandbox '
                . '(the createpostponed route/body still needs confirming against a live contract).',
            );
        }

        return $this->liveStagingClient();
    }
}
