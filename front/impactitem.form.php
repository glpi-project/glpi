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

$impact_item = new ImpactItem();

if (isset($_POST["update"])) {
   $id = $_POST["id"] ?? 0;

   // Can't update, id is missing
   if ($id === 0) {
      Toolbox::logWarning("Can't update the target impact item, id is missing");
      Html::back();
   }

   // Load item and check rights
   $impact_item->getFromDB($id);
   Session::checkRight($impact_item->fields['itemtype']::$rightname, UPDATE);

   // Update item and back
   $impact_item->update($_POST);
   Html::redirect(Html::getBackUrl() . "#list");
}
