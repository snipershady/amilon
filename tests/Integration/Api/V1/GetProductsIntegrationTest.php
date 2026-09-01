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

namespace Amilon\Tests\Integration\Api\V1;

use Amilon\Enum\CountryEnum;
use Amilon\Tests\Integration\AbstractIntegrationTestCase;

/**
 * Exercises {@see \Amilon\Service\AmilonClient::getProducts()} against the real
 * Amilon STAGING catalogue.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class GetProductsIntegrationTest extends AbstractIntegrationTestCase
{
    public function testItListsProductsForItaly(): void
    {
        $products = $this->liveStagingClient()->getProducts(CountryEnum::IT);

        $this->assertFalse($products->isEmpty(), 'the STAGING IT catalogue is expected to expose products');
        $this->assertCount($products->count(), $products->all());

        foreach ($products as $product) {
            $this->assertNotSame('', $product->productCode);
            $this->assertNotSame('', $product->name);
            $this->assertGreaterThanOrEqual(0.0, $product->price);
        }
    }
}

/*
  ^ Amilon\Dto\Response\ProductDto^ {#472
  +productCode: "fe3d038b-979f-f011-aa09-005056841cb3"
  +merchantCode: "875196f7-5e79-4e6d-8f8f-5e27f8fa2146"
  +name: "IdeaShopping - Gift Card 5,00 €"
  +price: 5.0
  +imageUrl: "https://b2bstg-web.amilon.eu/b2bfiles/products/05acd668-9dde-4f37-a30d-32ec50c88569/logo/3fdd7b13a94748d684d9ea01fdcd7ebe.png"
  +active: true
  +visible: true
}
 */
