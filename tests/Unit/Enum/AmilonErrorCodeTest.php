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

namespace Amilon\Tests\Unit\Enum;

use Amilon\Enum\AmilonErrorCode;
use Amilon\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class AmilonErrorCodeTest extends AbstractTestCase
{
    /**
     * Every code the "Error Codes list" section of the Web API v2 doc tabulates.
     *
     * @return iterable<string, array{string, string}>
     */
    public static function documentedCodes(): iterable
    {
        yield 'technical temporary issue' => ['0000', 'TECHNICAL_TEMPORARY_ISSUE'];
        yield 'server error' => ['0500', 'SERVER_ERROR'];
        yield 'user not associated to contract' => ['0100', 'USER_NOT_ASSOCIATED_TO_CONTRACT'];
        yield 'contract does not exist' => ['0101', 'CONTRACT_DOES_NOT_EXIST'];
        yield 'contract not valid' => ['0102', 'CONTRACT_NOT_VALID'];
        yield 'contract not yet started' => ['0103', 'CONTRACT_NOT_YET_STARTED'];
        yield 'contract expired' => ['0104', 'CONTRACT_EXPIRED'];
        yield 'insufficient credit' => ['0105', 'INSUFFICIENT_CONTRACT_CREDIT'];
        yield 'denomination not enabled' => ['0106', 'DENOMINATION_NOT_ENABLED'];
        yield 'code conversion error' => ['0200', 'CODE_CONVERSION_ERROR'];
        yield 'user not associated to customer' => ['0300', 'USER_NOT_ASSOCIATED_TO_CUSTOMER'];
        yield 'external order id already used' => ['0400', 'EXTERNAL_ORDER_ID_ALREADY_USED'];
        yield 'cancel pending order not pending' => ['0401', 'CANCEL_PENDING_ORDER_NOT_PENDING'];
        yield 'complete pending order not pending' => ['0402', 'COMPLETE_PENDING_ORDER_NOT_PENDING'];
        yield 'order not found' => ['0403', 'ORDER_NOT_FOUND'];
        yield 'create pending order invalid auth code' => ['0406', 'CREATE_PENDING_ORDER_INVALID_AUTH_CODE'];
        yield 'retailer not found' => ['0600', 'RETAILER_NOT_FOUND'];
    }

    #[DataProvider('documentedCodes')]
    public function testEveryDocumentedCodeIsModelled(string $wireValue, string $caseName): void
    {
        $case = AmilonErrorCode::from($wireValue);

        $this->assertSame($caseName, $case->name);
        $this->assertNotSame('', $case->description());
        $this->assertStringEndsWith('.', $case->description());
    }

    public function testTheEnumModelsExactlyTheDocumentedCodes(): void
    {
        $wireValues = array_map(
            static fn (AmilonErrorCode $amilonErrorCode): string => $amilonErrorCode->value,
            AmilonErrorCode::cases(),
        );

        sort($wireValues);

        $this->assertSame(
            ['0000', '0100', '0101', '0102', '0103', '0104', '0105', '0106', '0200', '0300', '0400', '0401', '0402', '0403', '0406', '0500', '0600'],
            $wireValues,
        );
    }

    public function testAnUndocumentedCodeDoesNotResolve(): void
    {
        $this->assertNull(AmilonErrorCode::tryFrom('9999'));
    }

    public function testOnlyTechnicalAndServerErrorsAreTransient(): void
    {
        $transient = array_values(array_filter(
            AmilonErrorCode::cases(),
            static fn (AmilonErrorCode $amilonErrorCode): bool => $amilonErrorCode->isTransient(),
        ));

        $this->assertSame(
            [AmilonErrorCode::TECHNICAL_TEMPORARY_ISSUE, AmilonErrorCode::SERVER_ERROR],
            $transient,
        );
    }

    public function testContractProblemsAreGroupedTogether(): void
    {
        $this->assertTrue(AmilonErrorCode::CONTRACT_EXPIRED->isContractProblem());
        $this->assertTrue(AmilonErrorCode::INSUFFICIENT_CONTRACT_CREDIT->isContractProblem());
        $this->assertFalse(AmilonErrorCode::ORDER_NOT_FOUND->isContractProblem());
        $this->assertFalse(AmilonErrorCode::RETAILER_NOT_FOUND->isContractProblem());
    }

    public function testItParsesAnErrorBody(): void
    {
        $this->assertSame(
            AmilonErrorCode::INSUFFICIENT_CONTRACT_CREDIT,
            AmilonErrorCode::tryFromErrorBody(['ErrorCode' => ' 0105 ', 'Message' => 'The credit is not sufficient.']),
        );
    }

    public function testItReturnsNullForAnErrorBodyWithoutAModelledCode(): void
    {
        $this->assertNull(AmilonErrorCode::tryFromErrorBody(['Message' => 'boom']));
        $this->assertNull(AmilonErrorCode::tryFromErrorBody(['ErrorCode' => 9999]));
        $this->assertNull(AmilonErrorCode::tryFromErrorBody(['ErrorCode' => '7777']));
    }
}
