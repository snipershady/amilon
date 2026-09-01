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

use Amilon\Dto\CredentialDto;
use Amilon\Enum\Environment;
use Amilon\Service\AmilonClientFactory;
use Amilon\Tests\AbstractTestCase;
use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierService;

/**
 * Exercises {@see \Amilon\Service\AmilonClient::getToken()} against the real
 * Amilon STAGING SSO endpoint.
 *
 * Skips itself while the `AMILON_*` variables are still the committed
 * placeholders or unset, so `composer test` stays green with no secrets around.
 * CI runs this suite with `--fail-on-skipped` once the sandbox credentials are
 * configured.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class GetTokenIntegrationTest extends AbstractTestCase
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

    public function testItObtainsAUsableAccessTokenFromTheSandbox(): void
    {
        $client = AmilonClientFactory::create($this->liveStagingCredentials());

        $token = $client->getToken();

        $this->assertNotSame('', $token->accessToken);
        $this->assertFalse($token->isExpired());
        $this->assertGreaterThan(time(), $token->expiresAt->getTimestamp());
        $this->assertStringEndsWith(' ' . $token->accessToken, $token->authorizationHeader());
    }

    public function testASecondCallReusesTheCachedToken(): void
    {
        $client = AmilonClientFactory::create($this->liveStagingCredentials());

        $this->assertSame($client->getToken()->accessToken, $client->getToken()->accessToken);
    }

    private function liveStagingCredentials(): CredentialDto
    {
        return new CredentialDto(
            username: $this->liveValue('AMILON_USERNAME', 'username'),
            password: $this->liveValue('AMILON_PASSWORD', 'password'),
            clientId: $this->liveValue('AMILON_CLIENT_ID', 'clientId'),
            clientSecret: $this->liveValue('AMILON_CLIENT_SECRET', 'clientSecret'),
            authDomain: $this->liveValue('AMILON_AUTH_DOMAIN', 'authDomain'),
            webDomain: $this->liveValue('AMILON_WEB_DOMAIN', 'webDomain'),
            contractId: $this->liveValue('AMILON_CONTRACT_ID', 'contractId'),
            environment: Environment::STAGING,
        );
    }

    private function liveValue(string $envKey, string $argument): string
    {
        $value = (new EffectivePrimitiveTypeIdentifierService())
            ->getStringValueFromArray($envKey, $_SERVER + $_ENV, trim: true);

        if ('' === $value || ($value === (self::PLACEHOLDERS[$argument] ?? null))) {
            self::markTestSkipped('Set real AMILON_* credentials in .env.local to run the integration suite.');
        }

        return $value;
    }
}
