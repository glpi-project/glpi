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

namespace Glpi\Api\HL\Middleware;

use Session;

use function Safe\ini_set;

class CookieAuthMiddleware extends AbstractMiddleware implements AuthMiddlewareInterface
{
    public function process(MiddlewareInput $input, callable $next): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            // session already started
            $next($input);
            return;
        }
        // User could be authenticated by a cookie
        // Need to use cookies for session and start it manually
        ini_set('session.use_cookies', '1');
        Session::start();

        if (($user_id = Session::getLoginUserID()) !== false) {
            // unset the response to indicate a successful auth
            $input->response = null;
            $input->client = [
                'client_id' => 'internal', // Internal just means the user was authenticated internally either by cookie or an already existing session.
                'users_id'  => $user_id,
                'scopes' => [],
            ];
        } else {
            $next($input);
        }
    }
}
