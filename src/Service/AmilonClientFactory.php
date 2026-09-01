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

use Amilon\Api\AmilonApiInterface;
use Amilon\Api\ApiVersion;
use Amilon\Api\V1\Catalog\ProductApi;
use Amilon\Api\V1\Catalog\ProductMapper;
use Amilon\Api\V1\Catalog\RetailerApi;
use Amilon\Api\V1\Catalog\RetailerMapper;
use Amilon\Api\V1\Contract\ContractApi;
use Amilon\Api\V1\Contract\ContractMapper;
use Amilon\Api\V1\Order\OrderApi;
use Amilon\Api\V1\Order\OrderMapper;
use Amilon\Api\V1\Order\OrderRequestMapper;
use Amilon\Api\V1\V1Api;
use Amilon\Auth\TokenProvider;
use Amilon\Configuration\Configuration;
use Amilon\Dto\CredentialDto;
use Amilon\Exception\InvalidConfigurationException;
use Amilon\Http\AmilonHttpExecutor;
use Amilon\Http\AmilonResponseParser;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierService;

/**
 * Builds a fully wired {@see AmilonClient} from a set of credentials.
 *
 * This is the single supported way to obtain a client. It:
 *
 *  1. turns the caller's {@see CredentialDto} into a validated
 *     {@see Configuration} (blank values, endpoint URLs and the contract UUID are
 *     checked here — a bad DTO throws {@see InvalidConfigurationException});
 *  2. picks the API implementation for {@see ApiVersion::latest()} and wires it
 *     to HTTP transports scoped to the SSO / Web API base URLs and primed with
 *     the JSON `Accept` header, so callers never assemble absolute URLs;
 *  3. tags the client with the {@see \Amilon\Enum\Environment} the DTO declared.
 *
 * The same factory serves production and staging: the environment is whatever the
 * DTO carries, there is no separate mode switch to keep in sync.
 *
 *     $client = AmilonClientFactory::create($stagingCredentials);
 *
 * The factory owns its transport: it always builds Symfony's best available HTTP
 * client via {@see HttpClient::create()}, with no injection seam. The wired-up
 * operations are covered by the integration suite against the Amilon sandbox, not
 * by transport mocking.
 *
 * The factory adds no caching layer: each {@see self::create()} call yields an
 * independent client with its own transports and its own in-memory token.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class AmilonClientFactory
{
    private function __construct()
    {
    }

    /**
     * @throws InvalidConfigurationException when the credentials are incomplete or malformed
     */
    public static function create(CredentialDto $credentialDto): AmilonClient
    {
        $configuration = Configuration::fromCredentialDto($credentialDto);

        return new AmilonClient(
            configuration: $configuration,
            environment: $credentialDto->environment,
            api: self::buildApi(ApiVersion::latest(), $configuration, HttpClient::create()),
        );
    }

    private static function buildApi(
        ApiVersion $apiVersion,
        Configuration $configuration,
        HttpClientInterface $httpClient,
    ): AmilonApiInterface {
        $types = new EffectivePrimitiveTypeIdentifierService();
        $responseParser = new AmilonResponseParser($types);

        return match ($apiVersion) {
            ApiVersion::V1 => self::buildV1Api($configuration, $httpClient, $types, $responseParser),
        };
    }

    private static function buildV1Api(
        Configuration $configuration,
        HttpClientInterface $httpClient,
        EffectivePrimitiveTypeIdentifierService $effectivePrimitiveTypeIdentifierService,
        AmilonResponseParser $amilonResponseParser,
    ): V1Api {
        $tokenProvider = new TokenProvider(
            self::scopedTo($httpClient, $configuration->authDomain),
            $configuration,
            $amilonResponseParser,
            $effectivePrimitiveTypeIdentifierService,
        );

        $executor = new AmilonHttpExecutor(
            self::scopedTo($httpClient, $configuration->webDomain),
            $tokenProvider,
            $amilonResponseParser,
        );

        return new V1Api(
            $tokenProvider,
            new ProductApi($executor, $configuration, new ProductMapper($effectivePrimitiveTypeIdentifierService)),
            new RetailerApi($executor, $configuration, new RetailerMapper($effectivePrimitiveTypeIdentifierService)),
            new OrderApi(
                $executor,
                $configuration,
                new OrderRequestMapper(),
                new OrderMapper($effectivePrimitiveTypeIdentifierService),
            ),
            new ContractApi($executor, $configuration, new ContractMapper($effectivePrimitiveTypeIdentifierService)),
        );
    }

    /**
     * @param non-empty-string $baseUri
     */
    private static function scopedTo(HttpClientInterface $httpClient, string $baseUri): HttpClientInterface
    {
        return $httpClient->withOptions([
            'base_uri' => $baseUri,
            'headers' => ['Accept' => 'application/json'],
        ]);
    }
}
