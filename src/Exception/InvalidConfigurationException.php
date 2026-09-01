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

namespace Amilon\Exception;

/**
 * Thrown when the Amilon client is handed an incomplete or malformed configuration.
 *
 * This is a caller/environment error, not a runtime data condition: a required
 * credential is missing, blank, or not shaped the way the API expects (an
 * endpoint that is not a valid absolute URL, a contract id that is not a UUID).
 * It is raised eagerly at construction time so a misconfigured deployment fails
 * before the first API call rather than mid-flow.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class InvalidConfigurationException extends \InvalidArgumentException implements AmilonExceptionInterface
{
    public static function missingValue(string $key): self
    {
        return new self(sprintf(
            'The Amilon configuration value "%s" is missing or empty. Set it in .env.local (or the process environment).',
            $key
        ));
    }

    public static function notAnAbsoluteUrl(string $key, string $value): self
    {
        return new self(sprintf(
            'The Amilon configuration value "%s" must be an absolute http(s) URL, got "%s".',
            $key,
            $value
        ));
    }

    public static function notAUuid(string $key, string $value): self
    {
        return new self(sprintf(
            'The Amilon configuration value "%s" must be a UUID, got "%s".',
            $key,
            $value
        ));
    }
}
