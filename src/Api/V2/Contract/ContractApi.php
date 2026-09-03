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

namespace Amilon\Api\V2\Contract;

use Amilon\Configuration\Configuration;
use Amilon\Dto\Response\ContractInfoDto;
use Amilon\Exception\ApiRequestException;
use Amilon\Exception\AuthenticationException;
use Amilon\Http\AmilonHttpExecutor;

/**
 * V2 contract endpoint: the configured contract's balance and last-update time.
 *
 * `GET contracts/{contractId}`, bearer-authenticated — unchanged from V1. Reached
 * through {@see \Amilon\Service\AmilonClient::getContractInfo()}.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class ContractApi
{
    public function __construct(
        private AmilonHttpExecutor $executor,
        private Configuration $configuration,
        private ContractMapper $mapper,
    ) {
    }

    /**
     * @throws AuthenticationException when the bearer token cannot be obtained
     * @throws ApiRequestException     when the call fails
     */
    public function info(): ContractInfoDto
    {
        return $this->mapper->map(
            $this->executor->get(sprintf('contracts/%s', $this->configuration->contractId)),
        );
    }
}
