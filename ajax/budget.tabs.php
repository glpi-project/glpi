<?php
/*
 * @version $Id: contact.tabs.php 8003 2009-02-26 11:03:19Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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

$NEEDED_ITEMS=array("budget","enterprise","link","document");
define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
header_nocache();

if(!isset($_POST["ID"])) {
	exit();
}

if(empty($_POST["ID"])) $_POST["ID"] = -1;

		if ($_POST['ID']>0){
			switch($_POST['glpi_tab']){
				case -1 :	
					showDocumentAssociated(BUDGET_TYPE,$_POST["ID"]);
					showLinkOnDevice(BUDGET_TYPE,$_POST["ID"]);
					displayPluginAction(BUDGET_TYPE,$_POST["ID"],$_POST['glpi_tab']);
					break;
				case 2 :
					
					break;
				case 5 : 
					showDocumentAssociated(BUDGET_TYPE,$_POST["ID"]);
					break;
				case 7 : 
					showLinkOnDevice(BUDGET_TYPE,$_POST["ID"]);
					break;
				case 10 :
					showNotesForm($_POST['target'],BUDGET_TYPE,$_POST["ID"]);
					break;
				default :
					if (!displayPluginAction(BUDGET_TYPE,$_POST["ID"],$_POST['glpi_tab'])){
					}
					break;
			}
	
}
	ajaxFooter();

?>