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

namespace Amilon\Enum;

/**
 * A market Amilon serves, as the `culture` path segment its catalogue endpoints
 * expect (`contracts/{id}/{culture}/denominations`).
 *
 * The case name is the ISO 3166-1 alpha-2 country; the backing value is the
 * `language-COUNTRY` culture tag the API wants, which is not always derivable
 * from the country (`GB` → `en-GB`, `NO` → `nn-NO`, `DK` → `da-DK`).
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com>
 */
enum CountryEnum: string
{
    case DE = 'de-DE'; // Deutsch
    case DK = 'da-DK'; // Danish
    case ES = 'es-ES'; // Spanish
    case FR = 'fr-FR'; // Français
    case GB = 'en-GB'; // English
    case IT = 'it-IT'; // Italian
    case NL = 'nl-NL'; // Dutch
    case NO = 'nn-NO'; // Norwegian
    case PL = 'pl-PL'; // Polish
    case PT = 'pt-PT'; // Português
    case SE = 'sv-SE'; // Swedish
}
