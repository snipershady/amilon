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

namespace Amilon\Tests\Unit\Support;

use Amilon\Support\DateParser;
use Amilon\Tests\AbstractTestCase;

/**
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class DateParserTest extends AbstractTestCase
{
    public function testItParsesAnIso8601Timestamp(): void
    {
        $parsed = DateParser::nullable('2026-03-15T10:30:00+00:00');

        $this->assertInstanceOf(\DateTimeImmutable::class, $parsed);
        $this->assertSame((new \DateTimeImmutable('2026-03-15T10:30:00+00:00'))->getTimestamp(), $parsed->getTimestamp());
    }

    public function testAnEmptyStringIsNull(): void
    {
        $this->assertNotInstanceOf(\DateTimeImmutable::class, DateParser::nullable(''));
    }

    public function testAnUnparseableStringIsNull(): void
    {
        $this->assertNotInstanceOf(\DateTimeImmutable::class, DateParser::nullable('not-a-date'));
    }
}
