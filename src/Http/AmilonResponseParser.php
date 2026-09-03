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

use Amilon\Exception\ApiRequestException;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Turns a {@see ResponseInterface} into the decoded array the mappers consume,
 * or an {@see ApiRequestException} when the call did not succeed.
 *
 * Every Amilon HTTP call — token acquisition included — funnels through here so
 * transport failure, non-2xx status and non-JSON bodies are reported the same
 * way. On a non-2xx status it runs the error body through {@see ApiErrorMapper}
 * so the resulting {@see ApiRequestException} carries the parsed
 * {@see \Amilon\Enum\AmilonErrorCode}, the human-readable message and any
 * `ModelErrors` validation lines.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class AmilonResponseParser
{
    public function __construct(
        private ApiErrorMapper $apiErrorMapper,
    ) {
    }

    /**
     * @return array<array-key, mixed>
     *
     * @throws ApiRequestException
     */
    public function toArray(ResponseInterface $response, string $method, string $path): array
    {
        try {
            $statusCode = $response->getStatusCode();
            $payload = $response->toArray(throw: false);
        } catch (DecodingExceptionInterface $decodingException) {
            throw ApiRequestException::malformedBody($method, $path, $decodingException);
        } catch (TransportExceptionInterface $transportException) {
            throw ApiRequestException::transportFailure($method, $path, $transportException);
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            throw ApiRequestException::httpError($method, $path, $statusCode, $this->apiErrorMapper->fromPayload($payload));
        }

        return $payload;
    }
}
