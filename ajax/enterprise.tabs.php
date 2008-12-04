<?php
/*
 * @version $Id: enterprise.form.php 7178 2008-07-31 12:30:25Z moyo $
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


$NEEDED_ITEMS=array("enterprise","contact","document","contract","tracking","user","group","computer","printer","monitor","peripheral","networking","software","link","phone","infocom","device");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if(!isset($_POST["ID"])) {
	exit();
}

$ent=new Enterprise();

if (!isset($_POST["start"])) {
	$_POST["start"]=0;
}

if (!isset($_POST["sort"])) $_POST["sort"]="";
if (!isset($_POST["order"])) $_POST["order"]="";

	$ent->check($_POST["ID"],'r');

		if ($_POST["ID"]>0){
			switch($_POST['glpi_tab']){
				case -1:
					showAssociatedContact($_POST["ID"]);
					showContractAssociatedEnterprise($_POST["ID"]);
					showDocumentAssociated(ENTERPRISE_TYPE,$_POST["ID"]);
					showTrackingList($_POST['target'],$_POST["start"],$_POST["sort"],$_POST["order"],"all",'','',0,0,0,0,0,$_POST["ID"]);
					showLinkOnDevice(ENTERPRISE_TYPE,$_POST["ID"]);
					displayPluginAction(ENTERPRISE_TYPE,$_POST["ID"],$_POST['glpi_tab']);
					break;
				case 1 :
					showAssociatedContact($_POST["ID"]);
					break;
				case 4 :
					showContractAssociatedEnterprise($_POST["ID"]);
					break;
				case 5 :
					showDocumentAssociated(ENTERPRISE_TYPE,$_POST["ID"],0);
					break;
				case 6 :
					showTrackingList($_POST['target']."?ID=".$_POST["ID"],$_POST["start"],$_POST["sort"],$_POST["order"],"all",'','',0,0,0,0,0,$_POST["ID"]);
					break;
				case 7 : 
					showLinkOnDevice(ENTERPRISE_TYPE,$_POST["ID"]);
					break;
				case 10 :
					showNotesForm($_POST['target'],ENTERPRISE_TYPE,$_POST["ID"]);
					break;	
				case 15 :
					showInfocomEnterprise($_POST["ID"]);
					break;	
				default : 
					if (!displayPluginAction(ENTERPRISE_TYPE,$_POST["ID"],$_POST['glpi_tab'])){
						showAssociatedContact($_POST["ID"]);
					}
					break;
			}
		}
	
	ajaxFooter();
?>
