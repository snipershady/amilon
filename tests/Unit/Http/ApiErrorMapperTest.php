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

namespace Amilon\Tests\Unit\Http;

use Amilon\Enum\AmilonErrorCode;
use Amilon\Http\ApiErrorMapper;
use Amilon\Tests\AbstractTestCase;
use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierService;

/**
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class ApiErrorMapperTest extends AbstractTestCase
{
    private ApiErrorMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new ApiErrorMapper(new EffectivePrimitiveTypeIdentifierService());
    }

    public function testItMapsAPlainAmilonErrorBody(): void
    {
        $error = $this->mapper->fromPayload([
            'ErrorCode' => '0105',
            'Message' => 'The credit is not sufficient.',
        ]);

        $this->assertSame('0105', $error->rawErrorCode);
        $this->assertSame('The credit is not sufficient.', $error->message);
        $this->assertSame(AmilonErrorCode::INSUFFICIENT_CONTRACT_CREDIT, $error->errorCode());
        $this->assertFalse($error->hasValidationErrors());
        $this->assertSame([], $error->validationErrors);
    }

    public function testAnUndocumentedCodeIsKeptRawWithNoParsedCase(): void
    {
        $error = $this->mapper->fromPayload(['ErrorCode' => '7777', 'Message' => 'brand new']);

        $this->assertSame('7777', $error->rawErrorCode);
        $this->assertNull($error->errorCode());
    }

    public function testANumericErrorCodeIsCoercedToAString(): void
    {
        $error = $this->mapper->fromPayload(['ErrorCode' => 105]);

        $this->assertSame('105', $error->rawErrorCode);
    }

    public function testAnEmptyBodyYieldsNullCodeAndEmptyMessage(): void
    {
        $error = $this->mapper->fromPayload([]);

        $this->assertNull($error->rawErrorCode);
        $this->assertSame('', $error->message);
        $this->assertNull($error->errorCode());
    }

    public function testItFallsBackToOAuthAndProblemDetailsMessageKeys(): void
    {
        $this->assertSame(
            'invalid_grant',
            $this->mapper->fromPayload(['error' => 'invalid_grant'])->message,
        );
        $this->assertSame(
            'The username or password is incorrect.',
            $this->mapper->fromPayload(['error_description' => 'The username or password is incorrect.'])->message,
        );
        $this->assertSame(
            'Detail wins over title',
            $this->mapper->fromPayload(['title' => 'Bad Request', 'detail' => 'Detail wins over title'])->message,
        );
    }

    public function testItFlattensCreateOrderModelErrors(): void
    {
        $error = $this->mapper->fromPayload([
            'ErrorCode' => '0400',
            'Message' => 'Validation failed.',
            'ModelErrors' => [
                ['PropertyName' => 'OrderRows[0].Price', 'Errors' => ['must be greater than 0', 'is required']],
                ['PropertyName' => 'ExternalOrderId', 'Errors' => ['already used']],
                'not-an-object',
                ['PropertyName' => 'Quantity'],
            ],
        ]);

        $this->assertTrue($error->hasValidationErrors());
        $this->assertSame(
            [
                'OrderRows[0].Price: must be greater than 0; is required',
                'ExternalOrderId: already used',
                'Quantity',
            ],
            $error->validationErrors,
        );
    }

    public function testModelErrorsThatAreNotAListAreIgnored(): void
    {
        $error = $this->mapper->fromPayload(['ModelErrors' => 'boom']);

        $this->assertSame([], $error->validationErrors);
    }
}
