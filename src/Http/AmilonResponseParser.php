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
use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierServiceInterface;

/**
 * Turns a {@see ResponseInterface} into the decoded array the mappers consume,
 * or an {@see ApiRequestException} when the call did not succeed.
 *
 * Every Amilon HTTP call — token acquisition included — funnels through here so
 * transport failure, non-2xx status and non-JSON bodies are reported the same
 * way. On a non-2xx status it also lifts a short human-readable reason out of the
 * error body (OAuth `error_description`, RFC 7807 `detail`/`title`, …) into the
 * exception message.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class AmilonResponseParser
{
    /**
     * @var list<string> error-body keys tried, in order, for a message on a non-2xx response
     */
    private const array ERROR_MESSAGE_KEYS = ['error_description', 'error', 'detail', 'title', 'Message', 'message'];

    public function __construct(
        private EffectivePrimitiveTypeIdentifierServiceInterface $types,
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
            throw ApiRequestException::httpError($method, $path, $statusCode, $this->describeError($payload));
        }

        return $payload;
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    private function describeError(array $payload): string
    {
        foreach (self::ERROR_MESSAGE_KEYS as $key) {
            $value = $this->types->getStringValueFromArray($key, $payload, trim: true);

            if ('' !== $value) {
                return $value;
            }
        }

        return '';
    }
}
