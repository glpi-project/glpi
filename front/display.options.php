<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.
 
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
* @since version 0.84
*/

if (!defined('GLPI_ROOT')) {
   include ('../inc/includes.php');
}

Html::popHeader(__('Display options'), $_SERVER['PHP_SELF']);

if (!isset($_GET['itemtype'])) {
   Html::displayErrorAndDie("lost");
}
$itemtype = $_GET['itemtype'];
if (!isset($_GET["sub_itemtype"])) {
   $_GET["sub_itemtype"] = '';
}

if ($item = getItemForItemtype($itemtype)) {
   if (isset($_GET['update']) || isset($_GET['reset'])) {
      $item->updateDisplayOptions($_GET, $_GET["sub_itemtype"]);
   }
   $item->checkGlobal(READ);
   $item->showDislayOptions($_GET["sub_itemtype"]);
}

Html::popFooter();
?>