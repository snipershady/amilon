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
 * The Amilon platform this client talks to.
 *
 * Amilon exposes two isolated deployments with their own hosts, credentials and
 * contract ids: the {@see self::STAGING} sandbox ("b2bstg-*") for development and
 * the {@see self::PRODUCTION} platform ("b2bsales-*") where orders spend real
 * money. The value travels on {@see \Amilon\Dto\CredentialDto} so the factory can
 * label the client it builds and callers can guard money-moving calls behind
 * {@see \Amilon\Service\AmilonClient::isProduction()}.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
enum Environment: string
{
    case PRODUCTION = 'production';
    case STAGING = 'staging';

    public function isProduction(): bool
    {
        return self::PRODUCTION === $this;
    }

    public function isStaging(): bool
    {
        return self::STAGING === $this;
    }
}
