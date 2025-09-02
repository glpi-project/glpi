<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

namespace Glpi\OAuth;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use OAuthClient;

use function Safe\json_decode;

class ClientRepository implements ClientRepositoryInterface
{
    public function getClientEntity($clientIdentifier): ?ClientEntityInterface
    {
        global $DB;

        $iterator = $DB->request([
            'FROM'   => 'glpi_oauthclients',
            'WHERE'  => [
                'identifier' => $clientIdentifier,
            ],
        ]);

        if (count($iterator) === 1) {
            $client = new Client();
            $client->setIdentifier($clientIdentifier);
            $client->setName($iterator->current()['name']);
            $client->setRedirectUri(json_decode($iterator->current()['redirect_uri'], true) ?? []);
            return $client;
        }

        return null;
    }

    /**
     * @throws OAuthServerException If the requested grant type is not allowed for the client
     */
    public function validateClient($clientIdentifier, $clientSecret, $grantType): bool
    {
        $client = new OAuthClient();
        $client->getFromDBByCrit([
            'identifier' => $clientIdentifier,
        ]);

        if ($client->fields['secret'] !== $clientSecret) {
            return false;
        }

        $global_grants = ['refresh_token'];
        $allowed_grants = array_merge($client->fields['grants'], $global_grants);
        if (!in_array($grantType, $allowed_grants, true)) {
            throw OAuthServerException::unauthorizedClient();
        }
        return true;
    }
}
