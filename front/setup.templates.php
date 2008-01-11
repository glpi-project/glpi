<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

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

// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------



$NEEDED_ITEMS=array("setup");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

$type="inventory";
if ($_GET["type"]==OCSNG_TYPE){
	$type="config";
}
$item=ereg_replace(".form.php","",$INFOFORM_PAGES[$_GET["type"]]);
$item=ereg_replace("front/","",$item);
commonHeader($LANG["common"][12],$_SERVER['PHP_SELF'],$type,$item);

listTemplates($_GET["type"],$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$_GET["type"]],$_GET["add"]);

commonFooter();


?>
