<?php
/*
 * @version $Id: contract.form.php 7178 2008-07-31 12:30:25Z moyo $
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


$NEEDED_ITEMS=array("contract","enterprise","computer","printer","monitor","peripheral","networking","software","document","link","phone","infocom");
define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");


if(!isset($_POST["ID"])) {
	exit();
}


if(!isset($_POST["ID"])) $_POST["ID"] = -1;

$contract=new Contract();

		if ($_POST['ID']>0){
			switch($_POST['glpi_tab']){
				case -1 :	
					showEnterpriseContract($_POST["ID"]);
					showDeviceContract($_POST["ID"]);
					showDocumentAssociated(CONTRACT_TYPE,$_POST["ID"]);
					showLinkOnDevice(CONTACT_TYPE,$_POST["ID"]);
					displayPluginAction(CONTRACT_TYPE,$_POST["ID"],$_POST['glpi_tab']);
					break;
				case 5 : 
					showDocumentAssociated(CONTRACT_TYPE,$_POST["ID"]);
					break;
				case 7 : 
					showLinkOnDevice(CONTRACT_TYPE,$_POST["ID"]);
					break;
				case 10 :
					showNotesForm($_POST['target'],CONTRACT_TYPE,$_POST["ID"]);
					break;
				default :
					if (!displayPluginAction(CONTRACT_TYPE,$_POST["ID"],$_POST['glpi_tab'])){
						showEnterpriseContract($_POST["ID"]);
						showDeviceContract($_POST["ID"]);
					}
					break;
			}
		}
		
	ajaxFooter();
?>