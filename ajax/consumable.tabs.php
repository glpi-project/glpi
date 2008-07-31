<?php
/*
 * @version $Id: consumable.form.php 6338 2008-01-12 20:01:17Z moyo $
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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------


$NEEDED_ITEMS=array("consumable","printer","infocom","link","document","enterprise","contract");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if(!isset($_POST["ID"])) {
	exit();
}

checkRight("consumable","r");

	switch($_SESSION['glpi_tab']){
				case -1 :	
					showConsumableAdd($_POST["ID"]);
					showConsumables($_POST["ID"]);
					showConsumables($_POST["ID"],1);
					showInfocomForm($CFG_GLPI["root_doc"]."/front/infocom.form.php",CONSUMABLE_TYPE,$_POST["ID"],1);
					showDocumentAssociated(CONSUMABLE_TYPE,$_POST["ID"]);
					showLinkOnDevice(CONSUMABLE_TYPE,$_POST["ID"]);
					displayPluginAction(CONSUMABLE_TYPE,$_POST["ID"],$_SESSION['glpi_tab']);
					break;
				case 4 :
					showInfocomForm($CFG_GLPI["root_doc"]."/front/infocom.form.php",CONSUMABLE_TYPE,$_POST["ID"],1);
					break;
				case 5 :
					showDocumentAssociated(CONSUMABLE_TYPE,$_POST["ID"]);
					break;

				case 7 : 
					showLinkOnDevice(CONSUMABLE_TYPE,$_POST["ID"]);
					break;

				case 10 :
					showNotesForm($_POST['target'],CONSUMABLE_TYPE,$_POST["ID"]);
					break;
				default :
					if (!displayPluginAction(CONSUMABLE_TYPE,$_POST["ID"],$_SESSION['glpi_tab'])){
						showConsumableAdd($_POST["ID"]);
						showConsumables($_POST["ID"]);
						showConsumables($_POST["ID"],1);
					}
					break;
			}
	
?>
