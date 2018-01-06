<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

Session::checkCentralAccess();

$ios = new \Item_OperatingSystem();

if (isset($_POST['update'])) {
   $ios->check($_POST['id'], UPDATE);
   //update existing OS
   $ios->update($_POST);

   $item = getItemForItemType($_POST['itemtype']);
   $url = $item->getFormURLWithID($_POST['items_id']);
   Html::redirect($url);
} else if (isset($_POST['add'])) {
   $ios->check(-1, CREATE, $_POST);
   $ios->add($_POST);

   $item = getItemForItemType($_POST['itemtype']);
   $url = $item->getFormURLWithID($_POST['items_id']);
   Html::redirect($url);
} else if (isset($_POST['purge'])) {
   $ios->check($_POST['id'], PURGE);
   $ios->delete($_POST, 1);

   $item = getItemForItemType($_POST['itemtype']);
   $url = $item->getFormURLWithID($_POST['items_id']);
   Html::redirect($url);
}

if (!isset($_GET['itemtype']) && !isset($_GET['items_id']) && !isset($_GET['id'])) {
   Html::displayErrorAndDie('Lost');
}

$params = [];
if (isset($_GET['id'])) {
   $params['id'] = $_GET['id'];
} else {
   $params = [
      'itemtype'  => $_GET['itemtype'],
      'items_id'  => $_GET['items_id']
   ];
}

Html::header(Computer::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "assets", "computer");

$ios->display($params);
Html::footer();
