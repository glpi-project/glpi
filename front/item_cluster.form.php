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

$icl = new \Item_Cluster();
$cluster = new Cluster();

if (isset($_POST['update'])) {
   $icl->check($_POST['id'], UPDATE);
   //update existing relation
   if ($icl->update($_POST)) {
      $url = $cluster->getFormURLWithID($_POST['clusters_id']);
   } else {
      $url = $icl->getFormURLWithID($_POST['id']);
   }
   Html::redirect($url);
} else if (isset($_POST['add'])) {
   $icl->check(-1, CREATE, $_POST);
   $icl->add($_POST);
   $url = $cluster->getFormURLWithID($_POST['clusters_id']);
   Html::redirect($url);
} else if (isset($_POST['purge'])) {
   $icl->check($_POST['id'], PURGE);
   $icl->delete($_POST, 1);
   $url = $cluster->getFormURLWithID($_POST['clusters_id']);
   Html::redirect($url);
}

if (!isset($_REQUEST['cluster']) && !isset($_REQUEST['id'])) {
   Html::displayErrorAndDie('Lost');
}

$params = [];
if (isset($_REQUEST['id'])) {
   $params['id'] = $_REQUEST['id'];
} else {
   $params = [
      'clusters_id'   => $_REQUEST['cluster']
   ];
}

Html::header(Cluster::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "management", "cluster");

$icl->display($params);
Html::footer();
