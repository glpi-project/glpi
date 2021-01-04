<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

use Glpi\CalDAV\Backend\Calendar;
use Glpi\CalDAV\Traits\CalDAVUriUtilTrait;
use Sabre\CalDAV\Plugin;
use Sabre\DAV\INode;
use Sabre\DAV\IProperties;
use Sabre\DAV\PropFind;

/**
 * CalDAV plugin for CalDAV server.
 *
 * @since 9.5.0
 */
class CalDAV extends Plugin {

   use CalDAVUriUtilTrait;

   public function getCalendarHomeForPrincipal($principalUrl) {

      $calendar_uri = null;

      $principal_itemtype = $this->getPrincipalItemtypeFromUri($principalUrl);
      switch ($principal_itemtype) {
         case \Group::class:
            $calendar_uri = Calendar::PREFIX_GROUPS . '/' . $this->getGroupIdFromPrincipalUri($principalUrl);
            break;
         case \User::class:
            $calendar_uri = Calendar::PREFIX_USERS . '/' . $this->getUsernameFromPrincipalUri($principalUrl);
            break;
      }

      return $calendar_uri;
   }

   public function propFind(PropFind $propFind, INode $node) {

      // Return any requested property as long as it is defined in node.
      if ($node instanceof IProperties) {
         $properties = $node->getProperties([]);
         foreach ($properties as $property_name => $property_value) {
            $propFind->handle($property_name, $property_value);
         }
      }

      parent::propFind($propFind, $node);
   }
}
