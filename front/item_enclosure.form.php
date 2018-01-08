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

$ien = new \Item_Enclosure();
$enclosure = new Enclosure();

if (isset($_POST['update'])) {
   $ien->check($_POST['id'], UPDATE);
   //update existing relation
   if ($ien->update($_POST)) {
      $url = $enclosure->getFormURLWithID($_POST['enclosures_id']);
   } else {
      $url = $ien->getFormURLWithID($_POST['id']);
   }
   Html::redirect($url);
} else if (isset($_POST['add'])) {
   $ien->check(-1, CREATE, $_POST);
   $ien->add($_POST);
   $url = $enclosure->getFormURLWithID($_POST['enclosures_id']);
   Html::redirect($url);
} else if (isset($_POST['purge'])) {
   $ien->check($_POST['id'], PURGE);
   $ien->delete($_POST, 1);
   $url = $enclosure->getFormURLWithID($_POST['enclosures_id']);
   Html::redirect($url);
}

if (!isset($_REQUEST['enclosure']) && !isset($_REQUEST['id'])) {
   Html::displayErrorAndDie('Lost');
}

$params = [];
if (isset($_REQUEST['id'])) {
   $params['id'] = $_REQUEST['id'];
} else {
   $params = [
      'enclosures_id'   => $_REQUEST['enclosure']
   ];
}

Html::header(Enclosure::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "management", "enclosure");

$ien->display($params);
Html::footer();
