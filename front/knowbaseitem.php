<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
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

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

Session::checkSeveralRightsOr(array('knowbase' => 'r',
                                    'faq'      => 'r'));

if (isset($_GET["id"])) {
   Html::redirect($CFG_GLPI["root_doc"]."/front/knowbaseitem.form.php?id=".$_GET["id"]);
}

Html::header($LANG['title'][5],$_SERVER['PHP_SELF'],"utils","knowbase");

// Search a solution
if (!isset($_GET["contains"]) && isset($_GET["itemtype"]) && isset($_GET["items_id"])) {
   $item = new $_GET["itemtype"]();
   if ($item->getFromDB($_GET["items_id"])) {
      $_GET["contains"] = addslashes($item->getField('name'));
   }
}

if (!isset($_GET["contains"])) {
   $_GET["contains"] = "";
}

if (!isset($_GET["knowbaseitemcategories_id"])) {
   $_GET["knowbaseitemcategories_id"] = "0";
}

$faq = !Session::haveRight("knowbase","r");

KnowbaseItem::searchForm($_GET, $faq);
if (!isset($_GET["itemtype"]) || !isset($_GET["items_id"])) {
   KnowbaseItemCategory::showFirstLevel($_GET, $faq);
}
KnowbaseItem::showList($_GET,$faq);

if (!$_GET["knowbaseitemcategories_id"] && strlen($_GET["contains"])==0) {
   KnowbaseItem::showViewGlobal($CFG_GLPI["root_doc"]."/front/knowbaseitem.form.php", $faq) ;
}

Html::footer();
?>