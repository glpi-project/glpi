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


if (!isset($POST["start"])) {
	$POST["start"]=0;
}

if (!isset($POST["sort"])) $POST["sort"]="";
if (!isset($POST["order"])) $POST["order"]="";

	$ent->check($POST["ID"],'r');

		if ($POST["ID"]>0){
			switch($_POST['glpi_tab']){
				case -1:
					showAssociatedContact($POST["ID"]);
					showContractAssociatedEnterprise($POST["ID"]);
					showDocumentAssociated(ENTERPRISE_TYPE,$POST["ID"]);
					showTrackingList($_POST['target'],$POST["start"],$POST["sort"],$POST["order"],"all",'','',0,0,0,0,0,$POST["ID"]);
					showLinkOnDevice(ENTERPRISE_TYPE,$POST["ID"]);
					displayPluginAction(ENTERPRISE_TYPE,$POST["ID"],$_POST['glpi_tab']);
					break;
				case 1 :
					showAssociatedContact($POST["ID"]);
					break;
				case 4 :
					showContractAssociatedEnterprise($POST["ID"]);
					break;
				case 5 :
					showDocumentAssociated(ENTERPRISE_TYPE,$POST["ID"],0);
					break;
				case 6 :
					showTrackingList($_POST['target']."?ID=".$POST["ID"],$POST["start"],$POST["sort"],$POST["order"],"all",'','',0,0,0,0,0,$POST["ID"]);
					break;
				case 7 : 
					showLinkOnDevice(ENTERPRISE_TYPE,$POST["ID"]);
					break;
				case 10 :
					showNotesForm($_POST['target'],ENTERPRISE_TYPE,$POST["ID"]);
					break;	
				case 15 :
					showInfocomEnterprise($POST["ID"]);
					break;	
				default : 
					if (!displayPluginAction(ENTERPRISE_TYPE,$POST["ID"],$_POST['glpi_tab'])){
						showAssociatedContact($POST["ID"]);
					}
					break;
			}
		}
	

?>
