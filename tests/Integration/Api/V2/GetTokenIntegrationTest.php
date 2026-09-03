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

/**
 * Exercises {@see \Amilon\Service\AmilonClient::getToken()} against the real
 * Amilon STAGING SSO endpoint.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class GetTokenIntegrationTest extends AbstractIntegrationTestCase
{
    public function testItObtainsAUsableAccessTokenFromTheSandbox(): void
    {
        $token = $this->liveStagingClient()->getToken();

        $this->assertNotSame('', $token->accessToken);
        $this->assertFalse($token->isExpired());
        $this->assertGreaterThan(time(), $token->expiresAt->getTimestamp());
        $this->assertStringEndsWith(' ' . $token->accessToken, $token->authorizationHeader());
    }

    public function testASecondCallReusesTheCachedToken(): void
    {
        $client = $this->liveStagingClient();

        $this->assertSame($client->getToken()->accessToken, $client->getToken()->accessToken);
    }
}
