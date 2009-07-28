<?php
/*
 * @version $Id$
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


define('GLPI_ROOT','..');
$NEEDED_ITEMS=array("search","contract","infocom","enterprise");
include (GLPI_ROOT."/inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
header_nocache();

	switch ($_POST["itemtype"]){
		case TRACKING_TYPE :
			checkTypeRight("update_ticket","1");
			break;
		default :
			if (in_array($_POST["itemtype"],$CFG_GLPI["infocom_types"])){
				checkSeveralRightsOr(array($_POST["itemtype"]=>"w","infocom"=>"w"));
			} else {
				checkTypeRight($_POST["itemtype"],"w");
			}
			break;
	}	

if (isset($_POST["itemtype"])&&isset($_POST["id_field"])&&$_POST["id_field"]){
	$search=$SEARCH_OPTION[$_POST["itemtype"]][$_POST["id_field"]];	
	
	// Specific budget case
	if ($_POST["id_field"]==50) $search["linkfield"]="budget";

	$FIELDNAME_PRINTED=false;

	if ($search["table"]==$LINK_ID_TABLE[$_POST["itemtype"]]){ // field type

		switch ($search["table"].".".$search["linkfield"]){
			case "glpi_cartridgesitems.alarm":
			case "glpi_consumablesitems.alarm":
				dropdownInteger($search["linkfield"],0,-1,100);
				break;
			case "glpi_contracts.duration":
			case "glpi_contracts.notice":
				dropdownInteger($search["field"],0,0,120);
				echo " ".$LANG['financial'][57];
				break;
			case "glpi_softwareslicenses.number":
				dropdownInteger($search["linkfield"],0,1,1000,1,array(-1=>$LANG['software'][4]));
				break;
			case "glpi_contracts.alert":
				dropdownContractAlerting($search["linkfield"],0);
				break;
			case "glpi_tickets.status":
				dropdownStatus($search["linkfield"]);
				break;
			case "glpi_tickets.priority":
				dropdownPriority($search["linkfield"]);
				break;
			default :
				// Specific plugin Type case
				$plugdisplay=false;
				if ($_POST["itemtype"]>1000){
					if (isset($PLUGIN_HOOKS['plugin_types'][$_POST["itemtype"]])){
						$plugdisplay=doOneHook($PLUGIN_HOOKS['plugin_types'][$_POST["itemtype"]], 
							'MassiveActionsFieldsDisplay',
							$_POST["itemtype"],$search["table"],$search["field"],$search["linkfield"]);
					} 
				} 

				$already_display=false;
				if (isset($search['datatype'])){
					switch ($search['datatype']){
						case "date":
							showDateFormItem($search["field"]);
							$already_display=true;
							break;
						case "datetime":
							showDateTimeFormItem($search["field"]);
							$already_display=true;
							break;
						case "bool":
							dropdownYesNo($search["linkfield"]);
							$already_display=true;
							break;
					}
				}

				
				if (!$plugdisplay && !$already_display){
					autocompletionTextField($search["linkfield"],$search["table"],$search["field"],'',40,$_SESSION["glpiactive_entity"]);
				}
				
				break;
		}
	} else { 
		switch ($search["table"]){

			case "glpi_infocoms":  // infocoms case
				switch ($search["field"]){
					case "alert":
						dropdownAlertInfocoms($search["field"]);
					break;

					case "buy_date" :
					case "use_date" :
						showDateFormItem($search["field"]);
						echo "&nbsp;&nbsp;";
					break;
					case "amort_type" :
						dropdownAmortType("amort_type");
					break;
					case "amort_time" :
						dropdownInteger("amort_time",0,0,15);
					break;
					case "warranty_duration" :
						dropdownInteger("warranty_duration",0,0,120);
						echo " ".$LANG['financial'][57]."&nbsp;&nbsp;";
					break;
					default :
						autocompletionTextField($search["field"],$search["table"],$search["field"],'',40,$_SESSION["glpiactive_entity"]);
					break;
				}
			break;
			case "glpi_suppliers_infocoms": // Infocoms suppliers
				dropdown("glpi_suppliers","FK_enterprise",1,$_SESSION["glpiactive_entity"]);
				echo "<input type='hidden' name='field' value='FK_enterprise'>";
				$FIELDNAME_PRINTED=true;
			break;
			case "glpi_budgets": // Infocoms budget
				dropdown("glpi_budgets","budget");
			break;
			case "glpi_ocslinks": // auto_update ocs_link
				dropdownYesNo("_auto_update_ocs");
				echo "<input type='hidden' name='field' value='_auto_update_ocs'>";
				$FIELDNAME_PRINTED=true;
			break;
			case "glpi_users": // users
				switch ($search["linkfield"]){
					case "users_id_assign":
						dropdownUsers($search["linkfield"],0,"own_ticket",0,1,$_SESSION["glpiactive_entity"]);
						break;
					case "users_id_tech":
						dropdownUsersID($search["linkfield"],0,"interface",1,$_SESSION["glpiactive_entity"]);
						break;
					default:
						dropdownAllUsers($search["linkfield"],0,1,$_SESSION["glpiactive_entity"]);
						break;
				}
				break;
			break;
			case "glpi_softwaresversions":
				
				switch ($search["linkfield"]){
					case "use_version":
					case "buy_version":
					$_POST['sID']=$_POST['extra_sID'];
					$_POST['myname']=$search['linkfield'];
					include("dropdownInstallVersion.php");
					break;
				}
				break;


			default : // dropdown case
				// Specific plugin Type case
				$plugdisplay=false;
				if ($_POST["itemtype"]>1000){
					if (isset($PLUGIN_HOOKS['plugin_types'][$_POST["itemtype"]])){
						$plugdisplay=doOneHook($PLUGIN_HOOKS['plugin_types'][$_POST["itemtype"]],
							'MassiveActionsFieldsDisplay',
							$_POST["itemtype"],$search["table"],$search["field"],$search["linkfield"]);
					} 
				} 
				if (!$plugdisplay){
					dropdown($search["table"],$search["linkfield"],1,$_SESSION["glpiactive_entity"]);
				}
				break;
		}
	}
	if (!$FIELDNAME_PRINTED){
		if (empty($search["linkfield"]))
			echo "<input type='hidden' name='field' value='".$search["field"]."'>";
		else {
			echo "<input type='hidden' name='field' value='".$search["linkfield"]."'>";
		}
	}

	echo "&nbsp;<input type=\"submit\" name=\"massiveaction\" class=\"submit\" value=\"".$LANG['buttons'][2]."\" >";
}

?>