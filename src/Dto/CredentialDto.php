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

namespace Amilon\Dto;

use Amilon\Enum\Environment;

/**
 * Transport object the caller fills with a set of Amilon credentials and hands
 * to {@see \Amilon\Service\AmilonClientFactory}.
 *
 * It is a plain, immutable data carrier on the library's public boundary: it
 * does **not** validate, trim or normalise anything. Where the credentials come
 * from — a secrets manager, a Symfony parameter bag, `$_ENV`, a database row — is
 * entirely the caller's concern. All checking (blank values, endpoint URLs, the
 * contract UUID) happens once the factory turns this DTO into a
 * {@see \Amilon\Configuration\Configuration}, which throws
 * {@see \Amilon\Exception\InvalidConfigurationException} on anything malformed.
 *
 * Build one per environment and keep it around; construct with named arguments
 * so the seven look-alike strings cannot be transposed:
 *
 *     $credentials = new CredentialDto(
 *         username:     $secrets->get('amilon_username'),
 *         password:     $secrets->get('amilon_password'),
 *         clientId:     $secrets->get('amilon_client_id'),
 *         clientSecret: $secrets->get('amilon_client_secret'),
 *         authDomain:   'https://b2bstg-sso.amilon.eu/',
 *         webDomain:    'https://b2bstg-webapi.amilon.eu/b2bwebapi/v1/',
 *         contractId:   '1ab2c3d4-567e-4b0c-b8da-a3ed94ae6392',
 *         environment:  Environment::STAGING,
 *     );
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class CredentialDto
{
    public function __construct(
        public string $username,
        public string $password,
        public string $clientId,
        public string $clientSecret,
        public string $authDomain,
        public string $webDomain,
        public string $contractId,
        public Environment $environment,
    ) {
    }

    /**
     * Map keyed by the {@see \Amilon\Configuration\Configuration} constructor
     * argument names, ready to feed {@see \Amilon\Configuration\Configuration::fromArray()}.
     *
     * @return array{
     *     username: string,
     *     password: string,
     *     clientId: string,
     *     clientSecret: string,
     *     authDomain: string,
     *     webDomain: string,
     *     contractId: string
     * }
     */
    public function toConfigurationArray(): array
    {
        return [
            'username' => $this->username,
            'password' => $this->password,
            'clientId' => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'authDomain' => $this->authDomain,
            'webDomain' => $this->webDomain,
            'contractId' => $this->contractId,
        ];
    }
}
