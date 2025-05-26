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

class SecurityResponseMiddleware extends AbstractMiddleware implements ResponseMiddlewareInterface
{
    public function process(MiddlewareInput $input, callable $next): void
    {
        // Inject header to prevent MIME sniffing
        $input->response = $input->response->withHeader('X-Content-Type-Options', 'nosniff');
        // CORS
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            $input->response = $input->response->withHeader('Access-Control-Allow-Origin', '*');   // cache for 1 day
        }
        if ($input->request->getMethod() === 'GET' || $input->request->getMethod() === 'OPTIONS') {
            $input->response = $input->response->withHeader('Access-Control-Expose-Headers', ['Content-Type', 'Content-Range', 'Accept-Ranges']);
        }
        $next($input);
    }
}
