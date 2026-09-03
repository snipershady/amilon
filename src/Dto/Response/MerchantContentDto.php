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
 * The extra editorial content the `denominations/complete` endpoint adds on top
 * of the plain `denominations` listing: long-form copy, extra logo sizes and the
 * merchant's category ids.
 *
 * Reachable through {@see MerchantDenominationsDto::$extendedContent}, which is
 * `null` for the results of {@see \Amilon\Service\AmilonClient::getDenominations()}
 * and populated only for {@see \Amilon\Service\AmilonClient::getDenominationsComplete()}.
 * Every field is an empty string when the API omits it — the mapper never
 * fabricates a value.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class MerchantContentDto
{
    public function __construct(
        public string $extraShortDescription,
        public string $termsAndConditions,
        public string $facebookFanPage,
        public string $image100x50,
        public string $image150x150,
        public string $image180x70,
        public string $category1,
        public string $category2,
        public string $category3,
    ) {
    }
}
