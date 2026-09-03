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

namespace Amilon\Tests\Unit\Dto\Response;

use Amilon\Dto\Response\AccessTokenDto;
use Amilon\Exception\AmilonExceptionInterface;
use Amilon\Exception\AuthenticationException;
use Amilon\Tests\AbstractTestCase;
use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierService;
use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierServiceInterface;

/**
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class AccessTokenDtoTest extends AbstractTestCase
{
    private EffectivePrimitiveTypeIdentifierServiceInterface $types;

    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();
        $this->types = new EffectivePrimitiveTypeIdentifierService();
        $this->now = new \DateTimeImmutable('2026-01-01T12:00:00+00:00');
    }

    public function testItMapsAConnectTokenPayload(): void
    {
        $dto = AccessTokenDto::fromResponsePayload([
            'access_token' => '  abc.def.ghi  ',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'refresh_token' => 'r-123',
            'scope' => 'b2b',
        ], $this->types, $this->now);

        $this->assertSame('abc.def.ghi', $dto->accessToken);
        $this->assertSame('Bearer', $dto->tokenType);
        $this->assertSame('r-123', $dto->refreshToken);
        $this->assertSame($this->now->getTimestamp() + 3600, $dto->expiresAt->getTimestamp());
    }

    public function testItAcceptsAStringExpiresInAndDefaultsTheTokenType(): void
    {
        $dto = AccessTokenDto::fromResponsePayload([
            'access_token' => 'abc',
            'expires_in' => '1800',
        ], $this->types, $this->now);

        $this->assertSame('Bearer', $dto->tokenType);
        $this->assertNull($dto->refreshToken);
        $this->assertSame($this->now->getTimestamp() + 1800, $dto->expiresAt->getTimestamp());
    }

    public function testABlankRefreshTokenBecomesNull(): void
    {
        $dto = AccessTokenDto::fromResponsePayload([
            'access_token' => 'abc',
            'refresh_token' => '   ',
            'expires_in' => 60,
        ], $this->types, $this->now);

        $this->assertNull($dto->refreshToken);
    }

    public function testAMissingAccessTokenIsRejected(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('access_token');

        AccessTokenDto::fromResponsePayload(['expires_in' => 3600], $this->types, $this->now);
    }

    public function testTheRejectionIsCatchableThroughTheLibraryMarker(): void
    {
        $this->expectException(AmilonExceptionInterface::class);

        AccessTokenDto::fromResponsePayload(['access_token' => '   '], $this->types, $this->now);
    }

    public function testItIsNotExpiredWellBeforeExpiry(): void
    {
        $dto = AccessTokenDto::fromResponsePayload([
            'access_token' => 'abc',
            'expires_in' => 3600,
        ], $this->types, $this->now);

        $this->assertFalse($dto->isExpired($this->now));
    }

    public function testLeewayTurnsANearlyExpiredTokenIntoAnExpiredOne(): void
    {
        $dto = AccessTokenDto::fromResponsePayload([
            'access_token' => 'abc',
            'expires_in' => 20,
        ], $this->types, $this->now);

        $this->assertFalse($dto->isExpired($this->now, 0));
        $this->assertTrue($dto->isExpired($this->now, 30));
    }

    public function testAuthorizationHeaderCombinesTypeAndToken(): void
    {
        $dto = AccessTokenDto::fromResponsePayload([
            'access_token' => 'abc',
            'token_type' => 'Bearer',
            'expires_in' => 60,
        ], $this->types, $this->now);

        $this->assertSame('Bearer abc', $dto->authorizationHeader());
    }
}
