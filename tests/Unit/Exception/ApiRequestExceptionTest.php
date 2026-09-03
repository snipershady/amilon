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

namespace Amilon\Tests\Unit\Exception;

use Amilon\Dto\Response\ApiErrorDto;
use Amilon\Enum\AmilonErrorCode;
use Amilon\Exception\ApiRequestException;
use Amilon\Tests\AbstractTestCase;

/**
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class ApiRequestExceptionTest extends AbstractTestCase
{
    public function testHttpErrorCarriesTheParsedCodeAndBuildsAReadableMessage(): void
    {
        $exception = ApiRequestException::httpError(
            'POST',
            'orders/create/abc',
            400,
            new ApiErrorDto('0105', 'The credit is not sufficient.'),
        );

        $this->assertSame(400, $exception->httpStatus);
        $this->assertSame('0105', $exception->rawErrorCode);
        $this->assertSame(AmilonErrorCode::INSUFFICIENT_CONTRACT_CREDIT, $exception->errorCode);
        $this->assertSame([], $exception->validationErrors);
        $this->assertSame(
            'The Amilon API call POST orders/create/abc failed with HTTP 400. [ErrorCode 0105] The credit is not sufficient.',
            $exception->getMessage(),
        );
        $this->assertFalse($exception->isTransient());
    }

    public function testHttpErrorAppendsValidationLines(): void
    {
        $exception = ApiRequestException::httpError(
            'POST',
            'orders/create/abc',
            400,
            new ApiErrorDto('0400', 'Validation failed.', ['Price: required', 'ExternalOrderId: already used']),
        );

        $this->assertSame(['Price: required', 'ExternalOrderId: already used'], $exception->validationErrors);
        $this->assertStringContainsString('(Price: required | ExternalOrderId: already used)', $exception->getMessage());
    }

    public function testAnUndocumentedCodeLeavesTheEnumNullButKeepsTheRawValue(): void
    {
        $exception = ApiRequestException::httpError('GET', 'contracts/abc', 403, new ApiErrorDto('7777', 'nope'));

        $this->assertSame('7777', $exception->rawErrorCode);
        $this->assertNull($exception->errorCode);
    }

    public function testATransientServerCodeIsFlagged(): void
    {
        $exception = ApiRequestException::httpError('GET', 'contracts/abc', 500, new ApiErrorDto('0500', 'Server Error.'));

        $this->assertTrue($exception->isTransient());
    }

    public function testTransportFailureHasNoHttpMetadataButChainsThePrevious(): void
    {
        $previous = new \RuntimeException('connection refused');
        $exception = ApiRequestException::transportFailure('GET', 'contracts/abc', $previous);

        $this->assertNull($exception->httpStatus);
        $this->assertNull($exception->rawErrorCode);
        $this->assertNull($exception->errorCode);
        $this->assertSame($previous, $exception->getPrevious());
    }
}
