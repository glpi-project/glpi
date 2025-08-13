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

use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use Safe\DateTime;

class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null): AccessTokenEntityInterface
    {
        $token = new AccessToken();
        $token->setClient($clientEntity);
        if ($userIdentifier !== null) {
            $token->setUserIdentifier($userIdentifier);
        }
        foreach ($scopes as $scope) {
            $token->addScope($scope);
        }
        return $token;
    }

    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void
    {
        global $DB;

        $DB->insert('glpi_oauth_access_tokens', [
            'identifier' => $accessTokenEntity->getIdentifier(),
            'client' => $accessTokenEntity->getClient()->getIdentifier(),
            'date_expiration' => $accessTokenEntity->getExpiryDateTime()->format('Y-m-d H:i:s'),
            'user_identifier' => $accessTokenEntity->getUserIdentifier(),
            'scopes' => exportArrayToDB($accessTokenEntity->getScopes()),
        ]);
    }

    public function revokeAccessToken($tokenId): void
    {
        global $DB;

        $DB->delete('glpi_oauth_access_tokens', ['identifier' => $tokenId]);
    }

    public function isAccessTokenRevoked($tokenId): bool
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => ['identifier', 'date_expiration'],
            'FROM' => 'glpi_oauth_access_tokens',
            'WHERE' => [
                'identifier' => $tokenId,
            ],
        ]);
        if (count($iterator) === 0) {
            return true;
        }
        // Check if the token is expired
        $expiration = $iterator->current()['date_expiration'];
        return (new DateTime($expiration)) < new DateTime();
    }
}
