<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
use Glpi\OAuth\RefreshToken;
use Glpi\OAuth\RefreshTokenRepository;

class RefreshTokenRepositoryTest extends \DbTestCase
{
    public function testPersistNewRefreshToken(): void
    {
        global $DB;

        $repo = new RefreshTokenRepository();
        $refresh_token = new RefreshToken();
        $access_token = new AccessToken();
        $refresh_token->setIdentifier('refresh_token_0');
        $access_token->setIdentifier('access_token_0');
        $refresh_token->setAccessToken($access_token);
        $refresh_token->setExpiryDateTime(new DateTimeImmutable('+1 hour'));
        $repo->persistNewRefreshToken($refresh_token);

        $it = $DB->request([
            'FROM' => 'glpi_oauth_refresh_tokens',
            'WHERE' => ['identifier' => 'refresh_token_0'],
        ]);
        $this->assertCount(1, $it);
        $data = $it->current();
        $this->assertEquals('access_token_0', $data['access_token']);
        $this->assertNotEmpty($data['date_expiration']);
    }

    public function testRevokeRefreshToken(): void
    {
        global $DB;

        $this->assertTrue($DB->insert('glpi_oauth_refresh_tokens', [
            'identifier' => 'refresh_token_1',
            'access_token' => 'access_token_1',
            'date_expiration' => date('Y-m-d H:i:s', time() + 3600),
        ]));
        $repo = new RefreshTokenRepository();
        $repo->revokeRefreshToken('refresh_token_1');
        $it = $DB->request([
            'FROM' => 'glpi_oauth_refresh_tokens',
            'WHERE' => ['identifier' => 'refresh_token_1'],
        ]);
        $this->assertCount(0, $it);
    }

    public function testIsRefreshTokenRevoked(): void
    {
        global $DB;

        $this->assertTrue($DB->insert('glpi_oauth_refresh_tokens', [
            'identifier' => 'refresh_token_2',
            'access_token' => 'access_token_2',
            'date_expiration' => date('Y-m-d H:i:s', time() + 3600),
        ]));
        $repo = new RefreshTokenRepository();
        $this->assertFalse($repo->isRefreshTokenRevoked('refresh_token_2'));
        $repo->revokeRefreshToken('refresh_token_2');
        $this->assertTrue($repo->isRefreshTokenRevoked('refresh_token_2'));
    }
}
