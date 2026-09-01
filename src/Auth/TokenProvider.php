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

namespace Amilon\Auth;

use Amilon\Configuration\Configuration;
use Amilon\Dto\Response\AccessTokenDto;
use Amilon\Exception\ApiRequestException;
use Amilon\Exception\AuthenticationException;
use Amilon\Http\AmilonResponseParser;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierServiceInterface;

/**
 * Holds the OAuth access token for one {@see \Amilon\Service\AmilonClient} and
 * fetches a fresh one from the Amilon SSO endpoint whenever it is missing or
 * (near) expired.
 *
 * This is the library's only piece of mutable state and it is deliberately not
 * shared: the token lives in memory for the lifetime of the client that owns
 * this provider, with no external cache. {@see \Amilon\Http\AmilonHttpExecutor}
 * asks it for a bearer value on every resource call;
 * {@see \Amilon\Service\AmilonClient::getToken()} returns the token DTO directly.
 *
 * The injected HTTP client must be scoped to the SSO base URL
 * ({@see Configuration::$authDomain}); this class only appends `connect/token`.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class TokenProvider
{
    private ?AccessTokenDto $token = null;

    public function __construct(
        private readonly HttpClientInterface $ssoHttpClient,
        private readonly Configuration $configuration,
        private readonly AmilonResponseParser $responseParser,
        private readonly EffectivePrimitiveTypeIdentifierServiceInterface $types,
    ) {
    }

    /**
     * The current token, obtaining or renewing it if needed.
     *
     * @throws AuthenticationException
     */
    public function currentToken(): AccessTokenDto
    {
        if (!$this->token instanceof AccessTokenDto || $this->token->isExpired()) {
            $this->token = $this->requestToken();
        }

        return $this->token;
    }

    /**
     * Discard any cached token and fetch a new one unconditionally.
     *
     * @throws AuthenticationException
     */
    public function forceRefresh(): AccessTokenDto
    {
        $this->token = $this->requestToken();

        return $this->token;
    }

    /**
     * @throws AuthenticationException
     */
    private function requestToken(): AccessTokenDto
    {
        try {
            $response = $this->ssoHttpClient->request('POST', 'connect/token', [
                'body' => [
                    'grant_type' => 'password',
                    'client_id' => $this->configuration->clientId,
                    'client_secret' => $this->configuration->clientSecret,
                    'username' => $this->configuration->username,
                    'password' => $this->configuration->password,
                ],
            ]);

            $payload = $this->responseParser->toArray($response, 'POST', 'connect/token');
        } catch (TransportExceptionInterface $transportException) {
            throw AuthenticationException::fromRequestFailure(ApiRequestException::transportFailure('POST', 'connect/token', $transportException));
        } catch (ApiRequestException $apiRequestException) {
            throw AuthenticationException::fromRequestFailure($apiRequestException);
        }

        return AccessTokenDto::fromResponsePayload($payload, $this->types);
    }
}
