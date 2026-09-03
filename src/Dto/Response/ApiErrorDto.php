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

namespace Amilon\Dto\Response;

use Amilon\Enum\AmilonErrorCode;

/**
 * A flat projection of an Amilon Web API error body:
 * `{"ErrorCode": "0105", "Message": "The credit is not sufficient."}` and, for a
 * `400` on `CreateOrder` / `CreateOrderPostponed`, the extra
 * `"ModelErrors": [{"PropertyName": …, "Errors": [ … ]}]` validation block.
 *
 * Behaviour-free and version-shared; {@see \Amilon\Http\ApiErrorMapper} builds it
 * from the decoded body and {@see \Amilon\Exception\ApiRequestException} carries
 * it out to callers. `rawErrorCode` is whatever Amilon sent (kept verbatim
 * because the documented {@see AmilonErrorCode} set is not exhaustive);
 * {@see self::errorCode()} is the parsed case when it is one this client models.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class ApiErrorDto
{
    /**
     * @param list<string> $validationErrors one flattened "Property: message; message" line per `ModelErrors` entry
     */
    public function __construct(
        public ?string $rawErrorCode,
        public string $message,
        public array $validationErrors = [],
    ) {
    }

    /**
     * The parsed {@see AmilonErrorCode}, or `null` when Amilon sent no code or
     * one this client does not model.
     */
    public function errorCode(): ?AmilonErrorCode
    {
        if (null === $this->rawErrorCode) {
            return null;
        }

        return AmilonErrorCode::tryFrom($this->rawErrorCode);
    }

    public function hasValidationErrors(): bool
    {
        return [] !== $this->validationErrors;
    }
}
