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

namespace Amilon\Service;

use Amilon\Configuration\Configuration;
use Amilon\Enum\Environment;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Entry point of the library: a resolved {@see Configuration} bound to a
 * ready-to-use HTTP transport.
 *
 * Do not instantiate this directly — {@see AmilonClientFactory} owns the wiring
 * (URL scoping, default headers, environment labelling). The resource-specific
 * operations (products, retailers, orders, …) will be added here / on
 * collaborators that consume {@see self::getHttpClient()} as the API surface is
 * built out.
 *
 * The instance holds no shared cache: one client owns one transport, and an OAuth
 * token, once introduced, will live for that transport's lifetime only.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final readonly class AmilonClient
{
    /**
     * @internal use {@see AmilonClientFactory::create()}
     */
    public function __construct(
        private Configuration $configuration,
        private HttpClientInterface $httpClient,
        private Environment $environment,
    ) {
    }

    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    /**
     * The transport the factory scoped to {@see Configuration::$webDomain} and
     * primed with the JSON `Accept` header. Consumed internally by the
     * resource operations; exposed while the API surface is under construction.
     */
    public function getHttpClient(): HttpClientInterface
    {
        return $this->httpClient;
    }

    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    /**
     * Guard money-moving calls (order creation) with this: it is only true when
     * the client was built from {@see Environment::PRODUCTION} credentials.
     */
    public function isProduction(): bool
    {
        return $this->environment->isProduction();
    }
}
