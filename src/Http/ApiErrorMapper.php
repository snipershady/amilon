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

use Amilon\Dto\Response\ApiErrorDto;
use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierServiceInterface;

/**
 * Maps a decoded Amilon error body into an {@see ApiErrorDto}.
 *
 * Handles the plain `{"ErrorCode": …, "Message": …}` shape every endpoint uses on
 * `400` / `403` / `404`, plus the OAuth (`error` / `error_description`) and RFC
 * 7807 (`detail` / `title`) shapes the SSO endpoint can answer with, and the
 * `ModelErrors` validation block `CreateOrder` adds on a `400`. Every read goes
 * through {@see EffectivePrimitiveTypeIdentifierServiceInterface} so a numeric
 * `ErrorCode` or a missing key still resolves to a definite value; the
 * `ModelErrors` list is walked structurally.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class ApiErrorMapper
{
    /**
     * @var list<string> error-body keys tried, in order, for a human-readable message
     */
    private const array MESSAGE_KEYS = ['Message', 'message', 'error_description', 'error', 'detail', 'title'];

    public function __construct(
        private EffectivePrimitiveTypeIdentifierServiceInterface $types,
    ) {
    }

    /**
     * @param array<array-key, mixed> $payload decoded error body
     */
    public function fromPayload(array $payload): ApiErrorDto
    {
        return new ApiErrorDto(
            rawErrorCode: $this->rawErrorCode($payload),
            message: $this->message($payload),
            validationErrors: $this->validationErrors($payload['ModelErrors'] ?? null),
        );
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    private function rawErrorCode(array $payload): ?string
    {
        // forceString: the codes are zero-padded ("0105"); without it a numeric
        // string would be promoted to an int and lose the padding.
        $rawErrorCode = $this->types->getStringValueFromArray('ErrorCode', $payload, trim: true, forceString: true);

        return '' === $rawErrorCode ? null : $rawErrorCode;
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    private function message(array $payload): string
    {
        foreach (self::MESSAGE_KEYS as $key) {
            $value = $this->types->getStringValueFromArray($key, $payload, trim: true);

            if ('' !== $value) {
                return $value;
            }
        }

        return '';
    }

    /**
     * @return list<string>
     */
    private function validationErrors(mixed $rawModelErrors): array
    {
        if (!is_array($rawModelErrors)) {
            return [];
        }

        $lines = [];

        foreach ($rawModelErrors as $rawModelError) {
            if (!is_array($rawModelError)) {
                continue;
            }

            $line = $this->validationLine($rawModelError);

            if ('' !== $line) {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    /**
     * @param array<array-key, mixed> $modelError one `{"PropertyName": …, "Errors": [ … ]}` entry
     */
    private function validationLine(array $modelError): string
    {
        $propertyName = $this->types->getStringValueFromArray('PropertyName', $modelError, trim: true);
        $messages = $this->validationMessages($modelError['Errors'] ?? null);

        if ([] === $messages) {
            return $propertyName;
        }

        $joined = implode('; ', $messages);

        return '' === $propertyName ? $joined : sprintf('%s: %s', $propertyName, $joined);
    }

    /**
     * @return list<string>
     */
    private function validationMessages(mixed $rawErrors): array
    {
        if (!is_array($rawErrors)) {
            return [];
        }

        $messages = [];

        foreach ($rawErrors as $rawError) {
            $message = $this->types->getStringValue($rawError, trim: true);

            if ('' !== $message) {
                $messages[] = $message;
            }
        }

        return $messages;
    }
}
