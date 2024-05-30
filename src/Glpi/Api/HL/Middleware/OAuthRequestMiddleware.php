<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

use CommonDBTM;
use Glpi\Api\HL\Controller\AbstractController;
use Glpi\Api\HL\Doc\Route;
use Glpi\Api\HL\RoutePath;
use Glpi\Api\HL\Router;
use Glpi\Http\Request;
use Glpi\Http\Response;

/**
 * Handles OAuth scopes
 */
class OAuthRequestMiddleware extends AbstractMiddleware implements RequestMiddlewareInterface
{
    public function process(MiddlewareInput $input, callable $next): void
    {
        $route_path = $input->route_path->getRoutePath();
        if (isCommandLine() || $input->route_path->getRouteSecurityLevel() === \Glpi\Api\HL\Route::SECURITY_NONE) {
            $next($input);
            return;
        }
        // If OAuth scopes are expanded to be more endpoint-specific, the Route attributes should be updated with the ability to specify the scopes,
        // and the middleware should check the scopes against the scopes allowed/requested for the client.
        //TODO Handle 'inventory' scope. I guess the agent communication needs redirected through the API.

        if (strcasecmp($route_path, '/Administration/User/Me/Email/Default') === 0) {
            $scopes_required = ['OR' => ['email', 'user']];
        } else if (
            strcasecmp($route_path, '/Administration/User/Me') === 0
            || str_starts_with(strtolower($route_path), '/administration/user/me/')
        ) {
            $scopes_required = ['OR' => ['user']];
        } else if (str_starts_with(strtolower($route_path), '/status')) {
            $scopes_required = ['OR' => ['status']];
        } else {
            $scopes_required = ['OR' => ['api']];
        }

        $client = Router::getInstance()->getCurrentClient();
        if ($client === null) {
            $input->response = AbstractController::getAccessDeniedErrorResponse();
            return;
        } else if ($client['client_id'] === 'internal') {
            // No scope restrictions for internal clients
            $next($input);
            return;
        }
        foreach ($scopes_required['OR'] as $scope) {
            if (in_array($scope, $client['scopes'], true)) {
                $next($input);
                return;
            }
        }

        $input->response = AbstractController::getAccessDeniedErrorResponse();
    }
}
