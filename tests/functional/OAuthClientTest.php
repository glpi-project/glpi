<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace tests\units;

use Glpi\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class OAuthClientTest extends DbTestCase
{
    public static function validateAllowedIPsProvider()
    {
        return [
            [null, true],
            ['', true],
            ['::1', true],
            ['127.0.0.1,::1', true],
            ['127.0.0.1, ::1', true],
            ['127.0.0.1, 10.10.13.0/24', true],
            ['10.10.13.0/0', false],
            ['10.10.13.0/1', true],
            ['10.10.13.0/128', false],
            ['::1/0', false],
            ['::1/1', true],
            ['::1/129', false],
            ['::1/128', true],
            ['2001:4860:4860::8888/32', true],
        ];
    }

    #[DataProvider('validateAllowedIPsProvider')]
    public function testValidateAllowedIPs($allowed_ips, $is_valid)
    {
        $client = new \OAuthClient();
        $add_result = $client->prepareInputForAdd([
            'allowed_ips' => $allowed_ips,
        ]);
        if (!$is_valid) {
            $this->assertFalse($add_result);
            $this->hasSessionMessages(ERROR, ['Invalid IP address or CIDR range']);
        } else {
            $this->assertSame($allowed_ips, $add_result['allowed_ips']);
        }

        $update_result = $client->prepareInputForUpdate([
            'allowed_ips' => $allowed_ips,
        ]);
        if (!$is_valid) {
            $this->assertFalse($update_result);
            $this->hasSessionMessages(ERROR, ['Invalid IP address or CIDR range']);
        } else {
            $this->assertSame($allowed_ips, $update_result['allowed_ips']);
        }
    }

    public static function publicClientGrantsProvider(): array
    {
        return [
            [true, ['password', 'client_credentials', 'authorization_code'], true],
            [false, ['authorization_code'], true],
            [false, [], true],
            [false, ['password'], false],
            [false, ['client_credentials', 'authorization_code'], false],
        ];
    }

    /**
     * @param string[] $grants
     */
    #[DataProvider('publicClientGrantsProvider')]
    public function testPublicClientGrants(bool $is_confidential, array $grants, bool $is_valid): void
    {
        $client = new \OAuthClient();
        $add_result = $client->prepareInputForAdd([
            'is_confidential' => (int) $is_confidential,
            'grants' => $grants,
        ]);
        if (!$is_valid) {
            $this->assertFalse($add_result);
            $this->hasSessionMessages(ERROR, ['Public clients may only use the authorization code grant']);
        } else {
            $this->assertIsArray($add_result);
        }

        // Switching an existing client to public must also validate its stored grants
        $client = $this->createItem(\OAuthClient::class, [
            'name' => __FUNCTION__,
            'grants' => $grants,
        ], ['grants']);
        $update_result = $client->prepareInputForUpdate([
            'is_confidential' => (int) $is_confidential,
        ]);
        if (!$is_valid) {
            $this->assertFalse($update_result);
            $this->hasSessionMessages(ERROR, ['Public clients may only use the authorization code grant']);
        } else {
            $this->assertIsArray($update_result);
        }
    }

    public static function publicClientGrantsInputProvider(): array
    {
        return [
            ['', true],
            ['[]', true],
            ['["authorization_code"]', true],
            ['{not json', false],
            ['"authorization_code"', false],
            ['123', false],
            ['{"a":"authorization_code"}', false],
            ['["password"]', false],
            [[['authorization_code']], false],
            [42, false],
        ];
    }

    #[DataProvider('publicClientGrantsInputProvider')]
    public function testPublicClientGrantsInput(mixed $grants, bool $is_valid): void
    {
        $client = new \OAuthClient();
        $add_result = $client->prepareInputForAdd([
            'is_confidential' => 0,
            'grants' => $grants,
        ]);
        if (!$is_valid) {
            $this->assertFalse($add_result);
            $this->hasSessionMessages(ERROR, ['Public clients may only use the authorization code grant']);
        } else {
            $this->assertIsArray($add_result);
        }
    }

    public function testUpdateToPublicClientWithMalformedGrants(): void
    {
        $client = $this->createItem(\OAuthClient::class, [
            'name' => __FUNCTION__,
            'grants' => ['authorization_code'],
        ], ['grants']);
        $this->assertFalse($client->prepareInputForUpdate([
            'is_confidential' => 0,
            'grants' => '{not json',
        ]));
        $this->hasSessionMessages(ERROR, ['Public clients may only use the authorization code grant']);
    }

    public function testPurgeDeletesAssociatedTokens(): void
    {
        global $DB;

        $client = $this->createItem(\OAuthClient::class, ['name' => 'Test client']);
        $identifier = $client->fields['identifier'];

        $this->assertTrue($DB->insert('glpi_oauth_access_tokens', [
            'identifier' => 'access_token_purge_test',
            'client' => $identifier,
            'date_expiration' => date('Y-m-d H:i:s', time() + 3600),
        ]));
        $this->assertTrue($DB->insert('glpi_oauth_refresh_tokens', [
            'identifier' => 'refresh_token_purge_test',
            'access_token' => 'access_token_purge_test',
            'date_expiration' => date('Y-m-d H:i:s', time() + 3600),
        ]));
        $this->assertTrue($DB->insert('glpi_oauth_auth_codes', [
            'identifier' => 'auth_code_purge_test',
            'client' => $identifier,
            'date_expiration' => date('Y-m-d H:i:s', time() + 3600),
        ]));
        $this->assertTrue($DB->insert('glpi_oauth_access_tokens', [
            'identifier' => 'access_token_other_client',
            'client' => 'other_client_identifier',
            'date_expiration' => date('Y-m-d H:i:s', time() + 3600),
        ]));

        $client->delete(['id' => $client->getID(), 'purge' => 1]);

        $this->assertCount(0, $DB->request([
            'FROM'  => 'glpi_oauth_access_tokens',
            'WHERE' => ['client' => $identifier],
        ]));
        $this->assertCount(0, $DB->request([
            'FROM'  => 'glpi_oauth_refresh_tokens',
            'WHERE' => ['identifier' => 'refresh_token_purge_test'],
        ]));
        $this->assertCount(0, $DB->request([
            'FROM'  => 'glpi_oauth_auth_codes',
            'WHERE' => ['client' => $identifier],
        ]));
        $this->assertCount(1, $DB->request([
            'FROM'  => 'glpi_oauth_access_tokens',
            'WHERE' => ['identifier' => 'access_token_other_client'],
        ]));
    }
}
