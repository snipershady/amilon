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

namespace Amilon\Tests\Unit\Configuration;

use Amilon\Configuration\Configuration;
use Amilon\Exception\AmilonExceptionInterface;
use Amilon\Exception\InvalidConfigurationException;
use Amilon\Tests\AbstractTestCase;

/**
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class ConfigurationTest extends AbstractTestCase
{
    /**
     * @return array<string, string>
     */
    private function validValues(): array
    {
        return [
            'username' => 'laforgiaWSSTG2020',
            'password' => 'rYL8-T5rR5Wd!#6NV2HV?P',
            'clientId' => 'b2bwsuserwebapi',
            'clientSecret' => 'ad582dc299',
            'authDomain' => 'https://b2bstg-sso.amilon.eu/',
            'webDomain' => 'https://b2bstg-webapi.amilon.eu/b2bwebapi/v1/',
            'contractId' => '7fb1c5d3-423e-4b0c-b8da-a3ed94ae6392',
        ];
    }

    public function testFromArrayKeepsEveryValue(): void
    {
        $config = Configuration::fromArray($this->validValues());

        $this->assertSame('laforgiaWSSTG2020', $config->username);
        $this->assertSame('rYL8-T5rR5Wd!#6NV2HV?P', $config->password);
        $this->assertSame('b2bwsuserwebapi', $config->clientId);
        $this->assertSame('ad582dc299', $config->clientSecret);
        $this->assertSame('https://b2bstg-sso.amilon.eu/', $config->authDomain);
        $this->assertSame('https://b2bstg-webapi.amilon.eu/b2bwebapi/v1/', $config->webDomain);
        $this->assertSame('7fb1c5d3-423e-4b0c-b8da-a3ed94ae6392', $config->contractId);
    }

    public function testItTrimsSurroundingWhitespace(): void
    {
        $values = $this->validValues();
        $values['username'] = "  laforgiaWSSTG2020\n";

        $this->assertSame('laforgiaWSSTG2020', Configuration::fromArray($values)->username);
    }

    public function testItAddsTheMissingTrailingSlashToEndpoints(): void
    {
        $values = $this->validValues();
        $values['authDomain'] = 'https://b2bstg-sso.amilon.eu';
        $values['webDomain'] = 'https://b2bstg-webapi.amilon.eu/b2bwebapi/v1';

        $config = Configuration::fromArray($values);

        $this->assertSame('https://b2bstg-sso.amilon.eu/', $config->authDomain);
        $this->assertSame('https://b2bstg-webapi.amilon.eu/b2bwebapi/v1/', $config->webDomain);
    }

    public function testItCollapsesRepeatedTrailingSlashesOnEndpoints(): void
    {
        $values = $this->validValues();
        $values['authDomain'] = 'https://b2bstg-sso.amilon.eu///';

        $this->assertSame('https://b2bstg-sso.amilon.eu/', Configuration::fromArray($values)->authDomain);
    }

    public function testItLowercasesTheContractUuid(): void
    {
        $values = $this->validValues();
        $values['contractId'] = '7FB1C5D3-423E-4B0C-B8DA-A3ED94AE6392';

        $this->assertSame('7fb1c5d3-423e-4b0c-b8da-a3ed94ae6392', Configuration::fromArray($values)->contractId);
    }

    public function testAMissingValueIsRejected(): void
    {
        $values = $this->validValues();
        unset($values['clientSecret']);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('"clientSecret"');

        Configuration::fromArray($values);
    }

    public function testABlankValueIsRejected(): void
    {
        $values = $this->validValues();
        $values['username'] = "   \t  ";

        $this->expectException(InvalidConfigurationException::class);

        Configuration::fromArray($values);
    }

    public function testANonAbsoluteEndpointIsRejected(): void
    {
        $values = $this->validValues();
        $values['webDomain'] = 'b2bstg-webapi.amilon.eu/b2bwebapi/v1/';

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('absolute http(s) URL');

        Configuration::fromArray($values);
    }

    public function testAContractIdThatIsNotAUuidIsRejected(): void
    {
        $values = $this->validValues();
        $values['contractId'] = 'not-a-uuid';

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('must be a UUID');

        Configuration::fromArray($values);
    }

    public function testEveryFailureIsCatchableThroughTheLibraryMarker(): void
    {
        $this->expectException(AmilonExceptionInterface::class);

        Configuration::fromArray([]);
    }

    public function testFromEnvironmentReadsTheAmilonPrefixedKeys(): void
    {
        $environment = [
            'AMILON_USERNAME' => 'laforgiaWSSTG2020',
            'AMILON_PASSWORD' => 'rYL8-T5rR5Wd!#6NV2HV?P',
            'AMILON_CLIENT_ID' => 'b2bwsuserwebapi',
            'AMILON_CLIENT_SECRET' => 'ad582dc299',
            'AMILON_AUTH_DOMAIN' => 'https://b2bstg-sso.amilon.eu/',
            'AMILON_WEB_DOMAIN' => 'https://b2bstg-webapi.amilon.eu/b2bwebapi/v1/',
            'AMILON_CONTRACT_ID' => '7fb1c5d3-423e-4b0c-b8da-a3ed94ae6392',
            'UNRELATED' => 'ignored',
        ];

        $config = Configuration::fromEnvironment($environment);

        $this->assertSame('laforgiaWSSTG2020', $config->username);
        $this->assertSame('https://b2bstg-webapi.amilon.eu/b2bwebapi/v1/', $config->webDomain);
        $this->assertSame('7fb1c5d3-423e-4b0c-b8da-a3ed94ae6392', $config->contractId);
    }
}
