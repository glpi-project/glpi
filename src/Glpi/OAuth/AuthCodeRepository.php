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

use Glpi\DBAL\QueryFunction;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;

class AuthCodeRepository implements AuthCodeRepositoryInterface
{
    public function getNewAuthCode(): AuthCode
    {
        $code = new AuthCode();
        $code->setIdentifier(bin2hex(random_bytes(Server::AUTH_CODE_LENGTH_BYTES)));
        return $code;
    }

    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity): void
    {
        global $DB;

        $DB->insert('glpi_oauth_auth_codes', [
            'identifier' => $authCodeEntity->getIdentifier(),
            'client' => $authCodeEntity->getClient()->getIdentifier(),
            'date_expiration' => $authCodeEntity->getExpiryDateTime()->format('Y-m-d H:i:s'),
            'user_identifier' => $authCodeEntity->getUserIdentifier(),
            'scopes' => exportArrayToDB($authCodeEntity->getScopes()),
        ]);
    }

    public function revokeAuthCode($codeId): void
    {
        global $DB;

        $DB->delete('glpi_oauth_auth_codes', ['identifier' => $codeId]);
    }

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
