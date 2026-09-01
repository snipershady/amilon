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

namespace Amilon\Tests\Integration\Api\V1;

use Amilon\Dto\Request\CreateOrderRequestDto;
use Amilon\Enum\CountryEnum;
use Amilon\Tests\Integration\AbstractIntegrationTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Exercises {@see \Amilon\Service\AmilonClient::makeOrder()} against the real
 * Amilon STAGING API.
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
    public function testItPlacesASingleProductOrderAndGetsAConfirmation(): void
    {
        $this->requireOrderPlacementEnabled();

        $client = $this->liveStagingClient();

        $products = $client->getProducts(CountryEnum::IT)->all();
        $this->assertArrayHasKey(0, $products, 'need at least one product to order');
        $productCode = $products[0]->productCode;

        $externalOrderId = 'amilon-lib-it-' . bin2hex(random_bytes(8));
        dump($externalOrderId);
        $confirmation = $client->makeOrder(
            CreateOrderRequestDto::singleLine($externalOrderId, $productCode, 1),
        );

        $this->assertSame($externalOrderId, $confirmation->externalOrderId);
        $this->assertNotSame('', $confirmation->orderStatus);
        $this->assertGreaterThanOrEqual(0.0, $confirmation->grossAmount);
    }
}
