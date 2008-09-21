<?php
/*
 * @version $Id: phone.form.php 7178 2008-07-31 12:30:25Z moyo $
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


$NEEDED_ITEMS=array("phone","infocom","contract","user","group","link","networking","document","tracking","reservation","computer","enterprise","ocsng");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if(!isset($_POST["ID"])) {
	exit();
}


if(empty($_POST["ID"])) $_POST["ID"] = "";
if(!isset($_POST["sort"])) $_POST["sort"] = "";
if(!isset($_POST["order"])) $_POST["order"] = "";

if(!isset($_POST["withtemplate"])) $_POST["withtemplate"] = "";


	checkRight("phone","r");


	if (!empty($_POST["withtemplate"])) {

		if ($_POST["ID"]>0){

				switch($_POST['glpi_tab']){
					case 4 :
						showInfocomForm($CFG_GLPI["root_doc"]."/front/infocom.form.php",PHONE_TYPE,$_POST["ID"],1,$_POST["withtemplate"]);
						showContractAssociated(PHONE_TYPE,$_POST["ID"],$_POST["withtemplate"]);
						break;
					case 5 :
						showDocumentAssociated(PHONE_TYPE,$_POST["ID"],$_POST["withtemplate"]);
						break;

					default :
						if (!displayPluginAction(PHONE_TYPE,$_POST["ID"],$_SESSION['glpi_tab'],$_POST["withtemplate"])){
							if ($_POST["withtemplate"]!=2)	{
								showPortsAdd($_POST["ID"],PHONE_TYPE);
							}
							showPorts($_POST["ID"], PHONE_TYPE,$_POST["withtemplate"]);
						}

						break;
				}
			}


	} else {
			switch($_POST['glpi_tab']){
				case -1:
					showConnect($_POST['target'],$_POST["ID"],PHONE_TYPE);
					showPortsAdd($_POST["ID"],PHONE_TYPE);
					showPorts($_POST["ID"], PHONE_TYPE,$_POST["withtemplate"]);
					showInfocomForm($CFG_GLPI["root_doc"]."/front/infocom.form.php",PHONE_TYPE,$_POST["ID"]);
					showContractAssociated(PHONE_TYPE,$_POST["ID"]);
					showDocumentAssociated(PHONE_TYPE,$_POST["ID"]);
					showJobListForItem($_SESSION["glpiname"],PHONE_TYPE,$_POST["ID"],$_POST["sort"],$_POST["order"]);
					showOldJobListForItem($_SESSION["glpiname"],PHONE_TYPE,$_POST["ID"],$_POST["sort"],$_POST["order"]);
					showLinkOnDevice(PHONE_TYPE,$_POST["ID"]);
					displayPluginAction(PHONE_TYPE,$_POST["ID"],$_SESSION['glpi_tab'],$_POST["withtemplate"]);
					break;
				case 4 :
					showInfocomForm($CFG_GLPI["root_doc"]."/front/infocom.form.php",PHONE_TYPE,$_POST["ID"]);
					showContractAssociated(PHONE_TYPE,$_POST["ID"]);
					break;
				case 5 :
					showDocumentAssociated(PHONE_TYPE,$_POST["ID"]);
					break;
				case 6 :
					showJobListForItem($_SESSION["glpiname"],PHONE_TYPE,$_POST["ID"],$_POST["sort"],$_POST["order"]);
					showOldJobListForItem($_SESSION["glpiname"],PHONE_TYPE,$_POST["ID"],$_POST["sort"],$_POST["order"]);
					break;
				case 7 :
					showLinkOnDevice(PHONE_TYPE,$_POST["ID"]);
					break;	
				case 10 :
					showNotesForm($_POST['target'],PHONE_TYPE,$_POST["ID"]);
					break;	
				case 11 :
					showDeviceReservations($_POST['target'],PHONE_TYPE,$_POST["ID"]);
					break;
				case 12 :
					showHistory(PHONE_TYPE,$_POST["ID"]);
					break;		
				default :
					if (!displayPluginAction(PHONE_TYPE,$_POST["ID"],$_SESSION['glpi_tab'],$_POST["withtemplate"])){
						showConnect($_POST['target'],$_POST["ID"],PHONE_TYPE);
						showPortsAdd($_POST["ID"],PHONE_TYPE);
						showPorts($_POST["ID"], PHONE_TYPE,$_POST["withtemplate"]);
					}
					break;
			}
	}

	ajaxFooter();
?>