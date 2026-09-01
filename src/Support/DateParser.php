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

namespace Amilon\Support;

/**
 * Parses the date strings Amilon returns (ISO 8601, e.g. `2026-03-15T10:30:00Z`)
 * into a `\DateTimeImmutable`.
 *
 * Used by the response mappers. Response-side dates are best-effort: an absent
 * (`''`) or unparseable value yields `null` rather than failing the whole
 * mapping, so a stray field cannot break an otherwise good response.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class DateParser
{
    private function __construct()
    {
    }

    public static function nullable(string $value): ?\DateTimeImmutable
    {
        if ('' === $value) {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\DateMalformedStringException) {
            return null;
        }
    }
}
