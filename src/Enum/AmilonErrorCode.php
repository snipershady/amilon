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

namespace Amilon\Enum;

/**
 * The `ErrorCode` string an Amilon Web API error body carries
 * (`{"ErrorCode": "0105", "Message": "…"}`), as listed in the "Error Codes list"
 * section of the Web API v2 documentation.
 *
 * The backing value is the exact four-digit string Amilon sends, zero-padded.
 * The doc's own note — "the online version includes also all error codes, that
 * are not mentioned in this document" — means this set is **not** exhaustive of
 * what production can return, so callers must treat
 * {@see self::tryFromErrorBody()} / `tryFrom()` returning `null` as "an error
 * code we do not model", never as "no error". {@see \Amilon\Exception\ApiRequestException}
 * keeps the raw string alongside the parsed case for exactly that reason.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
enum AmilonErrorCode: string
{
    /** Technical temporary issue — the call can be retried. */
    case TECHNICAL_TEMPORARY_ISSUE = '0000';

    /** User not associated to the contract. */
    case USER_NOT_ASSOCIATED_TO_CONTRACT = '0100';

    /** The contract does not exist. */
    case CONTRACT_DOES_NOT_EXIST = '0101';

    /** The contract is not valid. */
    case CONTRACT_NOT_VALID = '0102';

    /** The contract has not yet started. */
    case CONTRACT_NOT_YET_STARTED = '0103';

    /** The contract has expired. */
    case CONTRACT_EXPIRED = '0104';

    /** Insufficient residual credit in the contract. */
    case INSUFFICIENT_CONTRACT_CREDIT = '0105';

    /** The denomination is not enabled in the contract. */
    case DENOMINATION_NOT_ENABLED = '0106';

    /** Code conversion error. */
    case CODE_CONVERSION_ERROR = '0200';

    /** The user is not associated to a valid customer. */
    case USER_NOT_ASSOCIATED_TO_CUSTOMER = '0300';

    /** `CreateOrder` / `CreateOrderPostponed`: the passed `ExternalOrderId` is already used. */
    case EXTERNAL_ORDER_ID_ALREADY_USED = '0400';

    /** `CancelPendingOrder`: the state of the order is not `Pending`. */
    case CANCEL_PENDING_ORDER_NOT_PENDING = '0401';

    /** `CompletePendingOrder`: the state of the order is not `Pending`. */
    case COMPLETE_PENDING_ORDER_NOT_PENDING = '0402';

    /** Order not found. */
    case ORDER_NOT_FOUND = '0403';

    /** `CreatePendingOrder`: the decrypted authorization code is invalid. */
    case CREATE_PENDING_ORDER_INVALID_AUTH_CODE = '0406';

    /** Server error. */
    case SERVER_ERROR = '0500';

    /** Retailer not found. */
    case RETAILER_NOT_FOUND = '0600';

    /**
     * The parsed error code of an Amilon error body, or `null` when the body
     * carries no `ErrorCode` or one this enum does not model.
     *
     * @param array<array-key, mixed> $errorBody the decoded `{"ErrorCode": …, "Message": …}` payload
     */
    public static function tryFromErrorBody(array $errorBody): ?self
    {
        $rawErrorCode = $errorBody['ErrorCode'] ?? null;

        if (!is_string($rawErrorCode)) {
            return null;
        }

        return self::tryFrom(trim($rawErrorCode));
    }

    /**
     * The English description the documentation gives for this code.
     */
    public function description(): string
    {
        return match ($this) {
            self::TECHNICAL_TEMPORARY_ISSUE => 'Technical temporary issue.',
            self::USER_NOT_ASSOCIATED_TO_CONTRACT => 'User not associated to the contract.',
            self::CONTRACT_DOES_NOT_EXIST => 'The contract does not exist.',
            self::CONTRACT_NOT_VALID => 'The contract is not valid.',
            self::CONTRACT_NOT_YET_STARTED => 'The contract has not yet started.',
            self::CONTRACT_EXPIRED => 'The contract has expired.',
            self::INSUFFICIENT_CONTRACT_CREDIT => 'Insufficient residual credit in the contract.',
            self::DENOMINATION_NOT_ENABLED => 'The denomination is not enabled in the contract.',
            self::CODE_CONVERSION_ERROR => 'Code conversion error.',
            self::USER_NOT_ASSOCIATED_TO_CUSTOMER => 'The user is not associated to a valid customer.',
            self::EXTERNAL_ORDER_ID_ALREADY_USED => 'The passed ExternalOrderId is already used.',
            self::CANCEL_PENDING_ORDER_NOT_PENDING => 'CancelPendingOrder: the state of the order is not Pending.',
            self::COMPLETE_PENDING_ORDER_NOT_PENDING => 'CompletePendingOrder: the state of the order is not Pending.',
            self::ORDER_NOT_FOUND => 'Order not found.',
            self::CREATE_PENDING_ORDER_INVALID_AUTH_CODE => 'CreatePendingOrder: decrypt authorization code is invalid.',
            self::SERVER_ERROR => 'Server error.',
            self::RETAILER_NOT_FOUND => 'Retailer not found.',
        };
    }

    /**
     * Whether the condition is server-side and transient, so retrying the same
     * call with the same input can succeed. The FAQ's timeout advice ("try to
     * place the order again passing the same input parameters") maps here.
     */
    public function isTransient(): bool
    {
        return match ($this) {
            self::TECHNICAL_TEMPORARY_ISSUE, self::SERVER_ERROR => true,
            default => false,
        };
    }

    /**
     * Whether the condition is about the contract itself (missing, invalid, not
     * started, expired, out of credit, or the caller not being attached to it) —
     * i.e. no amount of request tweaking will help until the contract is sorted.
     */
    public function isContractProblem(): bool
    {
        return match ($this) {
            self::USER_NOT_ASSOCIATED_TO_CONTRACT,
            self::CONTRACT_DOES_NOT_EXIST,
            self::CONTRACT_NOT_VALID,
            self::CONTRACT_NOT_YET_STARTED,
            self::CONTRACT_EXPIRED,
            self::INSUFFICIENT_CONTRACT_CREDIT => true,
            default => false,
        };
    }
}
