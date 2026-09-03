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
use Amilon\Dto\Response\DenominationDto;
use Amilon\Dto\Response\MerchantDenominationsDto;
use Amilon\Enum\CountryEnum;
use Amilon\Tests\Integration\AbstractIntegrationTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Exercises {@see \Amilon\Service\AmilonClient::makeOrder()} against the real
 * Amilon STAGING API with a V2 retailer-id + price order row.
 *
 * This places an actual sandbox order, so it is in the `opt-in` group (excluded
 * from the CI integration run) and stays skipped unless `AMILON_RUN_ORDER_TESTS=1`
 * is set on top of the usual `AMILON_*` credentials:
 *
 *     AMILON_RUN_ORDER_TESTS=1 vendor/bin/phpunit --testsuite integration --group opt-in
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
#[Group('opt-in')]
final class MakeOrderIntegrationTest extends AbstractIntegrationTestCase
{
    public function testItPlacesASingleMerchantOrderAndGetsAConfirmation(): void
    {
        $this->requireOrderPlacementEnabled();

        $client = $this->liveStagingClient();

        [$merchant, $denomination] = $this->firstOrderableDenomination($client->getDenominations(CountryEnum::IT)->all());

        $externalOrderId = 'amilon-lib-it-' . bin2hex(random_bytes(8));
        // dump($externalOrderId);
        $confirmation = $client->makeOrder(CreateOrderRequestDto::singleLineWithPrice(
            $externalOrderId,
            $merchant->code,
            1,
            $this->pickPrice($denomination),
        ));

        $this->assertSame($externalOrderId, $confirmation->externalOrderId);
        $this->assertNotSame('', $confirmation->orderStatus);
        $this->assertGreaterThanOrEqual(0.0, $confirmation->grossAmount);
    }

    /**
     * @param list<MerchantDenominationsDto> $merchants
     *
     * @return array{MerchantDenominationsDto, DenominationDto}
     */
    private function firstOrderableDenomination(array $merchants): array
    {
        foreach ($merchants as $merchant) {
            foreach ($merchant->denominations as $denomination) {
                if ([] !== $denomination->prices || $denomination->isVariable()) {
                    return [$merchant, $denomination];
                }
            }
        }

        self::markTestSkipped('the STAGING IT catalogue exposed no orderable denomination');
    }

    private function pickPrice(DenominationDto $denominationDto): float
    {
        if ([] !== $denominationDto->prices) {
            return $denominationDto->prices[0]->price;
        }

        return $denominationDto->rangeMin ?? 5.0;
    }
}
