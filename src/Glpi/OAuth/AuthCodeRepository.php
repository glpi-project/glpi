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

use Glpi\DBAL\QueryFunction;
use Glpi\Toolbox\IPUtilities;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use Session;

class AuthCodeRepository implements AuthCodeRepositoryInterface
{
    /**
     * Session key storing the identifier of the client the user was asked to log in for, during the authorization code flow.
     */
    public const AUTHORIZE_LOGIN_CLIENT_SESSION_KEY = 'glpi_oauth_authorize_login_client';

    public function getNewAuthCode(): AuthCode
    {
        $code = new AuthCode();
        $code->setIdentifier(bin2hex(random_bytes(Server::AUTH_CODE_LENGTH_BYTES)));
        return $code;
    }

    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity): void
    {
        global $DB;

        // clean expired codes
        $DB->delete('glpi_oauth_auth_codes', [
            'date_expiration' => ['<', date('Y-m-d H:i:s')],
        ]);

        $DB->insert('glpi_oauth_auth_codes', [
            'identifier' => $authCodeEntity->getIdentifier(),
            'client' => $authCodeEntity->getClient()->getIdentifier(),
            'date_expiration' => $authCodeEntity->getExpiryDateTime()->format('Y-m-d H:i:s'),
            'user_identifier' => $authCodeEntity->getUserIdentifier(),
            'scopes' => exportArrayToDB($authCodeEntity->getScopes()),
            'ip_address' => IPUtilities::getClientIP(),
            'login_session_uid' => $this->getLoginSessionUIDToLink($authCodeEntity->getClient()->getIdentifier()),
        ]);
    }

    /**
     * Get the UID of the login session the tokens issued for the given client should be linked to.
     *
     * Only a login session opened to authorize this client is linked, so it is shown as a single session with the tokens.
     * A browser session the user already had before starting the authorization stays a separate session.
     *
     * @param string $client_identifier
     *
     * @return string|null
     */
    private function getLoginSessionUIDToLink(string $client_identifier): ?string
    {
        if (($_SESSION[self::AUTHORIZE_LOGIN_CLIENT_SESSION_KEY] ?? null) !== $client_identifier) {
            return null;
        }
        return Session::getLoginSessionUID();
    }

    /**
     * Get the UID of the login session the given authorization code was granted from.
     *
     * @param string $codeId
     *
     * @return string|null
     */
    public function getLinkedLoginSessionUID(string $codeId): ?string
    {
        global $DB;

        $row = $DB->request([
            'SELECT' => ['login_session_uid'],
            'FROM' => 'glpi_oauth_auth_codes',
            'WHERE' => [
                'identifier' => $codeId,
                'NOT' => ['login_session_uid' => null],
            ],
            'LIMIT' => 1,
        ])->current();

        return $row['login_session_uid'] ?? null;
    }

    /**
     * @param string $codeId
     *
     * @return void
     */
    public function revokeAuthCode($codeId): void
    {
        global $DB;

        $DB->delete('glpi_oauth_auth_codes', ['identifier' => $codeId]);
    }

    /**
     * Revoke all authorization codes issued for the given client.
     *
     * @param string $clientIdentifier
     *
     * @return void
     */
    public function revokeByClient(string $clientIdentifier): void
    {
        global $DB;

        $DB->delete('glpi_oauth_auth_codes', ['client' => $clientIdentifier]);
    }

    /**
     * @param string $codeId
     *
     * @return bool
     */
    public function isAuthCodeRevoked($codeId): bool
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => 'identifier',
            'FROM' => 'glpi_oauth_auth_codes',
            'WHERE' => [
                'identifier' => $codeId,
                'date_expiration' => ['>', QueryFunction::now()],
            ],
        ]);
        return $iterator->count() === 0;
    }
}
