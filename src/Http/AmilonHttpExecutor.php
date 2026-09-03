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

namespace Amilon\Http;

use Amilon\Auth\TokenProvider;
use Amilon\Exception\ApiRequestException;
use Amilon\Exception\AuthenticationException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Runs authenticated calls against the Amilon Web API: it attaches the bearer
 * token from {@see TokenProvider}, sends the request on the transport the factory
 * scoped to {@see \Amilon\Configuration\Configuration::$webDomain}, and hands the
 * response to {@see AmilonResponseParser}.
 *
 * The resource classes under {@see \Amilon\Api}\V* depend on this rather than on
 * a raw HTTP client, so token handling and response parsing live in one place and
 * every revision's endpoints share them. Paths are relative to the scoped base
 * URL (`contracts/{id}/…`), never absolute.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class AmilonHttpExecutor
{
    public function __construct(
        private HttpClientInterface $webHttpClient,
        private TokenProvider $tokenProvider,
        private AmilonResponseParser $responseParser,
    ) {
    }

    /**
     * @return array<array-key, mixed>
     *
     * @throws AuthenticationException when the bearer token cannot be obtained
     * @throws ApiRequestException     when the call itself fails
     */
    public function get(string $path): array
    {
        return $this->send('GET', $path);
    }

    /**
     * @param array<string, mixed> $body JSON-encoded and sent as the request body
     *
     * @return array<array-key, mixed>
     *
     * @throws AuthenticationException when the bearer token cannot be obtained
     * @throws ApiRequestException     when the call itself fails
     */
    public function post(string $path, array $body): array
    {
        return $this->send('POST', $path, ['json' => $body]);
    }

    /**
     * @param array<string, mixed> $options extra `symfony/http-client` request options
     *
     * @return array<array-key, mixed>
     *
     * @throws AuthenticationException
     * @throws ApiRequestException
     */
    private function send(string $method, string $path, array $options = []): array
    {
        $options['headers'] = ['Authorization' => $this->tokenProvider->currentToken()->authorizationHeader()];

        try {
            $response = $this->webHttpClient->request($method, $path, $options);
        } catch (TransportExceptionInterface $transportException) {
            throw ApiRequestException::transportFailure($method, $path, $transportException);
        }

        return $this->responseParser->toArray($response, $method, $path);
    }
}
