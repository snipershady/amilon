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
 * One issued gift card in an {@see OrderDto}: the redeemable link and its
 * validity window, plus which product and retailer it belongs to.
 *
 * A flat, version-shared projection of an entry in the order-create response's
 * `Vouchers` array. Dates are `null` when Amilon omits them or sends something
 * unparseable.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class VoucherDto
{
    public function __construct(
        public string $productId,
        public string $retailerId,
        public string $voucherLink,
        public ?\DateTimeImmutable $validityStartDate,
        public ?\DateTimeImmutable $validityEndDate,
    ) {
    }
}
