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

namespace Amilon\Tests\Integration;

use Amilon\Dto\CredentialDto;
use Amilon\Dto\Response\DenominationDto;
use Amilon\Dto\Response\MerchantDenominationCollectionDto;
use Amilon\Dto\Response\MerchantDenominationsDto;
use Amilon\Enum\Environment;
use Amilon\Service\AmilonClient;
use Amilon\Service\AmilonClientFactory;
use Amilon\Tests\AbstractTestCase;
use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierService;

/**
 * Base for tests that hit the real Amilon STAGING API.
 *
 * {@see self::liveStagingClient()} builds a client from the `AMILON_*` variables
 * (`.env.local`, or the CI environment) and, while any of them is still the
 * committed placeholder or unset, marks the calling test skipped — so
 * `composer test` stays green with no secrets around while CI can run the suite
 * with `--fail-on-skipped` once they are configured.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
abstract class AbstractIntegrationTestCase extends AbstractTestCase
{
    /**
     * @var array<string, string> constructor-argument name => committed placeholder in .env
     */
    private const array PLACEHOLDERS = [
        'username' => 'your-username',
        'password' => 'your-password',
        'clientId' => 'your-client-id',
        'clientSecret' => 'your-client-secret',
        'contractId' => '00000000-0000-0000-0000-000000000000',
    ];

    final protected function liveStagingClient(): AmilonClient
    {
        return AmilonClientFactory::create($this->liveStagingCredentials());
    }

    /**
     * Guard for the test that places a real sandbox order: it stays skipped
     * unless `AMILON_RUN_ORDER_TESTS` is explicitly truthy, so a plain
     * `composer test` never fires an order.
     */
    final protected function requireOrderPlacementEnabled(): void
    {
        $enabled = (new EffectivePrimitiveTypeIdentifierService())
            ->getBoolValueFromArray('AMILON_RUN_ORDER_TESTS', $_SERVER + $_ENV, trim: true);

        if (!$enabled) {
            self::markTestSkipped('Set AMILON_RUN_ORDER_TESTS=1 to run tests that place real sandbox orders.');
        }
    }

    final protected function liveStagingCredentials(): CredentialDto
    {
        return new CredentialDto(
            username: $this->liveValue('AMILON_USERNAME', 'username'),
            password: $this->liveValue('AMILON_PASSWORD', 'password'),
            clientId: $this->liveValue('AMILON_CLIENT_ID', 'clientId'),
            clientSecret: $this->liveValue('AMILON_CLIENT_SECRET', 'clientSecret'),
            authDomain: $this->liveValue('AMILON_AUTH_DOMAIN', 'authDomain'),
            webDomain: $this->liveValue('AMILON_WEB_DOMAIN', 'webDomain'),
            webDomainV2: $this->liveValue('AMILON_WEB_DOMAIN_V2', 'webDomainV2'),
            contractId: $this->liveValue('AMILON_CONTRACT_ID', 'contractId'),
            environment: Environment::STAGING,
        );
    }

    /**
     * The first denomination in the live catalogue that can actually be ordered
     * — one with an explicit price or an open range — paired with its merchant.
     * Skips the calling test when the catalogue exposes none.
     *
     * @return array{MerchantDenominationsDto, DenominationDto}
     */
    final protected function firstOrderableDenomination(MerchantDenominationCollectionDto $merchantDenominationCollectionDto): array
    {
        foreach ($merchantDenominationCollectionDto as $merchant) {
            foreach ($merchant->denominations as $denomination) {
                if ([] !== $denomination->prices || $denomination->isVariable()) {
                    return [$merchant, $denomination];
                }
            }
        }

        self::markTestSkipped('the STAGING catalogue exposed no orderable denomination');
    }

    /**
     * A face value that {@see self::firstOrderableDenomination()} accepts: the
     * first listed price, or the low end of an open range.
     */
    final protected function pickOrderablePrice(DenominationDto $denominationDto): float
    {
        if ([] !== $denominationDto->prices) {
            return $denominationDto->prices[0]->price;
        }

        return $denominationDto->rangeMin ?? 5.0;
    }

    private function liveValue(string $envKey, string $argument): string
    {
        $value = (new EffectivePrimitiveTypeIdentifierService())
            ->getStringValueFromArray($envKey, $_SERVER + $_ENV, trim: true);

        if ('' === $value || $value === (self::PLACEHOLDERS[$argument] ?? null)) {
            self::markTestSkipped('Set real AMILON_* credentials in .env.local to run the integration suite.');
        }

        return $value;
    }
}
