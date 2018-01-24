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

$pra  = new \PDU_Rack();
$rack = new Rack();

if (isset($_POST['update'])) {
   $pra->check($_POST['id'], UPDATE);
   //update existing relation
   if ($pra->update($_POST)) {
      $url = $rack->getFormURLWithID($_POST['racks_id']);
   } else {
      $url = $pra->getFormURLWithID($_POST['id']);
   }
   Html::redirect($url);
} else if (isset($_POST['add'])) {
   $pra->check(-1, CREATE, $_POST);
   $pra->add($_POST);
   $url = $rack->getFormURLWithID($_POST['racks_id']);
   Html::redirect($url);
} else if (isset($_POST['purge'])) {
   $pra->check($_POST['id'], PURGE);
   $pra->delete($_POST, 1);
   $url = $rack->getFormURLWithID($_POST['racks_id']);
   Html::redirect($url);
}

$params = [];
if (isset($_GET['id'])) {
   $params['id'] = $_GET['id'];
} else {
   $params = [
      'racks_id'     => $_GET['racks_id'],
   ];
}
$ajax = isset($_REQUEST['ajax']) ? true : false;

if (!$ajax) {
   Html::header(Rack::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "assets", "rack");
}
$pra->display($params);
if (!$ajax) {
   Html::footer();
}
