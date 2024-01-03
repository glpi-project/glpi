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

namespace Glpi\CalDAV\Plugin;

use Config;
use Glpi\CalDAV\Traits\CalDAVUriUtilTrait;
use Sabre\DAV\Browser\Plugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * Browser plugin for CalDAV server.
 *
 * @since 9.5.0
 */
class Browser extends Plugin
{
    use CalDAVUriUtilTrait;

    public function httpGet(RequestInterface $request, ResponseInterface $response)
    {
        if (!$this->canDisplayDebugInterface()) {
            return;
        }

        return parent::httpGet($request, $response);
    }

    /**
     * Check if connected user can display the HTML frontend.
     *
     * @return boolean
     */
    private function canDisplayDebugInterface()
    {
        /** @var $authPlugin \Sabre\DAV\Auth\Plugin */
        $authPlugin = $this->server->getPlugin('auth');
        if (!$authPlugin) {
            return false;
        }

        return Config::canUpdate();
    }
}
