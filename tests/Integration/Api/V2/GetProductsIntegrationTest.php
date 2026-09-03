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

namespace Amilon\Tests\Integration\Api\V2;

use Amilon\Dto\Response\MerchantDenominationsDto;
use Amilon\Enum\CountryEnum;
use Amilon\Tests\Integration\AbstractIntegrationTestCase;

/**
 * Exercises {@see \Amilon\Service\AmilonClient::getProducts()} — the V1-compatible
 * flat projection of the V2 `denominations` catalogue — against the real Amilon
 * STAGING API.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class GetProductsIntegrationTest extends AbstractIntegrationTestCase
{
    public function testItListsProductsForItalyInTheV1Shape(): void
    {
        $client = $this->liveStagingClient();

        $products = $client->getProducts(CountryEnum::IT);

        $this->assertFalse($products->isEmpty(), 'the STAGING IT catalogue is expected to expose products');
        $this->assertCount($products->count(), $products->all());

        foreach ($products as $product) {
            $this->assertNotSame('', $product->productCode);
            $this->assertNotSame('', $product->name);
            $this->assertGreaterThanOrEqual(0.0, $product->price);
            $this->assertTrue($product->active);
            $this->assertTrue($product->visible);
        }
    }

    public function testFlattenedRowsStayLinkedToTheDenominationMerchants(): void
    {
        $client = $this->liveStagingClient();

        $merchantCodes = array_map(
            static fn (MerchantDenominationsDto $merchantDenominationsDto): string => $merchantDenominationsDto->code,
            $client->getDenominations(CountryEnum::IT)->all(),
        );
        $products = $client->getProducts(CountryEnum::IT);

        $this->assertNotSame([], $products->all());

        foreach ($products as $product) {
            $this->assertContains(
                $product->merchantCode,
                $merchantCodes,
                'every product row must carry a merchantCode present in getDenominations()',
            );
        }
    }
}

/*
 Amilon\Dto\Response\ProductDto^ {#1633
  +productCode: "911d5af7-419b-ed11-b820-005056a53626"
  +merchantCode: "f72c8dc7-8feb-4dad-bf66-39c8ed238a2b"
  +name: "Carrefour - 20,00 €"
  +price: 20.0
  +imageUrl: "https://eurob2b.amilon.eu/b2bfiles/products/8f42058d-64b2-4a98-a5d3-b35cb5d3ce03/logo/d1ded42006514f609a06b5a063328dab.png"
  +active: true
  +visible: true
  +netPrice: 19.8
  +discountValue: 0.01
  +currency: "Euro"
  +currencySymbol: "€"
  +rangeMin: null
  +rangeMax: null
  +step: null
  +activationDate: DateTimeImmutable @1674497920 {#469
    date: 2023-01-23 18:18:40.0 UTC (+00:00)
  }
  +merchantName: "Carrefour"
  +countryIsoAlpha3: "ESP"
}

 */
