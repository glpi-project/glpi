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

$pdup = new \Pdu_Plug();
$pdu = new PDU();

if (isset($_POST['update'])) {
   $pdup->check($_POST['id'], UPDATE);
   //update existing relation
   if ($pdup->update($_POST)) {
      $url = $pdu->getFormURLWithID($_POST['pdus_id']);
   } else {
      $url = $pdup->getFormURLWithID($_POST['id']);
   }
   Html::redirect($url);
} else if (isset($_POST['add'])) {
   $pdup->check(-1, CREATE, $_POST);
   $pdup->add($_POST);
   $url = $pdu->getFormURLWithID($_POST['pdus_id']);
   Html::redirect($url);
} else if (isset($_POST['purge'])) {
   $pdup->check($_POST['id'], PURGE);
   $pdup->delete($_POST, 1);
   $url = $pdu->getFormURLWithID($_POST['pdus_id']);
   Html::redirect($url);
}

if (!isset($_GET['pdus_id']) && !isset($_GET['plugs_id']) && !isset($_GET['number_plug']) && !isset($_GET['id'])) {
   Html::displayErrorAndDie('Lost');
}

$params = [];
if (isset($_GET['id'])) {
   $params['id'] = $_GET['id'];
} else {
   $params = [
      'pdus_id'      => $_GET['pdus_id'],
      'plugs_id'     => $_GET['plugs_id'],
      'number_plug'  => $_GET['number_plug']
   ];
}
$ajax = isset($_REQUEST['ajax']) ? true : false;

if (!$ajax) {
   Html::header(Pdu_Plug::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "assets", "pdu");
}
$pdup->display($params);
if (!$ajax) {
   Html::footer();
}
