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
header('Content-Type: application/json; charset=UTF-8');
Html::header_nocache();

Session::checkLoginUser();

$savedsearch = new SavedSearch();

if (isset($_POST["name"])) {
   //Add a new saved search
   header("Content-Type: application/json; charset=UTF-8");
   $savedsearch->check(-1, CREATE, $_POST);
   if ($newID = $savedsearch->add($_POST)) {
      Session::addMessageAfterRedirect(
         __('Search has been saved'),
         false,
         INFO
      );
      echo json_encode(['success' => true]);
   } else {
      Session::addMessageAfterRedirect(
         __('Search has not been saved'),
         false,
         ERROR
      );
      echo json_encode(['success' => false]);
   }
   return;
}

if (isset($_GET['mark_default'])
           && isset($_GET["id"])) {
   $savedsearch->check($_GET["id"], READ);

   if ($_GET["mark_default"] > 0) {
      $savedsearch->markDefault($_GET["id"]);
   } else if ($_GET["mark_default"] == 0) {
      $savedsearch->unmarkDefault($_GET["id"]);
   }
   //to refresh slidepanel
   $_GET['action'] = 'show';
}

if (!isset($_GET['action'])) {
   return;
}

if ($_GET['action'] == 'reorder') {
   $savedsearch->saveOrder($_GET['ids']);
   header("Content-Type: application/json; charset=UTF-8");
   echo json_encode(['res' => true]);
}

if ($_GET['action'] == 'create') {
   header("Content-Type: text/html; charset=UTF-8");

   if (!isset($_GET['type'])) {
      $_GET['type'] = -1;
   } else {
      $_GET['type']  =(int)$_GET['type'];
   }

   $savedsearch->showForm(
      0, [
         'type'      => $_GET['type'],
         'url'       => rawurldecode($_GET["url"]),
         'itemtype'  => $_GET["itemtype"],
         'ajax'      => true
      ]
   );
   return;
}

if ($_GET['action'] == 'show') {
   header("Content-Type: text/html; charset=UTF-8");
   $savedsearch->displayMine();
}
