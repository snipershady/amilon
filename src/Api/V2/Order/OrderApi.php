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

namespace Amilon\Api\V2\Order;

use Amilon\Configuration\Configuration;
use Amilon\Dto\Request\CreateOrderRequestDto;
use Amilon\Dto\Response\OrderDto;
use Amilon\Exception\ApiRequestException;
use Amilon\Exception\AuthenticationException;
use Amilon\Exception\InvalidOrderRequestException;
use Amilon\Http\AmilonHttpExecutor;

/**
 * V2 ordering endpoints: place an order and read back an existing one.
 *
 *  - `POST orders/create/{contractId}` — immediate fulfilment; body from
 *    {@see OrderRequestMapper} (`RetailerId` + `Price` per row)
 *  - `POST orders/createpostponed/{contractId}` — same body, deferred
 *    fulfilment: the order is registered now and its vouchers are issued later,
 *    so the response usually carries an empty `vouchers` list; poll
 *    {@see self::complete()} for them
 *  - `GET orders/{externalOrderId}/complete`
 *
 * All bearer-authenticated and all answered with the same shape, mapped by
 * {@see OrderMapper} — the order response did not change in V2. Reached through
 * {@see \Amilon\Service\AmilonClient::makeOrder()} /
 * {@see \Amilon\Service\AmilonClient::makeOrderPostponed()} /
 * {@see \Amilon\Service\AmilonClient::getOrderInfo()}.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class OrderApi
{
    public function __construct(
        private AmilonHttpExecutor $executor,
        private Configuration $configuration,
        private OrderRequestMapper $requestMapper,
        private OrderMapper $orderMapper,
    ) {
    }

    /**
     * `POST orders/create/{contractId}` — Amilon fulfils the order straight away.
     *
     * @throws InvalidOrderRequestException when a line carries no price
     * @throws AuthenticationException      when the bearer token cannot be obtained
     * @throws ApiRequestException          when Amilon rejects or cannot fulfil the order
     */
    public function create(CreateOrderRequestDto $createOrderRequestDto): OrderDto
    {
        return $this->submit('create', $createOrderRequestDto);
    }

    /**
     * `POST orders/createpostponed/{contractId}` — same request body as
     * {@see self::create()}, but fulfilment is deferred: the response confirms
     * the order and echoes its `externalOrderId` while `vouchers` is typically
     * still empty. Read them back later with {@see self::complete()}.
     *
     * @throws InvalidOrderRequestException when a line carries no price
     * @throws AuthenticationException      when the bearer token cannot be obtained
     * @throws ApiRequestException          when Amilon rejects the order
     */
    public function createPostponed(CreateOrderRequestDto $createOrderRequestDto): OrderDto
    {
        return $this->submit('createpostponed', $createOrderRequestDto);
    }

    /**
     * @throws AuthenticationException when the bearer token cannot be obtained
     * @throws ApiRequestException     when the order is unknown or the call fails
     */
    public function complete(string $externalOrderId): OrderDto
    {
        $payload = $this->executor->get(
            sprintf('orders/%s/complete', rawurlencode($externalOrderId)),
        );

        return $this->orderMapper->map($payload);
    }

    /**
     * Shared body of {@see self::create()} / {@see self::createPostponed()}: the
     * two differ only by the `orders/{operation}/{contractId}` path segment.
     *
     * @param non-empty-string $operation
     *
     * @throws InvalidOrderRequestException when a line carries no price
     * @throws AuthenticationException      when the bearer token cannot be obtained
     * @throws ApiRequestException          when Amilon rejects or cannot fulfil the order
     */
    private function submit(string $operation, CreateOrderRequestDto $createOrderRequestDto): OrderDto
    {
        $payload = $this->executor->post(
            sprintf('orders/%s/%s', $operation, $this->configuration->contractId),
            $this->requestMapper->toPayload($createOrderRequestDto),
        );

        return $this->orderMapper->map($payload);
    }
}
