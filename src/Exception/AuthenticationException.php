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
 * Thrown when the client cannot obtain a usable OAuth access token from the
 * Amilon SSO endpoint.
 *
 * Unlike {@see InvalidConfigurationException} this is a runtime condition, not a
 * caller mistake: the SSO host is unreachable, it rejected the credentials, or it
 * answered with a body the client cannot turn into a token. Callers catch it
 * through {@see AmilonExceptionInterface} like any other failure from the library.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class AuthenticationException extends \RuntimeException implements AmilonExceptionInterface
{
    public static function transportFailure(\Throwable $throwable): self
    {
        return new self('Could not reach the Amilon SSO endpoint to obtain an access token.', 0, $throwable);
    }

    public static function httpError(int $status, string $detail = ''): self
    {
        $message = sprintf('The Amilon SSO endpoint rejected the token request with HTTP %d.', $status);

        if ('' !== $detail) {
            $message .= sprintf(' %s', $detail);
        }

        return new self($message);
    }

    public static function malformedResponse(string $reason): self
    {
        return new self(sprintf('The Amilon SSO token response could not be used: %s.', $reason));
    }
}
