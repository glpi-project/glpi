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

namespace Glpi\Http;

use Symfony\Component\HttpFoundation\Request;

use function Safe\preg_replace;

class SymfonyRequest extends Request
{
    public function __construct(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null)
    {
        // Normalize plugins paths.
        // All plugins resources should now be accessed using the `/plugins/${plugin_key}/${resource_path}`.
        $pattern = '#^/marketplace/#';
        if (isset($server['REQUEST_URI']) && preg_match($pattern, $server['REQUEST_URI'])) {
            // /!\ `/marketplace/` URLs were massively used prior to GLPI 11.0.
            //
            // To not break URLs than can be found in the wild (in e-mail, forums, external apps configuration, ...),
            // please do not remove this behaviour before, at least, 2030 (about 5 years after GLPI 11.0.0 release).
            //deprecated message cause glpiinventory call from agent to fail.
            //Toolbox::deprecated('Accessing the plugins resources from the `/marketplace/` path is deprecated. Use the `/plugins/` path instead.');
            $server['REQUEST_URI'] = preg_replace(
                $pattern,
                '/plugins/',
                $server['REQUEST_URI']
            );
        }
        $this->initialize($query, $request, $attributes, $cookies, $files, $server, $content);
    }
}
