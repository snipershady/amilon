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
 * Thrown when a call to the Amilon Web API does not come back as a usable
 * success: the host is unreachable or times out, the response carries a non-2xx
 * status, or its body is not the JSON the client expects.
 *
 * It is raised by {@see \Amilon\Http\AmilonResponseParser} and surfaces through
 * the resource operations on {@see \Amilon\Service\AmilonClient}. Token
 * acquisition wraps it into {@see AuthenticationException} so callers can tell an
 * auth problem from a resource-call problem while still catching everything with
 * {@see AmilonExceptionInterface}.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class ApiRequestException extends \RuntimeException implements AmilonExceptionInterface
{
    public static function transportFailure(string $method, string $path, \Throwable $throwable): self
    {
        return new self(
            sprintf('The Amilon API call %s %s could not be completed.', $method, $path),
            0,
            $throwable,
        );
    }

    public static function malformedBody(string $method, string $path, \Throwable $throwable): self
    {
        return new self(
            sprintf('The Amilon API call %s %s answered with a body that is not valid JSON.', $method, $path),
            0,
            $throwable,
        );
    }

    public static function httpError(string $method, string $path, int $status, string $detail = ''): self
    {
        $message = sprintf('The Amilon API call %s %s failed with HTTP %d.', $method, $path, $status);

        if ('' !== $detail) {
            $message .= sprintf(' %s', $detail);
        }

        return new self($message);
    }
}
