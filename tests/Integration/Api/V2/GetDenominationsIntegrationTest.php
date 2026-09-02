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

use Amilon\Dto\Response\MerchantContentDto;
use Amilon\Enum\CountryEnum;
use Amilon\Tests\Integration\AbstractIntegrationTestCase;

/**
 * Exercises {@see \Amilon\Service\AmilonClient::getDenominations()} and
 * {@see \Amilon\Service\AmilonClient::getDenominationsComplete()} against the real
 * Amilon STAGING catalogue.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class GetDenominationsIntegrationTest extends AbstractIntegrationTestCase
{
    public function testItListsDenominationsForItaly(): void
    {
        $merchants = $this->liveStagingClient()->getDenominations(CountryEnum::IT);

        $this->assertFalse($merchants->isEmpty(), 'the STAGING IT catalogue is expected to expose denominations');
        $this->assertCount($merchants->count(), $merchants->all());

        foreach ($merchants as $merchant) {
            $this->assertNotSame('', $merchant->code);
            $this->assertNotSame('', $merchant->name);
            $this->assertNotInstanceOf(MerchantContentDto::class, $merchant->extendedContent);

            foreach ($merchant->denominations as $denomination) {
                $this->assertNotSame('', $denomination->code);
                $this->assertTrue(
                    $denomination->isFixed() || $denomination->isVariable() || $denomination->hasContractPriceOverride()
                    || [] === $denomination->prices,
                    'a denomination should fall into one of the migration-guide shapes',
                );
            }
        }
    }

    public function testCompleteAddsTheExtendedContentBlock(): void
    {
        $merchants = $this->liveStagingClient()->getDenominationsComplete(CountryEnum::IT);

        $this->assertFalse($merchants->isEmpty());

        foreach ($merchants as $merchant) {
            $this->assertInstanceOf(MerchantContentDto::class, $merchant->extendedContent, 'complete mode should carry the merchant content block');
        }
    }
}
