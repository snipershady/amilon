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

namespace Amilon\Api\V2\Contract;

use Amilon\Dto\Response\ContractInfoDto;
use Amilon\Support\DateParser;
use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierServiceInterface;

/**
 * Maps the V2 `contracts/{contractId}` response into a {@see ContractInfoDto} —
 * unchanged from V1.
 *
 * Scalars go through {@see EffectivePrimitiveTypeIdentifierServiceInterface}
 * (`CurrentAmount` may arrive as a string); `LastUpdate` goes through
 * {@see DateParser}.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class ContractMapper
{
    public function __construct(
        private EffectivePrimitiveTypeIdentifierServiceInterface $types,
    ) {
    }

    /**
     * @param array<array-key, mixed> $payload decoded contract response
     */
    public function map(array $payload): ContractInfoDto
    {
        return new ContractInfoDto(
            contractId: $this->types->getStringValueFromArray('ContractId', $payload, trim: true),
            currentAmount: $this->types->getFloatValueFromArray('CurrentAmount', $payload),
            lastUpdate: DateParser::nullable(
                $this->types->getStringValueFromArray('LastUpdate', $payload, trim: true),
            ),
        );
    }
}
