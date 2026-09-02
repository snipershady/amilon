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
 * One merchant (retailer) and the denominations of its gift card a contract can
 * sell, as returned by the V2 `denominations` / `denominations/complete`
 * endpoints.
 *
 * This is the V2 replacement for V1's per-value product: `code` is the merchant
 * id (V1's `MerchantCode` / `RetailerId`) and every orderable face value hangs
 * off it as a {@see DenominationDto} in {@see self::$denominations}. To place an
 * order pass this `code` plus the chosen price as an
 * {@see \Amilon\Dto\Request\OrderLineDto}.
 *
 * `extendedContent` is `null` for {@see \Amilon\Service\AmilonClient::getDenominations()}
 * and carries a {@see MerchantContentDto} for
 * {@see \Amilon\Service\AmilonClient::getDenominationsComplete()}.
 *
 * A flat, version-shared projection built by
 * {@see \Amilon\Api\V2\Catalog\DenominationMapper}; it does not validate.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class MerchantDenominationsDto
{
    /**
     * @param list<DenominationDto> $denominations
     */
    public function __construct(
        public string $code,
        public string $country,
        public string $countryIsoAlpha3,
        public string $name,
        public string $shortDescription,
        public string $longDescription,
        public string $imageUrl,
        public string $slug,
        public string $currency,
        public string $currencySymbol,
        public string $rebateTypeName,
        public float $vatValue,
        public string $vatValueName,
        public array $denominations,
        public ?MerchantContentDto $extendedContent = null,
    ) {
    }
}
