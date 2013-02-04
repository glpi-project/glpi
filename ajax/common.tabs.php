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

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();


if (!isset($_REQUEST['glpi_tab'])) {
   exit();
}

if (!isset($_REQUEST['itemtype']) || empty($_REQUEST['itemtype'])) {
   exit();
}

if (!isset($_REQUEST["sort"])) {
   $_REQUEST["sort"] = "";
}

if (!isset($_REQUEST["order"])) {
   $_REQUEST["order"] = "";
}

if (!isset($_REQUEST["withtemplate"])) {
   $_REQUEST["withtemplate"] = "";
}

$item = new $_REQUEST['itemtype']();

if (($item instanceof CommonDBTM)
    && $item->isNewItem()
    && (!isset($_REQUEST["id"]) || !$item->can($_REQUEST["id"],'r'))) {
   exit();
}

CommonGLPI::displayStandardTab($item, $_REQUEST['glpi_tab'],$_REQUEST["withtemplate"]);

Html::ajaxFooter();
?>