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

use Glpi\OAuth\ClientRepository;
use Glpi\Tests\DbTestCase;
use GLPIKey;
use OAuthClient;

class ClientRepositoryTest extends DbTestCase
{
    public function testActiveClientIsAuthenticated(): void
    {
        $client = $this->createItem(OAuthClient::class, [
            'name'      => __FUNCTION__,
            'is_active' => 1,
            'grants'    => ['client_credentials'],
        ], ['grants']);

        $identifier = $client->fields['identifier'];
        $secret = (new GLPIKey())->decrypt($client->fields['secret']);

        $repository = new ClientRepository();
        $this->assertNotNull($repository->getClientEntity($identifier));
        $this->assertTrue($repository->validateClient($identifier, $secret, 'client_credentials'));
    }

    public function testInactiveClientCannotBeAuthenticated(): void
    {
        $client = $this->createItem(OAuthClient::class, [
            'name'      => __FUNCTION__,
            'is_active' => 0,
            'grants'    => ['client_credentials'],
        ], ['grants']);

        $identifier = $client->fields['identifier'];
        $secret = (new GLPIKey())->decrypt($client->fields['secret']);

        $repository = new ClientRepository();
        $this->assertNull($repository->getClientEntity($identifier));
        $this->assertFalse($repository->validateClient($identifier, $secret, 'client_credentials'));
    }

    public function testDeactivatedClientCannotBeAuthenticated(): void
    {
        $client = $this->createItem(OAuthClient::class, [
            'name'      => __FUNCTION__,
            'is_active' => 1,
            'grants'    => ['client_credentials'],
        ], ['grants']);

        $identifier = $client->fields['identifier'];
        $secret = (new GLPIKey())->decrypt($client->fields['secret']);

        $repository = new ClientRepository();
        $this->assertTrue($repository->validateClient($identifier, $secret, 'client_credentials'));

        $this->updateItem(OAuthClient::class, $client->getID(), ['is_active' => 0], ['grants']);

        $this->assertNull($repository->getClientEntity($identifier));
        $this->assertFalse($repository->validateClient($identifier, $secret, 'client_credentials'));
    }
}
