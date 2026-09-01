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

use Amilon\Exception\AuthenticationException;
use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierServiceInterface;

/**
 * The OAuth access token the Amilon SSO endpoint hands back for a set of
 * credentials, normalised into an absolute expiry instant.
 *
 * This is a response DTO: every API revision maps its own token payload into
 * this same shape, so callers of
 * {@see \Amilon\Service\AmilonClient::getToken()} never see version-specific
 * fields. Build it only through {@see self::fromResponsePayload()}, which pulls
 * each field through {@see EffectivePrimitiveTypeIdentifierServiceInterface} and
 * rejects a payload without a usable `access_token`.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class AccessTokenDto
{
    /**
     * @param non-empty-string      $accessToken
     * @param non-empty-string      $tokenType    OAuth token type, defaulted to "Bearer" when the payload omits it
     * @param non-empty-string|null $refreshToken null when the payload carries no (or a blank) refresh token
     */
    private function __construct(
        public string $accessToken,
        public string $tokenType,
        public \DateTimeImmutable $expiresAt,
        public ?string $refreshToken,
    ) {
    }

    /**
     * @param array<array-key, mixed> $payload decoded `connect/token` response body
     * @param \DateTimeImmutable|null $now     reference instant `expires_in` is added to; defaults to "now"
     *
     * @throws AuthenticationException when the payload has no usable `access_token`
     */
    public static function fromResponsePayload(
        array $payload,
        EffectivePrimitiveTypeIdentifierServiceInterface $effectivePrimitiveTypeIdentifierService,
        ?\DateTimeImmutable $now = null,
    ): self {
        $now ??= new \DateTimeImmutable();

        $accessToken = $effectivePrimitiveTypeIdentifierService->getStringValueFromArray('access_token', $payload, trim: true);

        if ('' === $accessToken) {
            throw AuthenticationException::malformedResponse('the "access_token" field is missing or empty');
        }

        $tokenType = $effectivePrimitiveTypeIdentifierService->getStringValueFromArray('token_type', $payload, trim: true);

        if ('' === $tokenType) {
            $tokenType = 'Bearer';
        }

        $refreshToken = $effectivePrimitiveTypeIdentifierService->getStringValueFromArray('refresh_token', $payload, trim: true);
        $expiresIn = $effectivePrimitiveTypeIdentifierService->getIntValueFromArray('expires_in', $payload);

        return new self(
            accessToken: $accessToken,
            tokenType: $tokenType,
            expiresAt: $now->setTimestamp($now->getTimestamp() + max($expiresIn, 0)),
            refreshToken: '' === $refreshToken ? null : $refreshToken,
        );
    }

    /**
     * Whether the token is expired, or close enough to expiry that it should be
     * treated as expired.
     *
     * @param int $leewaySeconds slack subtracted from the expiry so a token about
     *                           to lapse mid-request counts as already gone
     */
    public function isExpired(?\DateTimeImmutable $now = null, int $leewaySeconds = 30): bool
    {
        $now ??= new \DateTimeImmutable();
        $threshold = $now->setTimestamp($now->getTimestamp() + max($leewaySeconds, 0));

        return $this->expiresAt <= $threshold;
    }

    /**
     * The value for an HTTP `Authorization` header, e.g. `Bearer eyJ...`.
     *
     * @return non-empty-string
     */
    public function authorizationHeader(): string
    {
        return sprintf('%s %s', $this->tokenType, $this->accessToken);
    }
}
