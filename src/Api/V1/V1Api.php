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

namespace Amilon\Api\V1;

use Amilon\Api\AmilonApiInterface;
use Amilon\Auth\TokenProvider;
use Amilon\Dto\Response\AccessTokenDto;

/**
 * {@see AmilonApiInterface} implementation for revision 1 of the Amilon Web API
 * (`.../b2bwebapi/v1/`).
 *
 * This class is where V1's endpoint knowledge lives — paths, verbs, payload
 * shapes. It is selected by {@see \Amilon\Service\AmilonClientFactory} while
 * {@see \Amilon\Api\ApiVersion::latest()} points at
 * {@see \Amilon\Api\ApiVersion::V1}; callers never name it.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class V1Api implements AmilonApiInterface
{
    public function __construct(
        private TokenProvider $tokenProvider,
    ) {
    }

    public function getToken(): AccessTokenDto
    {
        return $this->tokenProvider->currentToken();
    }
}
