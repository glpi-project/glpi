<?php
/*
 * @version $Id: cartridge.form.php 6338 2008-01-12 20:01:17Z moyo $
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


$NEEDED_ITEMS=array("cartridge","printer","link","document","infocom","contract","enterprise");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if(!isset($_POST["ID"])) {
	exit();
}

checkRight("cartridge","r");

	if (isset($_POST['tab'])) {
		$_SESSION['glpi_tab']=$_POST['tab'];
	}

	
	switch($_POST['tab']){
		case -1 :	
					showCompatiblePrinters($_POST["ID"]);
					showCartridgesAdd($_POST["ID"]);
					showCartridges($_POST["ID"]);
					showCartridges($_POST["ID"],1);
					showInfocomForm($CFG_GLPI["root_doc"]."/front/infocom.form.php",CARTRIDGE_TYPE,$_POST["ID"],1);
					showDocumentAssociated(CARTRIDGE_TYPE,$_POST["ID"]);
					showLinkOnDevice(CARTRIDGE_TYPE,$_POST["ID"]);
					displayPluginAction(CARTRIDGE_TYPE,$_POST["ID"],$_POST['tab']);
					break;
				case 4 :
					showInfocomForm($CFG_GLPI["root_doc"]."/front/infocom.form.php",CARTRIDGE_TYPE,$_POST["ID"],1);
					break;

				case 5 :
					showDocumentAssociated(CARTRIDGE_TYPE,$_POST["ID"]);
					break;			
				case 7 : 
					showLinkOnDevice(CARTRIDGE_TYPE,$_POST["ID"]);
					break;
				case 10 :
					showNotesForm($_POST['target'],CARTRIDGE_TYPE,$_POST["ID"]);
					break;
				default :
					if (!displayPluginAction(CARTRIDGE_TYPE,$_POST["ID"],$_SESSION['glpi_tab'])){
						showCompatiblePrinters($_POST["ID"]);
						showCartridgesAdd($_POST["ID"]);
						showCartridges($_POST["ID"]);
						showCartridges($_POST["ID"],1);
					}
					break;
			}
	


?>
