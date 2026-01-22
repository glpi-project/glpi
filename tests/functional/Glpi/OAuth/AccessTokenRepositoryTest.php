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

namespace tests\units\Glpi\OAuth;

use DateTimeImmutable;
use Glpi\OAuth\AccessToken;
use Glpi\OAuth\AccessTokenRepository;
use Glpi\OAuth\Client;
use Glpi\Tests\DbTestCase;

class AccessTokenRepositoryTest extends DbTestCase
{
    public function testPersistNewAccessToken(): void
    {
        global $DB;

        // add some preexisting tokens that are expired and current to test cleanup
        $this->assertTrue($DB->insert('glpi_oauth_access_tokens', [
            'identifier' => 'expired_token',
            'client' => 'client_1',
            'date_expiration' => date('Y-m-d H:i:s', time() - 3600),
        ]));
        $this->assertTrue($DB->insert('glpi_oauth_access_tokens', [
            'identifier' => 'current_token',
            'client' => 'client_1',
            'date_expiration' => date('Y-m-d H:i:s', time() + 3600),
        ]));

        $repo = new AccessTokenRepository();
        $access_token = new AccessToken();
        $client = new Client();
        $client->setIdentifier('client_1');
        $client->setName('client_1');
        $access_token->setIdentifier('access_token_0');
        $access_token->setClient($client);
        $access_token->setExpiryDateTime(new DateTimeImmutable('+1 hour'));
        $repo->persistNewAccessToken($access_token);

        $it = $DB->request([
            'FROM' => 'glpi_oauth_access_tokens',
            'WHERE' => ['identifier' => 'access_token_0'],
        ]);
        $this->assertCount(1, $it);
        $data = $it->current();
        $this->assertNotEmpty($data['date_expiration']);

        // check that expired token is cleaned up
        $it = $DB->request([
            'FROM' => 'glpi_oauth_access_tokens',
            'WHERE' => ['identifier' => 'expired_token'],
        ]);
        $this->assertCount(0, $it);
        // check that current token is not removed
        $it = $DB->request([
            'FROM' => 'glpi_oauth_access_tokens',
            'WHERE' => ['identifier' => 'current_token'],
        ]);
        $this->assertCount(1, $it);
    }

    public function testRevokeAccessToken(): void
    {
        global $DB;

        $this->assertTrue($DB->insert('glpi_oauth_access_tokens', [
            'identifier' => 'access_token_1',
            'client' => 'client_1',
            'date_expiration' => date('Y-m-d H:i:s', time() + 3600),
        ]));
        $repo = new AccessTokenRepository();
        $repo->revokeAccessToken('access_token_1');
        $it = $DB->request([
            'FROM' => 'glpi_oauth_access_tokens',
            'WHERE' => ['identifier' => 'access_token_1'],
        ]);
        $this->assertCount(0, $it);
    }

    public function testIsAccessTokenRevoked(): void
    {
        global $DB;

        $this->assertTrue($DB->insert('glpi_oauth_access_tokens', [
            'identifier' => 'access_token_2',
            'client' => 'client_1',
            'date_expiration' => date('Y-m-d H:i:s', time() + 3600),
        ]));
        $repo = new AccessTokenRepository();
        $this->assertFalse($repo->isAccessTokenRevoked('access_token_2'));
        $repo->revokeAccessToken('access_token_2');
        $this->assertTrue($repo->isAccessTokenRevoked('access_token_2'));
    }
}
