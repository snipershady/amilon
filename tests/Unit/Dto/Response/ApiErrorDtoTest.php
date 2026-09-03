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

use Amilon\Dto\Response\ApiErrorDto;
use Amilon\Enum\AmilonErrorCode;
use Amilon\Tests\AbstractTestCase;

/**
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class ApiErrorDtoTest extends AbstractTestCase
{
    public function testItResolvesADocumentedErrorCode(): void
    {
        $apiErrorDto = new ApiErrorDto('0403', 'Order not found.');

        $this->assertSame(AmilonErrorCode::ORDER_NOT_FOUND, $apiErrorDto->errorCode());
    }

    public function testAnUndocumentedOrAbsentCodeResolvesToNull(): void
    {
        $this->assertNull((new ApiErrorDto('7777', 'brand new'))->errorCode());
        $this->assertNull((new ApiErrorDto(rawErrorCode: null, message: ''))->errorCode());
    }

    public function testValidationErrorsPredicate(): void
    {
        $this->assertFalse((new ApiErrorDto(rawErrorCode: null, message: 'x'))->hasValidationErrors());
        $this->assertTrue((new ApiErrorDto(rawErrorCode: null, message: 'x', validationErrors: ['Price: required']))->hasValidationErrors());
    }
}
