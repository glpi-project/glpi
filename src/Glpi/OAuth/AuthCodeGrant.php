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
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

use function Safe\json_decode;

final class AuthCodeGrant extends \League\OAuth2\Server\Grant\AuthCodeGrant
{
    use LinkedLoginSessionTrait;

    public function respondToAccessTokenRequest(
        ServerRequestInterface $request,
        ResponseTypeInterface $responseType,
        DateInterval $accessTokenTTL
    ): ResponseTypeInterface {
        $this->linked_login_session_uid = null;
        $encrypted_auth_code = $this->getRequestParameter('code', $request);
        if ($encrypted_auth_code !== null) {
            try {
                $auth_code_payload = json_decode($this->decrypt($encrypted_auth_code), true);
                if (is_array($auth_code_payload) && is_string($auth_code_payload['auth_code_id'] ?? null)) {
                    $this->linked_login_session_uid = (new AuthCodeRepository())->getLinkedLoginSessionUID($auth_code_payload['auth_code_id']);
                }
            } catch (Throwable) {
                // Invalid codes are rejected by the parent implementation
            }
        }

        return parent::respondToAccessTokenRequest($request, $responseType, $accessTokenTTL);
    }
}
