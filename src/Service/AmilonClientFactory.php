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

namespace Amilon\Service;

use Amilon\Configuration\Configuration;
use Amilon\Dto\CredentialDto;
use Amilon\Exception\InvalidConfigurationException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Builds a fully wired {@see AmilonClient} from a set of credentials.
 *
 * This is the single supported way to obtain a client. It:
 *
 *  1. turns the caller's {@see CredentialDto} into a validated
 *     {@see Configuration} (blank values, endpoint URLs and the contract UUID are
 *     checked here — a bad DTO throws {@see InvalidConfigurationException});
 *  2. derives a {@see HttpClientInterface} scoped to the Web API base URL and
 *     primed with the JSON `Accept` header, so callers never assemble absolute
 *     URLs or repeat transport options;
 *  3. tags the client with the {@see \Amilon\Enum\Environment} the DTO declared.
 *
 * The same factory serves production and staging: the environment is whatever the
 * DTO carries, there is no separate mode switch to keep in sync.
 *
 *     $factory = new AmilonClientFactory();
 *     $client  = $factory->create($stagingCredentials);
 *
 * Pass a base transport to the constructor to decorate it (retry, logging,
 * profiling) or to substitute a `MockHttpClient` in tests; the per-client scoping
 * from step 2 is layered on top of whatever is supplied. When omitted, Symfony's
 * best available transport is used.
 *
 * The factory adds no caching layer: each {@see self::create()} call yields an
 * independent client with its own transport.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class AmilonClientFactory
{
    private static HttpClientInterface $httpClient;

    private function __construct()
    {
    }

    /**
     * @throws InvalidConfigurationException when the credentials are incomplete or malformed
     */
    public static function create(CredentialDto $credentialDto): AmilonClient
    {
        self::$httpClient = HttpClient::create();
        $configuration = Configuration::fromCredentialDto($credentialDto);

        $scopedHttpClient = self::$httpClient->withOptions([
            'base_uri' => $configuration->webDomain,
            'headers' => ['Accept' => 'application/json'],
        ]);

        return new AmilonClient(
            configuration: $configuration,
            httpClient: $scopedHttpClient,
            environment: $credentialDto->environment,
        );
    }
}
