<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace Glpi\CalDAV\Plugin;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use Glpi\CalDAV\Traits\CalDAVUriUtilTrait;
use Sabre\DAV\Browser\Plugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * Browser plugin for CalDAV server.
 *
 * @since 9.5.0
 */
class Browser extends Plugin {

   use CalDAVUriUtilTrait;

   public function httpGet(RequestInterface $request, ResponseInterface $response) {
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
   private function canDisplayDebugInterface() {
      /** @var $authPlugin \Sabre\DAV\Auth\Plugin */
      $authPlugin = $this->server->getPlugin('auth');
      if (!$authPlugin) {
         return false;
      }

      $user = $this->getPrincipalItemFromUri($authPlugin->getCurrentPrincipal());

      return $user instanceof \User && \Session::DEBUG_MODE == $user->fields['use_mode'];
   }
}
