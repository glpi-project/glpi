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

use Psr\Http\Message\ServerRequestInterface;

final class RefreshTokenGrant extends \League\OAuth2\Server\Grant\RefreshTokenGrant
{
    use LinkedLoginSessionTrait;

    protected function validateOldRefreshToken(ServerRequestInterface $request, string $clientId): array
    {
        $old_refresh_token = parent::validateOldRefreshToken($request, $clientId);
        // The new tokens remain linked to the same login session as the old ones
        $this->linked_login_session_uid = (new RefreshTokenRepository())->getLinkedLoginSessionUID($old_refresh_token['refresh_token_id']);
        return $old_refresh_token;
    }
}
