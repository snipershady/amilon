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

namespace Amilon\Api\V2\Catalog;

use Amilon\Configuration\Configuration;
use Amilon\Dto\Response\RetailerCategoryCollectionDto;
use Amilon\Dto\Response\RetailerCollectionDto;
use Amilon\Enum\CountryEnum;
use Amilon\Exception\ApiRequestException;
use Amilon\Exception\AuthenticationException;
use Amilon\Http\AmilonHttpExecutor;

/**
 * V2 "Retailers" resource area:
 *
 *  - `GET contracts/{contractId}/{culture}/retailers` — the brands available to
 *    the contract in a country (unchanged from V1)
 *  - `GET retailers/categories` — the platform-wide list of brand categories,
 *    with optional `CategoryId` / `CategoryName` filters
 *
 * Both bearer-authenticated. Reached through
 * {@see \Amilon\Service\AmilonClient::getRetailers()} /
 * {@see \Amilon\Service\AmilonClient::getRetailerCategories()}.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class RetailerApi
{
    public function __construct(
        private AmilonHttpExecutor $executor,
        private Configuration $configuration,
        private RetailerMapper $mapper,
        private RetailerCategoryMapper $categoryMapper,
    ) {
    }

    /**
     * @throws AuthenticationException when the bearer token cannot be obtained
     * @throws ApiRequestException     when the call fails
     */
    public function list(CountryEnum $countryEnum): RetailerCollectionDto
    {
        $payload = $this->executor->get(sprintf(
            'contracts/%s/%s/retailers',
            $this->configuration->contractId,
            $countryEnum->value,
        ));

        return $this->mapper->mapCollection($payload);
    }

    /**
     * `GET retailers/categories`, optionally narrowed by category id and/or name.
     *
     * @throws AuthenticationException when the bearer token cannot be obtained
     * @throws ApiRequestException     when the call fails
     */
    public function categories(?string $categoryId = null, ?string $categoryName = null): RetailerCategoryCollectionDto
    {
        $filters = array_filter(
            ['CategoryId' => $categoryId, 'CategoryName' => $categoryName],
            static fn (?string $value): bool => null !== $value && '' !== trim($value),
        );

        $path = 'retailers/categories';

        if ([] !== $filters) {
            $path .= '?' . http_build_query($filters);
        }

        return $this->categoryMapper->mapCollection($this->executor->get($path));
    }
}
