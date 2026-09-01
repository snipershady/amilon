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

use Symfony\Component\Dotenv\Dotenv;

require_once dirname(__DIR__) . '/vendor/autoload.php';

/*
 * Load .env (placeholders, committed), then let .env.local (real STAGING
 * credentials, git-ignored) override it. Real process/CI environment variables
 * set before this point still win over .env. The integration suite reads the
 * resulting AMILON_* variables and skips itself while they are still the
 * placeholder values, so the unit suite stays runnable with no secrets around.
 *
 * load()/overload() are used directly instead of bootEnv() because bootEnv()
 * deliberately ignores .env.local under the "test" environment.
 */
$projectRoot = dirname(__DIR__);
$dotenv = new Dotenv();

if (is_file($projectRoot . '/.env')) {
    $dotenv->load($projectRoot . '/.env');
}

if (is_file($projectRoot . '/.env.local')) {
    $dotenv->overload($projectRoot . '/.env.local');
}
