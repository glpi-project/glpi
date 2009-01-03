<?php
/*
 * @version $Id: software.tabs.php 7375 2008-10-06 20:54:52Z moyo $
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

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------



$NEEDED_ITEMS=array("computer","software","rulesengine","tracking","document","user","group","link","reservation","infocom","contract","enterprise","rule.softwarecategories");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");


if (!isset($_POST['ID'])) {
	exit();
}

if(!isset($_POST["sort"])) $_POST["sort"] = "";
if(!isset($_POST["order"])) $_POST["order"] = "";
if(!isset($_POST["withtemplate"])) $_POST["withtemplate"] = "";

	checkRight("software","r");

		switch($_POST['glpi_tab']){
			case -1:
				showInstallations($_POST["ID"], "ID");
				displayPluginAction(SOFTWAREVERSION_TYPE,$_POST["ID"],$_POST['glpi_tab'],$_POST["withtemplate"]);
				break;
			case 2 :
				showInstallations($_POST["ID"], "ID");
				break;
			case 12 :
				showHistory(SOFTWAREVERSION_TYPE,$_POST["ID"]);
				break;
			default :
				if (!displayPluginAction(SOFTWAREVERSION_TYPE,$_POST["ID"],$_POST['glpi_tab'],$_POST["withtemplate"])){
					showInstallationsByEntity($_POST["ID"]);
				}
				break;
		}

	ajaxFooter();
?>
