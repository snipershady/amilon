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

/**
 * The result of {@see \Amilon\Service\AmilonClient::getRetailers()}: an ordered,
 * immutable set of {@see RetailerDto}, iterable and countable.
 *
 * @implements \IteratorAggregate<int, RetailerDto>
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class RetailerCollectionDto implements \IteratorAggregate, \Countable
{
    /**
     * @param list<RetailerDto> $retailers
     */
    public function __construct(
        private array $retailers,
    ) {
    }

    /**
     * @return list<RetailerDto>
     */
    public function all(): array
    {
        return $this->retailers;
    }

    /**
     * @return \ArrayIterator<int, RetailerDto>
     */
    #[\Override]
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->retailers);
    }

    #[\Override]
    public function count(): int
    {
        return count($this->retailers);
    }

    public function isEmpty(): bool
    {
        return [] === $this->retailers;
    }
}
