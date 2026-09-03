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

use Amilon\Dto\Response\MerchantContentDto;
use Amilon\Enum\CountryEnum;
use Amilon\Tests\Integration\AbstractIntegrationTestCase;

/**
 * Exercises {@see \Amilon\Service\AmilonClient::getDenominations()} and
 * {@see \Amilon\Service\AmilonClient::getDenominationsComplete()} against the real
 * Amilon STAGING catalogue.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class GetDenominationsIntegrationTest extends AbstractIntegrationTestCase
{
    public function testItListsDenominationsForItaly(): void
    {
        $merchants = $this->liveStagingClient()->getDenominations(CountryEnum::IT);

        $this->assertFalse($merchants->isEmpty(), 'the STAGING IT catalogue is expected to expose denominations');
        $this->assertCount($merchants->count(), $merchants->all());

        foreach ($merchants as $merchant) {
            $this->assertNotSame('', $merchant->code);
            $this->assertNotSame('', $merchant->name);
            $this->assertNotInstanceOf(MerchantContentDto::class, $merchant->extendedContent);

            foreach ($merchant->denominations as $denomination) {
                $this->assertNotSame('', $denomination->code);
                $this->assertTrue(
                    $denomination->isFixed() || $denomination->isVariable() || $denomination->hasContractPriceOverride()
                    || [] === $denomination->prices,
                    'a denomination should fall into one of the migration-guide shapes',
                );
            }
        }
    }

    public function testCompleteAddsTheExtendedContentBlock(): void
    {
        $merchants = $this->liveStagingClient()->getDenominationsComplete(CountryEnum::IT);

        $this->assertFalse($merchants->isEmpty());

        foreach ($merchants as $merchant) {
            $this->assertInstanceOf(MerchantContentDto::class, $merchant->extendedContent, 'complete mode should carry the merchant content block');
        }
    }
}

/*

 * Amilon\Dto\Response\MerchantDenominationsDto^ {#482
  +code: "f72c8dc7-8feb-4dad-bf66-39c8ed238a2b"
  +country: "Spain"
  +countryIsoAlpha3: "ESP"
  +name: "Carrefour"
  +shortDescription: "Carrefour es una cadena de distribución multinacional de origen francés. Está considerado como el primer grupo europeo y en España cuenta con más de 200 hipermercados, más de 100 supermercados Carrefour Market, más de 800 supermercados Carrefour Express, más de 140 Estaciones de Servicio.En sus supermercados, ofrecemos un surtido de más de 20.000 artículos de productos frescos, alimentación, bebidas, congelados, droguería, perfumería, farmacia, etc."
  +longDescription: """
    <p>Carrefour es una cadena de distribuci&oacute;n multinacional de origen franc&eacute;s. Est&aacute; considerado como el primer grupo europeo y en Espa&ntilde;a cuenta con m&aacute;s de 200 hipermercados, m&aacute;s de 100 supermercados Carrefour Market, m&aacute;s de 800 supermercados Carrefour Express, m&aacute;s de 140 Estaciones de Servicio.</p>\r\n
    \r\n
    <p>Somos especialistas en electr&oacute;nica de consumo, inform&aacute;tica, moda, deportes, electrodom&eacute;sticos, juguetes, etc. Tenemos m&aacute;s de 400.000 productos a su servicio y cada d&iacute;a ampliamos un poco m&aacute;s la gama de productos que ofrecemos para darle un mejor servicio. En sus supermercados, ofrecemos un surtido de m&aacute;s de 20.000 art&iacute;culos de productos frescos, alimentaci&oacute;n, bebidas, congelados, droguer&iacute;a, perfumer&iacute;a, farmacia, etc. con la garant&iacute;a y frescura de Carrefour.</p>\r\n
    \r\n
    <p>Tarjeta para adquirir productos en culquiera de los hipermercados y supermercados Carrefour radicados en espana contra el saldo previamente cargado, queda excluida expresamente su utilizaci&oacute;n para la adquisici&oacute;n de otros medio de pago, tales como tarjeta prepago o similares.</p>\r\n
    \r\n
    <p>Las compras realizadas reducir&aacute;n&nbsp; en el mismo importe del saldo disponible en la Tarjeta.&nbsp;</p>\r\n
    \r\n
    <p>En caso de p&eacute;rdida o robo de la tarjeta no ser&aacute; reemplazada por otra nueva, ni restituido el saldo no dispuesto.</p>\r\n
    \r\n
    <p>Agotado su saldo, la Tarjeta pasar&aacute; a ser propiedad de Carrefour.&nbsp;</p>\r\n
    \r\n
    <p>El saldo disponible y las compras realizadas, pueden ser comprobadas llamando a Atenci&oacute;n al Cliente (914 908 900) o en cualquiera de los hipermercados Carrefour.</p>\r\n
    \r\n
    <p>Tarjeta no canjeable por dinero.&nbsp;</p>\r\n
    \r\n
    <p>La Tarjeta Regalo Carrefour no podr&aacute; ser utilizada publicitariamente por terceros para fines promocionales, sin la autorizaci&oacute;n expresa de centros Comerciales Carrefour, S.A.</p>\r\n
    \r\n
    <p>INSTRUCCIONES:</p>\r\n
    \r\n
    <p>Imprime o descarga la tarjeta en tu movil.</p>\r\n
    \r\n
    <p>Elige tu tienda carrefour.</p>\r\n
    \r\n
    <p><a href="http://www.carrefour.es/tiendas-carrefour">www.carrefour.es/tiendas-carrefour</a></p>\r\n
    \r\n
    <p>Presenta tu tarjeta impresa o en tu movil en la caja.</p>\r\n
    \r\n
    <p>Valida para varios usos. Puedes utilizarla hasta agotar el saldo de tu tarjeta.</p>\r\n
    \r\n
    <p>No valida para servicios Carrefour (Viajes, Estaciones de servicios, Seguros, etc.) compras online, tiendas ubicadas en E.S. Cepsa, en cajas de autocobro y Scan&amp;go.</p>\r\n
    \r\n
    <p>Escribenos a&nbsp;<a href="mailto:clientes_carrefour.es@carrefour.com">clientes_carrefour.es@carrefour.com</a>.</p>\r\n
    \r\n
    <p>Lunes a Domingo de 9:00h. a 22:00h 914908800.</p>
    """
  +imageUrl: "https://eurob2b.amilon.eu/b2bfiles/retailers/f72c8dc7-8feb-4dad-bf66-39c8ed238a2b/logo/a6fd150ccbdb435caa839f3e797a629f.png"
  +slug: "carrefour-esp"
  +currency: "Euro"
  +currencySymbol: "€"
  +rebateTypeName: "Sconto fisso per Retailer"
  +vatValue: 0.0
  +vatValueName: "FC IVA art. 6-quater"
  +denominations: array:7 [
    0 => Amilon\Dto\Response\DenominationDto^ {#480
      +code: "911d5af7-419b-ed11-b820-005056a53626"
      +activationDate: DateTimeImmutable @1674497920 {#470
        date: 2023-01-23 18:18:40.0 UTC (+00:00)
      }
      +imageUrl: "https://eurob2b.amilon.eu/b2bfiles/products/8f42058d-64b2-4a98-a5d3-b35cb5d3ce03/logo/d1ded42006514f609a06b5a063328dab.png"
      +rangeMin: null
      +rangeMax: null
      +step: null
      +discountValue: 0.01
      +prices: array:1 [
        0 => Amilon\Dto\Response\DenominationPriceDto^ {#478
          +price: 20.0
          +netPrice: 19.8
        }
      ]
    }
    1 => Amilon\Dto\Response\DenominationDto^ {#479
      +code: "951d5af7-419b-ed11-b820-005056a53626"
      +activationDate: DateTimeImmutable @1674497920 {#466
        date: 2023-01-23 18:18:40.0 UTC (+00:00)
      }
      +imageUrl: "https://eurob2b.amilon.eu/b2bfiles/products/1fd23681-0fb8-4aca-9f02-568299964f49/logo/8ec7976218ab447a8af8345c7557a7b9.png"
      +rangeMin: null
      +rangeMax: null
      +step: null
      +discountValue: 0.01
      +prices: array:1 [
        0 => Amilon\Dto\Response\DenominationPriceDto^ {#465
          +price: 100.0
          +netPrice: 99.0
        }
      ]
    }
    2 => Amilon\Dto\Response\DenominationDto^ {#476
      +code: "991d5af7-419b-ed11-b820-005056a53626"
      +activationDate: DateTimeImmutable @1674497920 {#464
        date: 2023-01-23 18:18:40.0 UTC (+00:00)
      }
      +imageUrl: "https://eurob2b.amilon.eu/b2bfiles/products/bb2bd49a-0ec5-4d39-bcaf-dc1da538ab53/logo/4234082166934256943dc846c7d0c4b5.png"
      +rangeMin: null
      +rangeMax: null
      +step: null
      +discountValue: 0.01
      +prices: array:1 [
        0 => Amilon\Dto\Response\DenominationPriceDto^ {#471
          +price: 10.0
          +netPrice: 9.9
        }
      ]
    }
    3 => Amilon\Dto\Response\DenominationDto^ {#477
      +code: "9d1d5af7-419b-ed11-b820-005056a53626"
      +activationDate: DateTimeImmutable @1674497920 {#469
        date: 2023-01-23 18:18:40.0 UTC (+00:00)
      }
      +imageUrl: "https://eurob2b.amilon.eu/b2bfiles/products/e4847e99-7bd1-4315-bc5d-d28706b3fba1/logo/6a58c0aad4434f8fb31d3ed356aba23a.png"
      +rangeMin: null
      +rangeMax: null
      +step: null
      +discountValue: 0.01
      +prices: array:1 [
        0 => Amilon\Dto\Response\DenominationPriceDto^ {#468
          +price: 25.0
          +netPrice: 24.75
        }
      ]
    }
    4 => Amilon\Dto\Response\DenominationDto^ {#463
      +code: "a11d5af7-419b-ed11-b820-005056a53626"
      +activationDate: DateTimeImmutable @1674497920 {#462
        date: 2023-01-23 18:18:40.0 UTC (+00:00)
      }
      +imageUrl: "https://eurob2b.amilon.eu/b2bfiles/products/960b9d02-57ea-411a-a9fe-c0bafa1af179/logo/c073f1946dae42f29b093b55cbb1d976.png"
      +rangeMin: null
      +rangeMax: null
      +step: null
      +discountValue: 0.01
      +prices: array:1 [
        0 => Amilon\Dto\Response\DenominationPriceDto^ {#461
          +price: 50.0
          +netPrice: 49.5
        }
      ]
    }
    5 => Amilon\Dto\Response\DenominationDto^ {#460
      +code: "a51d5af7-419b-ed11-b820-005056a53626"
      +activationDate: DateTimeImmutable @1674497920 {#459
        date: 2023-01-23 18:18:40.0 UTC (+00:00)
      }
      +imageUrl: "https://eurob2b.amilon.eu/b2bfiles/products/06d2979c-2f25-4c6d-b781-0d90e289095c/logo/627e05c48dab4342945ede7e77754c3b.png"
      +rangeMin: null
      +rangeMax: null
      +step: null
      +discountValue: 0.01
      +prices: array:1 [
        0 => Amilon\Dto\Response\DenominationPriceDto^ {#458
          +price: 5.0
          +netPrice: 4.95
        }
      ]
    }
    6 => Amilon\Dto\Response\DenominationDto^ {#457
      +code: "809f8653-eda6-429f-a26a-c0d7afd234e6"
      +activationDate: DateTimeImmutable @1734630935 {#456
        date: 2024-12-19 17:55:35.0 UTC (+00:00)
      }
      +imageUrl: "https://eurob2b.amilon.eu/b2bfiles/products/809f8653-eda6-429f-a26a-c0d7afd234e6/logo/e1d27542a78c46e389d2c834e984b977.png"
      +rangeMin: null
      +rangeMax: null
      +step: null
      +discountValue: 0.01
      +prices: array:1 [
        0 => Amilon\Dto\Response\DenominationPriceDto^ {#455
          +price: 150.0
          +netPrice: 148.5
        }
      ]
    }
  ]
  +extendedContent: null
}

 */
