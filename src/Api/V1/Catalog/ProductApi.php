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

namespace Amilon\Api\V1\Catalog;

use Amilon\Configuration\Configuration;
use Amilon\Dto\Response\ProductCollectionDto;
use Amilon\Enum\CountryEnum;
use Amilon\Exception\ApiRequestException;
use Amilon\Exception\AuthenticationException;
use Amilon\Http\AmilonHttpExecutor;

/**
 * V1 catalogue endpoint: the gift-card products a contract can sell in a country.
 *
 * `GET contracts/{contractId}/{country}/products`, bearer-authenticated. Reached
 * through {@see \Amilon\Service\AmilonClient::getProducts()}; not part of the
 * public surface itself.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class ProductApi
{
    public function __construct(
        private AmilonHttpExecutor $executor,
        private Configuration $configuration,
        private ProductMapper $mapper,
    ) {
    }

    /**
     * @throws AuthenticationException when the bearer token cannot be obtained
     * @throws ApiRequestException     when the call fails
     */
    public function list(CountryEnum $countryEnum): ProductCollectionDto
    {
        $payload = $this->executor->get(sprintf(
            'contracts/%s/%s/products',
            $this->configuration->contractId,
            $countryEnum->value,
        ));

        return $this->mapper->mapCollection($payload);
    }
}
