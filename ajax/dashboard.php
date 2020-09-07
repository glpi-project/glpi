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

include ('../inc/includes.php');

use Glpi\Dashboard\Grid;

if (!isset($_REQUEST["action"])) {
   exit;
}

if (!isset($_REQUEST['embed']) || !$_REQUEST['embed']) {
   Session::checkCentralAccess();

} else if (!in_array($_REQUEST['action'], [
   'get_dashboard_items',
   'get_card'
])) {
   Html::displayRightError();
}

$dashboard = new Glpi\Dashboard\Dashboard($_REQUEST['dashboard'] ?? "");

switch ($_REQUEST['action']) {
   case 'save_new_dashboard':
      echo $dashboard->saveNew(
         $_REQUEST['title']   ?? "",
         $_REQUEST['context'] ?? ""
      );
      exit;

   case 'save_items':
      $dashboard->saveitems($_REQUEST['items'] ?? []);
      $dashboard->saveTitle($_REQUEST['title'] ?? "");
      exit;

   case 'save_rights':
      echo $dashboard->saveRights($_REQUEST['rights'] ?? []);
      exit;

   case 'delete_dashboard':
      echo $dashboard->delete(['key' => $_REQUEST['dashboard']]);
      exit;

   case 'set_last_dashboard':
      $grid = new Grid($_REQUEST['dashboard'] ?? "");
      echo $grid->setLastDashboard($_REQUEST['page'], $_REQUEST['dashboard']);
      exit;

   case 'clone_dashboard':
      $new_dashboard = $dashboard->cloneCurrent();
      echo json_encode($new_dashboard);
      exit;
}

$grid = new Grid($_REQUEST['dashboard'] ?? "");

session_write_close();
header("Content-Type: text/html; charset=UTF-8");
switch ($_REQUEST['action']) {
   case 'add_new':
      $grid->displayAddDashboardForm();
      break;

   case 'edit_rights':
      $grid->displayEditRightsForm();
      break;

   case 'display_edit_widget':
   case 'display_add_widget':
      $grid->displayWidgetForm($_REQUEST);
      break;

   case 'display_embed_form':
      $grid->displayEmbedForm();
      break;

   case 'get_card':
      echo $grid->getCardHtml($_REQUEST['card_id'], $_REQUEST);
      break;

   case 'display_add_filter':
      $grid->displayFilterForm($_REQUEST);
      break;
   case 'get_dashboard_filters':
      echo $grid->getFiltersSetHtml($_REQUEST['filters'] ?? []);
      break;
   case 'get_filter':
      echo $grid->getFilterHtml($_REQUEST['filter_id']);
      break;

   case 'get_dashboard_items':
      echo $grid->getGridItemsHtml(true, $_REQUEST['embed'] ?? false);
      break;
}
