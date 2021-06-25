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

namespace Glpi\Gantt;

use Glpi\Gantt\Link;
use ProjectTaskLink;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * DAO class for handling project task links
 */
class LinkDAO {

   public function getLinksForItemIDs($ids) {
      $links = [];
      $tasklink = new ProjectTaskLink();

      $ids = implode(',', $ids);
      $iterator = $tasklink->getFromDBForItemIDs($ids);
      while ($data = $iterator->next()) {
         array_push($links, $this->populateFromDB($data));
      }

      return $links;
   }

   /**
    * Populates a Link object with data
    *
    * @param $data Database record
    *
    * @return Link object
    */
   function populateFromDB($data) {
      $link = new Link();
      $link->id = $data["id"];
      $link->source = $data["source_uuid"];
      $link->target = $data["target_uuid"];
      $link->type = $data["type"];
      $link->lag = $data["lag"];
      $link->lead = $data["lead"];
      return $link;
   }
}
