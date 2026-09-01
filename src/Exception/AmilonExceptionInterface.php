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

namespace Amilon\Exception;

/**
 * Marker interface implemented by every exception thrown by this library.
 *
 * Lets consumers catch anything originating from the Amilon client with a
 * single catch block, without coupling to the concrete SPL parent classes:
 *
 *     try {
 *         $order = $client->createOrder($payload);
 *     } catch (AmilonExceptionInterface $e) {
 *         // handle any failure coming from the Amilon integration
 *     }
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
interface AmilonExceptionInterface extends \Throwable
{
}
