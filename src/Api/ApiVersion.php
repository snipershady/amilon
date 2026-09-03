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

namespace Amilon\Api;

/**
 * The Amilon Web API revisions this library knows how to speak.
 *
 * Versioning is an internal concern: callers use the version-less operations on
 * {@see \Amilon\Service\AmilonClient} and always get the newest implementation.
 * {@see self::latest()} is the single switch — {@see \Amilon\Service\AmilonClientFactory}
 * reads it to decide which `Amilon\Api\V*` implementation of
 * {@see AmilonApiInterface} to wire up. Promoting a new revision means adding a
 * case here, pointing {@see self::latest()} at it, and providing the matching
 * implementation; the public surface and the response DTOs stay as stable as the
 * revision-to-revision differences allow.
 *
 * The backing value is the path segment Amilon uses for that revision
 * (`.../b2bwebapi/v2/`), and the base URL for it is configured per revision
 * ({@see \Amilon\Configuration\Configuration::$webDomainV2}), never hard-coded.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
enum ApiVersion: string
{
    case V2 = 'v2';

    /**
     * The revision new clients are built against.
     */
    public static function latest(): self
    {
        return self::V2;
    }
}
