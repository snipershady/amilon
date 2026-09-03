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

use Amilon\Enum\CountryEnum;
use Amilon\Tests\Integration\AbstractIntegrationTestCase;

/**
 * Exercises {@see \Amilon\Service\AmilonClient::getRetailers()} against the real
 * Amilon STAGING catalogue.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class GetRetailersIntegrationTest extends AbstractIntegrationTestCase
{
    public function testItListsRetailersForItaly(): void
    {
        $retailers = $this->liveStagingClient()->getRetailers(CountryEnum::IT);

        $this->assertFalse($retailers->isEmpty(), 'the STAGING IT catalogue is expected to expose retailers');
        $this->assertCount($retailers->count(), $retailers->all());

        foreach ($retailers as $retailer) {
            $this->assertNotSame('', $retailer->retailerId);
            $this->assertNotSame('', $retailer->name);
            $this->assertGreaterThanOrEqual(0, $retailer->codeValidityMonths);
        }
    }
}
