<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
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

include '../inc/includes.php';


if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}
if (!isset($_GET["withtemplate"])) {
   $_GET["withtemplate"] = "";
}

$appliance = new Appliance();
$item       = new Appliance_Item();

if (isset($_POST["add"])) {
   $appliance->check(-1, CREATE, $_POST);
   $newID = $appliance->add($_POST);
   if ($_SESSION['glpibackcreated']) {
      Html::redirect($appliance->getFormURLWithID($newID));
   }
   Html::back();

} else if (isset($_POST["update"])) {
   $appliance->check($_POST['id'], UPDATE);
   $appliance->update($_POST);
   Html::back();

} else if (isset($_POST["delete"])) {
   $appliance->check($_POST['id'], DELETE);
   $appliance->delete($_POST);
   Html::redirect(Appliance::getSearchURL());

} else if (isset($_POST["restore"])) {
   $appliance->check($_POST['id'], PURGE);
   $appliance->restore($_POST);
   Html::back();

} else if (isset($_POST["purge"])) {
   $appliance->check($_POST['id'], PURGE);
   $appliance->delete($_POST, 1);
   Html::redirect(Appliance::getSearchURL());

} else if (isset($_POST["delrelation"])) {
   // delete a relation
   $relation = new ApplianceRelation();
   if (isset($_POST['itemrelation'])) {
      foreach ($_POST["itemrelation"] as $key => $val) {
         $relation->delete(['id' => $key]);
      }
   }
   Html::back();

} else if (isset($_POST["addrelation"])) {
   // add a relation
   $relation = new ApplianceRelation();
   if ($_POST['tablekey'] >0) {
      foreach ($_POST["tablekey"] as $key => $val) {
         if ($val > 0) {
            $relation->add(['appliances_items_id' => $key,
                            'relations_id'         => $val]);
         }
      }
   }
   Html::back();

} else if (isset($_POST["additem"])) {
   if ($_POST['itemtype']
       && ($_POST['item'] > 0)) {
      $input = ['appliances_id'  => $_POST['conID'],
                'items_id'       => $_POST['item'],
                'itemtype'       => $_POST['itemtype']];

      $item->check(-1, UPDATE, $input);
      $newID = $item->add($input);
   }
   Html::back();

} else if (isset($_POST["deleteappliance"])) {
   $input = ['id' => $_POST["id"]];
   $item->check($_POST["id"], UPDATE);
   $item->delete($input);
   Html::back();

} else {
   $appliance->checkGlobal(READ);
   Html::header(Appliance::getTypeName(1), $_SERVER['PHP_SELF'], "management", "appliance");
   $appliance->display($_GET + ['formoptions' => "data-track-changes=true"]);
   Html::footer();
}
