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

/**
 * The result of {@see \Amilon\Service\AmilonClient::getContractInfo()}: the full
 * documented `contracts/{contractId}` shape — the contract's identity, validity
 * window, currency and balances.
 *
 * `currentAmount` is the balance orders draw down and `previousAmount` is what it
 * was before the last operation; `currencyIsoCode` is the ISO-4217 code every
 * denomination in an order must match. `startDate` / `endDate` / `lastUpdate` are
 * `null` when Amilon omits or sends an unparseable timestamp.
 *
 * A flat, version-shared projection built by
 * {@see \Amilon\Api\V2\Contract\ContractMapper}; it does not validate.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class ContractInfoDto
{
    public function __construct(
        public string $contractId,
        public string $contractName,
        public string $currencyIsoCode,
        public float $currentAmount,
        public float $previousAmount,
        public ?\DateTimeImmutable $startDate,
        public ?\DateTimeImmutable $endDate,
        public ?\DateTimeImmutable $lastUpdate,
    ) {
    }
}
