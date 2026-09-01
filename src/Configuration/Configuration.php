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

namespace Amilon\Configuration;

use Amilon\Dto\CredentialDto;
use Amilon\Exception\InvalidConfigurationException;

/**
 * Immutable, self-validating holder for the credentials the Amilon API needs.
 *
 * Build it from the process environment — which `symfony/dotenv` populates from
 * `.env` / `.env.local` — with {@see self::fromEnvironment()}, or from an
 * explicit map with {@see self::fromArray()}. Both paths trim every value,
 * reject anything missing or blank, normalise the two endpoint URLs to a single
 * trailing slash and check that the contract id is a UUID, so a misconfigured
 * deployment fails fast instead of at the first API call.
 *
 * Recognised environment keys:
 *
 *   AMILON_USERNAME       AMILON_PASSWORD
 *   AMILON_CLIENT_ID      AMILON_CLIENT_SECRET
 *   AMILON_AUTH_DOMAIN    AMILON_WEB_DOMAIN
 *   AMILON_CONTRACT_ID
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class Configuration
{
    private const string UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    /**
     * @var non-empty-array<string, string> map of constructor argument name => environment key
     */
    private const array ENVIRONMENT_KEYS = [
        'username' => 'AMILON_USERNAME',
        'password' => 'AMILON_PASSWORD',
        'clientId' => 'AMILON_CLIENT_ID',
        'clientSecret' => 'AMILON_CLIENT_SECRET',
        'authDomain' => 'AMILON_AUTH_DOMAIN',
        'webDomain' => 'AMILON_WEB_DOMAIN',
        'contractId' => 'AMILON_CONTRACT_ID',
    ];

    /**
     * @param non-empty-string $username
     * @param non-empty-string $password
     * @param non-empty-string $clientId
     * @param non-empty-string $clientSecret
     * @param non-empty-string $authDomain   absolute http(s) URL, guaranteed to end with a single "/"
     * @param non-empty-string $webDomain    absolute http(s) URL, guaranteed to end with a single "/"
     * @param non-empty-string $contractId   lower-case UUID
     */
    private function __construct(
        public string $username,
        public string $password,
        public string $clientId,
        public string $clientSecret,
        public string $authDomain,
        public string $webDomain,
        public string $contractId,
    ) {
    }

    /**
     * @param array<string, mixed>|null $environment defaults to the live process environment ($_SERVER merged over $_ENV)
     *
     * @throws InvalidConfigurationException when a value is missing, blank or malformed
     */
    public static function fromEnvironment(?array $environment = null): self
    {
        /** @var array<string, mixed> $source */
        $source = $environment ?? $_SERVER + $_ENV;

        $values = [];
        foreach (self::ENVIRONMENT_KEYS as $argument => $key) {
            $values[$argument] = $source[$key] ?? null;
        }

        return self::fromArray($values);
    }

    /**
     * Validate and normalise the credentials carried by a {@see CredentialDto}
     * (the environment discriminator is not part of the configuration itself —
     * {@see \Amilon\Service\AmilonClientFactory} keeps it on the client).
     *
     * @throws InvalidConfigurationException when a value is missing, blank or malformed
     */
    public static function fromCredentialDto(CredentialDto $credentialDto): self
    {
        return self::fromArray($credentialDto->toConfigurationArray());
    }

    /**
     * @param array<string, mixed> $values keyed by the constructor argument names (username, password, ...)
     *
     * @throws InvalidConfigurationException when a value is missing, blank or malformed
     */
    public static function fromArray(array $values): self
    {
        return new self(
            username: self::readRequired($values, 'username'),
            password: self::readRequired($values, 'password'),
            clientId: self::readRequired($values, 'clientId'),
            clientSecret: self::readRequired($values, 'clientSecret'),
            authDomain: self::readUrl($values, 'authDomain'),
            webDomain: self::readUrl($values, 'webDomain'),
            contractId: self::readUuid($values, 'contractId'),
        );
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return non-empty-string
     *
     * @throws InvalidConfigurationException
     */
    private static function readRequired(array $values, string $name): string
    {
        $value = $values[$name] ?? null;

        if (!is_string($value)) {
            throw InvalidConfigurationException::missingValue($name);
        }

        $trimmed = trim($value);

        if ('' === $trimmed) {
            throw InvalidConfigurationException::missingValue($name);
        }

        return $trimmed;
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return non-empty-string
     *
     * @throws InvalidConfigurationException
     */
    private static function readUrl(array $values, string $name): string
    {
        $value = self::readRequired($values, $name);

        $isHttp = str_starts_with($value, 'http://') || str_starts_with($value, 'https://');

        if (!$isHttp || false === filter_var($value, FILTER_VALIDATE_URL)) {
            throw InvalidConfigurationException::notAnAbsoluteUrl($name, $value);
        }

        return rtrim($value, '/') . '/';
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return non-empty-string
     *
     * @throws InvalidConfigurationException
     */
    private static function readUuid(array $values, string $name): string
    {
        $value = self::readRequired($values, $name);

        if (1 !== preg_match(self::UUID_PATTERN, $value)) {
            throw InvalidConfigurationException::notAUuid($name, $value);
        }

        return strtolower($value);
    }
}
