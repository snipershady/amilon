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
 * The result of {@see \Amilon\Service\AmilonClient::getProducts()}: an ordered,
 * immutable set of {@see ProductDto}, iterable and countable.
 *
 * Same shape V1 returned, so an existing caller keeps iterating it and calling
 * {@see self::all()} / {@see self::count()} / {@see self::isEmpty()} unchanged.
 * It is rebuilt from the V2 `denominations` tree by
 * {@see \Amilon\Api\V2\Catalog\ProductCompatMapper}.
 *
 * @implements \IteratorAggregate<int, ProductDto>
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class ProductCollectionDto implements \IteratorAggregate, \Countable
{
    /**
     * @param list<ProductDto> $products
     */
    public function __construct(
        private array $products,
    ) {
    }

    /**
     * @return list<ProductDto>
     */
    public function all(): array
    {
        return $this->products;
    }

    /**
     * @return \ArrayIterator<int, ProductDto>
     */
    #[\Override]
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->products);
    }

    #[\Override]
    public function count(): int
    {
        return count($this->products);
    }

    public function isEmpty(): bool
    {
        return [] === $this->products;
    }
}
