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

declare(strict_types=1);

namespace Glpi\Security\ReAuth;

use Auth;
use Session;
use User;

/**
 * Resolves the authentication method a re-authentication strategy must match against.
 *
 * This is the method that opened the current session (@see Session::getAuthType()), not the one
 * configured on the user record, so that a session opened through an external system is not asked
 * for a credential the user does not have.
 */
final class SessionAuthType
{
    public static function resolve(User $user): int
    {
        $auth_type = Session::getAuthType();

        // A "remember me" session resumes a real login, and a stolen cookie is exactly what
        // re-authentication is meant to stop: the account's own method still applies, local
        // password included.
        if ($auth_type === Auth::COOKIE) {
            return (int) ($user->fields['authtype'] ?? Auth::NOT_YET_AUTHENTIFIED);
        }

        if (!Auth::isAlternateAuth($auth_type)) {
            return $auth_type;
        }

        // The session was opened by an external system that re-presents its proof on every request
        // without any user interaction, so it cannot be replayed as a challenge. Fall back to the
        // account's own store, but only when that store can be queried: a directory or a mail
        // server is the user's identity source, whereas a local hash left on an externally
        // authenticated account is a leftover they no longer know.
        $store = (int) ($user->fields['authtype'] ?? Auth::NOT_YET_AUTHENTIFIED);

        return in_array($store, [Auth::LDAP, Auth::MAIL], true) && (int) $user->fields['auths_id'] > 0
            ? $store
            : Auth::NOT_YET_AUTHENTIFIED;
    }
}
