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
 *  - `POST orders/createpostponed/{contractId}` — same order rows plus a
 *    mandatory `CodeValidityStartDate`; fulfilment is deferred: the order is
 *    registered now and its vouchers are issued later, so the response usually
 *    carries an empty `vouchers` list; poll {@see self::complete()} for them
 *  - `GET orders/{externalOrderId}` — order summary, no vouchers
 *  - `GET orders/{externalOrderId}/complete` — order plus its issued vouchers
 *
 * All bearer-authenticated and all answered with the same shape, mapped by
 * {@see OrderMapper} — the order response did not change in V2. Reached through
 * {@see \Amilon\Service\AmilonClient::makeOrder()} /
 * {@see \Amilon\Service\AmilonClient::makeOrderPostponed()} /
 * {@see \Amilon\Service\AmilonClient::getOrderInfo()} /
 * {@see \Amilon\Service\AmilonClient::getOrderInfoComplete()}.
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
        return $this->post('create', $this->requestMapper->toPayload($createOrderRequestDto));
    }

    /**
     * `POST orders/createpostponed/{contractId}` — same order rows as
     * {@see self::create()} plus the mandatory `CodeValidityStartDate`, and
     * fulfilment is deferred: the response confirms the order and echoes its
     * `externalOrderId` while `vouchers` is typically still empty. Read them back
     * later with {@see self::complete()}.
     *
     * @throws InvalidOrderRequestException when a line carries no price, or the date is past / more than a month out
     * @throws AuthenticationException      when the bearer token cannot be obtained
     * @throws ApiRequestException          when Amilon rejects the order
     */
    public function createPostponed(
        CreateOrderRequestDto $createOrderRequestDto,
        \DateTimeImmutable $codeValidityStartDate,
    ): OrderDto {
        return $this->post(
            'createpostponed',
            $this->requestMapper->toPostponedPayload($createOrderRequestDto, $codeValidityStartDate),
        );
    }

    /**
     * `GET orders/{externalOrderId}` — order summary: status and totals, no
     * vouchers.
     *
     * @throws AuthenticationException when the bearer token cannot be obtained
     * @throws ApiRequestException     when the order is unknown or the call fails
     */
    public function summary(string $externalOrderId): OrderDto
    {
        return $this->orderMapper->map(
            $this->executor->get(sprintf('orders/%s', rawurlencode($externalOrderId))),
        );
    }

    /**
     * `GET orders/{externalOrderId}/complete` — the order plus its issued
     * vouchers.
     *
     * @throws AuthenticationException when the bearer token cannot be obtained
     * @throws ApiRequestException     when the order is unknown or the call fails
     */
    public function complete(string $externalOrderId): OrderDto
    {
        return $this->orderMapper->map(
            $this->executor->get(sprintf('orders/%s/complete', rawurlencode($externalOrderId))),
        );
    }

    /**
     * Shared tail of {@see self::create()} / {@see self::createPostponed()}: the
     * two differ only by the `orders/{operation}/{contractId}` path segment and
     * the body their request mapper produced.
     *
     * @param non-empty-string     $operation
     * @param array<string, mixed> $body
     *
     * @throws AuthenticationException when the bearer token cannot be obtained
     * @throws ApiRequestException     when Amilon rejects or cannot fulfil the order
     */
    private function post(string $operation, array $body): OrderDto
    {
        return $this->orderMapper->map($this->executor->post(
            sprintf('orders/%s/%s', $operation, $this->configuration->contractId),
            $body,
        ));
    }
}
