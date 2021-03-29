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

include ('../inc/includes.php');

Html::header_nocache();

Session::checkLoginUser();

if (isset($_REQUEST["urgency"])
    && isset($_REQUEST["impact"])) {

   $priority = Ticket::computePriority($_REQUEST["urgency"], $_REQUEST["impact"]);

   if (isset($_REQUEST['getJson'])) {
      header("Content-Type: application/json; charset=UTF-8");
      echo json_encode(['priority' => $priority]);

   } elseif ($_REQUEST["priority"]) {
      // Send UTF8 Headers
      header("Content-Type: text/html; charset=UTF-8");
      echo "<script type='text/javascript' >\n";
      echo Html::jsSetDropdownValue($_REQUEST["priority"], $priority);
      echo "\n</script>";

   } else {
      echo Ticket::getPriorityName($priority);
   }
}
