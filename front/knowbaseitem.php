<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkSeveralRightsOr(array('knowbase' => 'r',
                           'faq'      => 'r'));

if (isset($_GET["id"])) {
   glpi_header($CFG_GLPI["root_doc"]."/front/knowbaseitem.form.php?id=".$_GET["id"]);
}

commonHeader($LANG['title'][5],$_SERVER['PHP_SELF'],"utils","knowbase");

if (!isset($_GET["contains"])) {
   $_GET["contains"] = "";
}
if (!isset($_GET["knowbaseitemcategories_id"])) {
   $_GET["knowbaseitemcategories_id"] = "0";
}

$faq = !haveRight("knowbase","r");

KnowbaseItem::searchForm($_GET,$faq);
KnowbaseItemCategory::showFirstLevel($_GET,$faq);
KnowbaseItem::showList($_GET,$faq);

if (!$_GET["knowbaseitemcategories_id"] && strlen($_GET["contains"])==0) {
   KnowbaseItem::showViewGlobal($CFG_GLPI["root_doc"]."/front/knowbaseitem.form.php",$faq) ;
}

commonFooter();

?>