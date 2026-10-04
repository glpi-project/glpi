<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QuerySubQuery;
use Glpi\Toolbox\IPUtilities;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use Ramsey\Uuid\Uuid;
use Safe\DateTime;
use Session;

class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    /**
     * @param ClientEntityInterface $clientEntity
     * @param array $scopes
     * @param ?string $userIdentifier
     *
     * @return AccessTokenEntityInterface
     */
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

        // clean expired tokens
        $DB->delete('glpi_oauth_access_tokens', [
            'date_expiration' => ['<', date('Y-m-d H:i:s')],
        ]);

        $DB->insert('glpi_oauth_access_tokens', [
            'identifier' => $accessTokenEntity->getIdentifier(),
            'client' => $accessTokenEntity->getClient()->getIdentifier(),
            'date_expiration' => $accessTokenEntity->getExpiryDateTime()->format('Y-m-d H:i:s'),
            'user_identifier' => $accessTokenEntity->getUserIdentifier(),
            'scopes' => exportArrayToDB($accessTokenEntity->getScopes()),
            'ip_address' => IPUtilities::getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'uuid' => Uuid::uuid4()->toString(),
        ]);
    }

    /**
     * Link an access token to the login session it was authorized from.
     *
     * @param string $tokenId
     * @param string $login_session_uid
     *
     * @return void
     */
    public function linkLoginSession(string $tokenId, string $login_session_uid): void
    {
        global $DB;

        $DB->update('glpi_oauth_access_tokens', [
            'login_session_uid' => $login_session_uid,
        ], ['identifier' => $tokenId]);
    }

    /**
     * Get the login session UID the access token with the given UUID is linked to.
     *
     * @param string $uuid
     *
     * @return string|null
     */
    public function getLoginSessionUIDByUUID(string $uuid): ?string
    {
        global $DB;

        $row = $DB->request([
            'SELECT' => ['login_session_uid'],
            'FROM' => 'glpi_oauth_access_tokens',
            'WHERE' => ['uuid' => $uuid],
            'LIMIT' => 1,
        ])->current();

        return $row['login_session_uid'] ?? null;
    }

    /**
     * Revoke all access tokens, and their refresh tokens, linked to the given login session.
     *
     * @param string $login_session_uid
     *
     * @return void
     */
    public function revokeByLoginSession(string $login_session_uid): void
    {
        global $DB;

        // Refresh tokens are linked to the session too, in case their access token was already cleaned up after expiring
        $DB->delete('glpi_oauth_refresh_tokens', ['login_session_uid' => $login_session_uid]);
        $this->revokeWithRefreshTokens(['login_session_uid' => $login_session_uid]);
    }

    /**
     * Revoke the access tokens matching the given criteria along with their refresh tokens.
     * Otherwise, the refresh tokens could still be used to get new access tokens.
     *
     * @param array<string, mixed> $where
     *
     * @return void
     */
    private function revokeWithRefreshTokens(array $where): void
    {
        global $DB;

        $DB->delete('glpi_oauth_refresh_tokens', [
            'access_token' => new QuerySubQuery([
                'SELECT' => 'identifier',
                'FROM' => 'glpi_oauth_access_tokens',
                'WHERE' => $where,
            ]),
        ]);
        $DB->delete('glpi_oauth_access_tokens', $where);
    }

    /**
     * @param string $tokenId
     *
     * @return void
     */
    public function revokeAccessToken($tokenId): void
    {
        global $DB;
        $DB->delete('glpi_oauth_access_tokens', ['identifier' => $tokenId]);
    }

    /**
     * Revoke all access tokens issued for the given client.
     *
     * @param string $clientIdentifier
     *
     * @return void
     */
    public function revokeByClient(string $clientIdentifier): void
    {
        global $DB;

        $DB->delete('glpi_oauth_access_tokens', ['client' => $clientIdentifier]);
    }

    /**
     * Revoke access token by its UUID.
     * Useful in cases where the token may be revoked by the frontend where it is not desirable to expose the real access token.
     * @param string $uuid
     * @return void
     */
    public function revokeAccessTokenByUUID(string $uuid): void
    {
        $this->revokeWithRefreshTokens(['uuid' => $uuid]);
    }

    /**
     * Revoke access token by its UUID if it belongs to the current user.
     * @param string $uuid
     * @return void
     * @see AccessTokenRepository::revokeAccessTokenByUUID()
     */
    public function revokeMyAccessTokenByUUID(string $uuid): void
    {
        $this->revokeWithRefreshTokens([
            'uuid' => $uuid,
            'user_identifier' => Session::getLoginUserID(),
        ]);
    }

    /**
     * Revoke all access tokens.
     *
     * @return void
     */
    public function revokeAll(): void
    {
        global $DB;
        $DB->delete('glpi_oauth_access_tokens', [new QueryExpression('true')]);
    }

    /**
     * Revoke all access tokens for a specific user.
     *
     * @param int $users_id
     *
     * @return void
     */
    public function revokeAllForUser(int $users_id): void
    {
        global $DB;
        $DB->delete('glpi_oauth_access_tokens', ['user_identifier' => $users_id]);
    }

    /**
     * @param string $tokenId
     *
     * @return bool
     */
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
