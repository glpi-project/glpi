<?php
/*
 * @version $Id: uemailUpdate.php 7079 2008-07-14 13:41:00Z moyo $
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

	define('GLPI_ROOT','..');
	$AJAX_INCLUDE=1;
	$NEEDED_ITEMS=array("mailing","tracking");
	include (GLPI_ROOT."/inc/includes.php");
	
	// Send UTF8 Headers
	header("Content-Type: text/html; charset=UTF-8");
	header_nocache();
	
	checkCentralAccess();
	
	$params = getTrackingFormFields($_POST);
	
	$group = $params["group"];
	$device_type=$params["device_type"];
	$assign=$params["assign"];
	$assign_group=$params["assign_group"];
	$category=$params["category"];
	$priority=$params["priority"];
	$hour=$params["hour"];
	$minute=$params["minute"];
	$request_type=$params["request_type"];
	$name=$params["name"];
	$contents=$params["contents"];
	$target=$params["target"];
	$entity_restrict = (isset($_POST["entity_restrict"])?$_POST["entity_restrict"]:$_SESSION["glpiactive_entity"]);
	$userID = (isset($_POST["userID"])?$_POST["userID"]:$_SESSION["glpiID"]);
	
	echo "<table class='tab_cadre_fixe'>";

	echo "<tr class='tab_bg_2' align='center'>";
	if (isMultiEntitiesMode()){
		echo "<th colspan='4'>";
		echo $LANG["job"][46].":&nbsp;".getDropdownName("glpi_entities",$entity_restrict);
		echo "<input type='hidden' name='FK_entities' value='".$entity_restrict."'>";
		echo "</th></tr>";
	}
	else
		echo "<input type='hidden' name='FK_entities' value='".$entity_restrict."'>";

	$author_rand=0;
	if (haveRight("update_ticket","1")){
		echo "<tr class='tab_bg_2' align='center'>";
		echo "<td>".$LANG["common"][35].":</td>";
		echo "<td align='center' colspan='3'><span id='span_group'>";
		dropdownValue("glpi_groups","FK_group",$group,1,$entity_restrict);
		echo "</span></td></tr>";
	} 


	if ($device_type==0 && $_SESSION["glpiactiveprofile"]["helpdesk_hardware"]!=0){
		echo "<tr class='tab_bg_2'>";
		echo "<td class='center'>".$LANG["help"][24].": </td>";
		echo "<td align='center' colspan='3'>";
		dropdownMyDevices($userID,$entity_restrict);
		dropdownTrackingAllDevices("device_type",$device_type,0,$entity_restrict);
		echo "</td></tr>";
	} 


	if (haveRight("update_ticket","1")){
		echo "<tr class='tab_bg_2'><td class='center'>".$LANG["common"][27].":</td>";
		echo "<td align='center' class='tab_bg_2'>";
		showDateTimeFormItem("date",date("Y-m-d H:i"),1);
		echo "</td>";

		echo "<td class='center'>".$LANG["job"][44].":</td>";
		echo "<td class='center'>";
		dropdownRequestType("request_type",$request_type);
		echo "</td></tr>";
	}


	// Need comment right to add a followup with the realtime
	if (haveRight("comment_all_ticket","1")){
		echo "<tr  class='tab_bg_2'>";
		echo "<td class='center'>";
		echo $LANG["job"][20].":</td>";
		echo "<td align='center' colspan='3'>";
		dropdownInteger('hour',$hour,0,100);

		echo $LANG["job"][21]."&nbsp;&nbsp;";
		dropdownInteger('minute',$minute,0,59);

		echo $LANG["job"][22]."&nbsp;&nbsp;";
		echo "</td></tr>";
	}


	echo "<tr class='tab_bg_2'>";

	echo "<td class='tab_bg_2' align='center'>".$LANG["joblist"][2].":</td>";
	echo "<td align='center' class='tab_bg_2'>";

	dropdownPriority("priority",$priority);
	echo "</td>";

	echo "<td>".$LANG["common"][36].":</td>";
	echo "<td class='center'>";
	dropdownValue("glpi_dropdown_tracking_category","category",$category);
	echo "</td></tr>";

	if (haveRight("assign_ticket","1")||haveRight("steal_ticket","1")){
		echo "<tr class='tab_bg_2' align='center'><td>".$LANG["buttons"][3].":</td>";
		echo "<td colspan='3'>";

		if (haveRight("assign_ticket","1")){
			echo $LANG["job"][6].": ";
			dropdownUsers("assign",$assign,"own_ticket",0,1,$entity_restrict);
			echo "<br>".$LANG["common"][35].": <span id='span_group_assign'>";
			dropdownValue("glpi_groups", "assign_group", $assign_group,1,$entity_restrict);
			echo "</span>";
		} else if (haveRight("steal_ticket","1") || haveRight("own_ticket","1")) {
			echo $LANG["job"][6].":";
			dropdownUsers("assign",$assign,"ID",0,1,$entity_restrict);
		}
		echo "</td></tr>";

	}




	if(isAuthorMailingActivatedForHelpdesk()){

		$query="SELECT email from glpi_users WHERE ID='$userID'";
		
		$result=$DB->query($query);
		$email="";
		if ($result&&$DB->numrows($result))
			$email=$DB->result($result,0,"email");
		echo "<tr class='tab_bg_1'>";
		echo "<td class='center'>".$LANG["help"][8].":</td>";
		echo "<td class='center'>";
		dropdownYesNo('emailupdates',!empty($email));
		echo "</td>";
		echo "<td class='center'>".$LANG["help"][11].":</td>";
		echo "<td><span id='uemail_result'>";
		echo "<input type='text' size='30' name='uemail' value='$email'>";
		echo "</span>";

		echo "</td></tr>";

	}

	echo "</table><br><table class='tab_cadre_fixe'>";
	echo "<tr><th class='center'>".$LANG["common"][57].":";
	echo "</th><th colspan='3' class='left'>";

	echo "<input type='text' size='80' name='name' value='$name'>";
	echo "</th> </tr>";

	
	echo "<tr><th colspan='4' align='center'>".$LANG["job"][11].":";
	echo "</th></tr>";

	echo "<tr class='tab_bg_1'><td colspan='4' align='center'><textarea cols='100' rows='6'  name='contents'>$contents</textarea></td></tr>";

	$max_size=return_bytes_from_ini_vars(ini_get("upload_max_filesize"));
	$max_size/=1024*1024;
	$max_size=round($max_size,1);

	echo "<tr class='tab_bg_1'><td>".$LANG["document"][2]." (".$max_size." ".$LANG["common"][45]."):	";
	echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/aide.png\"class='pointer;' alt=\"aide\"onClick=\"window.open('".$CFG_GLPI["root_doc"]."/front/typedoc.list.php','Help','scrollbars=1,resizable=1,width=1000,height=500')\">";
	echo "</td>";
	echo "<td colspan='3'><input type='file' name='filename' value=\"\" size='25'></td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'>";

	echo "<td colspan='2' class='center'><a href='$target'><img title=\"".$LANG["buttons"][16]."\" alt=\"".$LANG["buttons"][16]."\" src='".$CFG_GLPI["root_doc"]."/pics/reset.png' class='calendrier'></a></td>";



	echo "<td colspan='2' align='center'><input type='submit' name='add' value=\"".$LANG["buttons"][2]."\" class='submit'>";

	echo "</td></tr></table>";
?>
