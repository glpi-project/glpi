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

if (!Session::haveRightsOr('knowbase', [READ, KnowbaseItem::READFAQ])) {
   Session::redirectIfNotLoggedIn();
   Html::displayRightError();
}
if (isset($_GET["id"])) {
   Html::redirect(KnowbaseItem::getFormURLWithID($_GET["id"]));
}

Html::header(KnowbaseItem::getTypeName(1), $_SERVER['PHP_SELF'], "tools", "knowbaseitem");

// Search a solution
if (!isset($_GET["contains"])
    && isset($_GET["item_itemtype"])
    && isset($_GET["item_items_id"])) {

   if ($item = getItemForItemtype($_GET["item_itemtype"])) {
      if ($item->getFromDB($_GET["item_items_id"])) {
         $_GET["contains"] = $item->getField('name');
      }
   }
}

// Manage forcetab : non standard system (file name <> class name)
if (isset($_GET['forcetab'])) {
   Session::setActiveTab('Knowbase', $_GET['forcetab']);
   unset($_GET['forcetab']);
}

$kb = new Knowbase();
$kb->display($_GET);


Html::footer();
