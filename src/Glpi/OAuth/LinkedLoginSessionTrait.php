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

use DateInterval;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;

/**
 * Links the tokens issued by a grant to the login session the authorization was originally granted from.
 * This allows the session tracker to show a single session instead of one for the login and another one for the tokens.
 */
trait LinkedLoginSessionTrait
{
    private ?string $linked_login_session_uid = null;

    protected function issueAccessToken(
        DateInterval $accessTokenTTL,
        ClientEntityInterface $client,
        ?string $userIdentifier,
        array $scopes = []
    ): AccessTokenEntityInterface {
        $access_token = parent::issueAccessToken($accessTokenTTL, $client, $userIdentifier, $scopes);
        if ($this->linked_login_session_uid !== null) {
            (new AccessTokenRepository())->linkLoginSession($access_token->getIdentifier(), $this->linked_login_session_uid);
        }
        return $access_token;
    }

    protected function issueRefreshToken(AccessTokenEntityInterface $accessToken): ?RefreshTokenEntityInterface
    {
        $refresh_token = parent::issueRefreshToken($accessToken);
        if ($refresh_token !== null && $this->linked_login_session_uid !== null) {
            (new RefreshTokenRepository())->linkLoginSession($refresh_token->getIdentifier(), $this->linked_login_session_uid);
        }
        return $refresh_token;
    }
}
