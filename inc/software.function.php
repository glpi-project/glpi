<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2007 by the INDEPNET Development Team.

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

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}


function showLicensesAdd($ID) {

	global $CFG_GLPI,$LANG;

	if (!haveRight("software","w")) return false;

	echo "<div class='center'>&nbsp;<table class='tab_cadre_fixe' cellpadding='2'>";
	echo "<tr><td align='center' class='tab_bg_2'><strong>";
	echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/software.licenses.php?form=add&amp;sID=$ID\">";
	echo $LANG["software"][12];
	echo "</a></strong></td></tr>";
	echo "</table></div><br>";
}

function showLicenses ($sID,$show_computers=0) {

	global $DB,$CFG_GLPI, $LANG;

	if (!haveRight("software","r")) return false;
	$canedit=haveRight("software","w");
	$canshowcomputer=haveRight("computer","r");
	$ci=new CommonItem();
	$query = "SELECT count(*) AS COUNT  FROM glpi_licenses WHERE (sID = '$sID')";
	$query_update = "SELECT count(glpi_licenses.ID) AS COUNT  FROM glpi_licenses, glpi_software WHERE (glpi_software.ID = glpi_licenses.sID AND glpi_software.update_software = '$sID' and glpi_software.is_update='1')";

	$found_soft=false;
	if ($result = $DB->query($query)) {
		if ($DB->result($result,0,0)!=0) { 
			$nb_licences=$DB->result($result, 0, "COUNT");
			$result_update = $DB->query($query_update);
			$nb_updates=$DB->result($result_update, 0, "COUNT");;
			$installed = getInstalledLicence($sID);
			$tobuy=getLicenceToBuy($sID);
			$isfreeorglobal=isFreeSoftware($sID)||isGlobalSoftware($sID);
			// As t'on utilisé trop de licences en prenant en compte les mises a jours (double install original + mise �jour)
			// Rien si free software
			$pb="";
			if (($nb_licences-$nb_updates-$installed)<0&&!$isfreeorglobal) $pb="class='tab_bg_1_2'";

			echo "<form id='lic_form' name='lic_form' method='post' action=\"".$CFG_GLPI["root_doc"]."/front/software.licenses.php\">";

			echo "<br><div class='center'><table cellpadding='2' class='tab_cadre_fixe'>";
			echo "<tr>";
			if ($canedit&&!$show_computers){
				echo "<th>&nbsp;</th>";
			}

			echo "<th colspan='6' $pb >";
			echo $nb_licences;
			echo "&nbsp;".$LANG["software"][13]."&nbsp;-&nbsp;$nb_updates&nbsp;".$LANG["software"][36]."&nbsp;-&nbsp;$installed&nbsp;".$LANG["software"][19]."&nbsp;-&nbsp;$tobuy&nbsp;".$LANG["software"][37]."</th>";
			echo "<th colspan='1'>";
			echo " ".$LANG["software"][19]." :</th></tr>";
			$i=0;
			echo "<tr>";
			if ($canedit&&!$show_computers){
				echo "<th>&nbsp;</th>";
			}

			echo "<th>".$LANG["software"][5]."</th><th>".$LANG["common"][19]."</th><th>".$LANG["common"][33]."</th><th>".$LANG["software"][32]."</th><th>".$LANG["software"][28]."</th><th>".$LANG["software"][35]."</th>";
			echo "<th>";

			if ($canedit){
				if ($show_computers){
					echo $LANG["buttons"][14]."&nbsp;";
					echo "<select name='update_licenses' id='update_licenses_choice'>";
					echo "<option value=''>-----</option>";
					echo "<option value='update_expire'>".$LANG["software"][32]."</option>";
					echo "<option value='update_buy'>".$LANG["software"][35]."</option>";
					echo "<option value='move'>".$LANG["buttons"][20]."</option>";
					echo "<option value='delete_license'>".$LANG["buttons"][6]."</option>";
					echo "</select>";
	
					$params=array('type'=>'__VALUE__',
							'sID'=>$sID,
					);
					ajaxUpdateItemOnSelectEvent("update_licenses_choice","update_licenses_view",$CFG_GLPI["root_doc"]."/ajax/updateLicenses.php",$params,false);
	
					echo "<span id='update_licenses_view'>\n";
					echo "&nbsp;";
					echo "</span>\n";	
				} else {
					echo $LANG["buttons"][14]."&nbsp;";
					echo "<select name='update_licenses' id='update_licenses_choice'>";
					echo "<option value=''>-----</option>";
					echo "<option value='move_to_software'>".$LANG["buttons"][20]."</option>";
					echo "<option value='delete_similar_license'>".$LANG["buttons"][6]."</option>";
					echo "</select>";
	
					$params=array('type'=>'__VALUE__',
							'sID'=>$sID,
							'sID'=>$sID,
					);
					ajaxUpdateItemOnSelectEvent("update_licenses_choice","update_licenses_view",$CFG_GLPI["root_doc"]."/ajax/updateLicenses.php",$params,false);
	
				}
				echo "<span id='update_licenses_view'>\n";
				echo "&nbsp;";
				echo "</span>\n";	

			} else echo "&nbsp;";

			echo "</th></tr>";
		} else {

			echo "<br><div class='center'><table border='0' width='50%' cellpadding='2'>";
			echo "<tr><th>".$LANG["software"][14]."</th></tr>";
			echo "</table></div>";
		}
	}

	$query = "SELECT count(ID) AS COUNT, version as VERSION, serial as SERIAL, expire as EXPIRE, oem as OEM, oem_computer as OEM_COMPUTER, buy as BUY, ID AS ID  FROM glpi_licenses WHERE (sID = '$sID') GROUP BY version, serial, expire, oem, oem_computer, buy ORDER BY version, serial,oem, oem_computer";
	//echo $query;
	if ($result = $DB->query($query)) {			
		while ($data=$DB->fetch_array($result)) {
			$version=$data["VERSION"];
			$serial=$data["SERIAL"];
			$expire=$data["EXPIRE"];
			$oem=$data["OEM"];
			$oem_computer=$data["OEM_COMPUTER"];
			$buy=$data["BUY"];

			$SEARCH_LICENCE="(glpi_licenses.sID = $sID AND glpi_licenses.serial = '".$serial."'  AND glpi_licenses.oem = '$oem' AND glpi_licenses.oem_computer = '$oem_computer'  AND glpi_licenses.buy = '$buy' ";
			if ($expire=="")
				$SEARCH_LICENCE.=" AND glpi_licenses.expire IS NULL";
			else $SEARCH_LICENCE.=" AND glpi_licenses.expire = '$expire'";

			if ($version=="" || is_null($version))
				$SEARCH_LICENCE.=" AND (glpi_licenses.version='' OR glpi_licenses.version IS NULL))";
			else $SEARCH_LICENCE.=" AND glpi_licenses.version = '$version')";



			$today=date("Y-m-d"); 
			$expirer=0;
			$expirecss="";
			if ($expire!=NULL&&$today>$expire) {$expirer=1; $expirecss="_2";}
			// Get installed licences


			$query_lic = "SELECT glpi_inst_software.ID AS ID, glpi_licenses.ID AS lID, glpi_computers.deleted as deleted, ";
			$query_lic .= " glpi_infocoms.ID as infocoms, glpi_licenses.comments AS COMMENT, ";
			$query_lic .= " glpi_inst_software.cID AS cID, glpi_computers.name AS cname FROM glpi_licenses";
			$query_lic .= " LEFT JOIN glpi_inst_software ";
			$query_lic .= " ON ( glpi_inst_software.license = glpi_licenses.ID )";
			$query_lic .= " LEFT JOIN glpi_computers ON (glpi_computers.deleted='0' AND glpi_computers.is_template='0' AND glpi_inst_software.cID= glpi_computers.ID) ";
			$query_lic .= " LEFT JOIN glpi_infocoms ON (glpi_infocoms.device_type='".LICENSE_TYPE."' AND glpi_infocoms.FK_device=glpi_licenses.ID) ";
			$query_lic .= " WHERE $SEARCH_LICENCE ORDER BY cname";
			//echo $query_lic;
			$result_lic = $DB->query($query_lic);
			$num_tot=$DB->numrows($result_lic);

			$num_inst=0;
			$firstID=0;
			$freeID=0;

			while ($data_lic=$DB->fetch_array($result_lic)){
				if ($firstID==0){
					$firstID=$data_lic['lID'];
				}
				if ($data_lic['cID']>0){
					$num_inst++;
				} else {
					$freeID=$data_lic['lID'];
				}
			}

			$DB->data_seek($result_lic,0);

			$restant=$num_tot-$num_inst;

			
			echo "<tr class='tab_bg_1' valign='top'>";
			if ($canedit&&!$show_computers){
				$found_soft=true;
				echo "<td><input type='checkbox' name='license_".$data['ID']."'></td>";
			}
			echo "<td class='center'><strong>".$version."</strong></td>";
			echo "<td class='center'><strong>".$serial."</strong></td>";
			echo "<td class='center'><strong>";
			echo $num_tot;
			echo "</strong></td>";

			echo "<td align='center' class='tab_bg_1$expirecss'><strong>";
			if ($expire==NULL)
				echo $LANG["software"][26];
			else {
				if ($expirer) echo $LANG["software"][27];
				else echo $LANG["software"][25]."&nbsp;".convDate($expire);
			}

			echo "</strong></td>";
			// OEM
			if ($data["OEM"]) {
				$comp=new Computer();
				$comp->getFromDB($data["OEM_COMPUTER"]);
			}
			echo "<td align='center' class='tab_bg_1".($data["OEM"]&&!isset($comp->fields['ID'])?"_2":"")."'>".($data["OEM"]?$LANG["choice"][1]:$LANG["choice"][0]);
			if ($data["OEM"]) {
				echo "<br><strong>";
				if (isset($comp->fields['ID']))
					echo "<a href='".$CFG_GLPI["root_doc"]."/front/computer.form.php?ID=".$comp->fields['ID']."'>".$comp->fields['name']."</a>";
				else echo "N/A";
				echo "</strong>";
			} 
			echo "</td>";

			if ($serial!="free"){
				// BUY
				echo "<td class='center'>".($data["BUY"]?$LANG["choice"][1]:$LANG["choice"][0]);
				echo "</td>";
			} else 
				echo "<td>&nbsp;</td>";

			echo "<td class='center'>";


			// Logiciels install� :
			echo "<table width='100%'>";

			// Restant	

			echo "<tr><td class='center'>";

			if (!$show_computers){
				echo $LANG["software"][19].": $num_inst&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			}	

//			$query_new="SELECT glpi_licenses.ID as ID FROM glpi_licenses WHERE $SEARCH_LICENCE";	
			//echo $query_new;	
//			if ($result_new = $DB->query($result_lic)) 

			if ($firstID&&$serial!="free"&&$serial!="global"&&$canedit) {
				echo $LANG["software"][20].":";
				echo "<select name='stock_licenses_$firstID'>";
				if (max(0,$restant-100)>0) echo "<option value='0'>0</option>";
				for ($i=max(0,$restant-100);$i<=$restant+100;$i++)
					echo "<option value='$i' ".($i==$restant?" selected ":"").">$i</option>";
				echo "</select>";
				echo "<input type='hidden' name='nb_licenses_$firstID' value='$restant'>";
				echo "<input type='image' name='update_stock_licenses' value='$firstID' src='".$CFG_GLPI["root_doc"]."/pics/actualiser.png' class='calendrier'>";
			}
			if (($serial=="free"||$serial=="global")){
				// Display infocoms
				echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>";
				showDisplayInfocomLink(LICENSE_TYPE,$firstID,1);
				echo "</strong>";
			}

			if ($restant>0||$serial=="free"||$serial=="global") {
				if ($firstID>0){
					echo "</td><td class='center'>";
					if ($canedit){
						if (($serial=="free"||$serial=="global")){
							echo "<strong><a href=\"".$CFG_GLPI["root_doc"]."/front/software.licenses.php?delete=delete&amp;ID=$firstID\">";
							echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/delete.png\" alt='".$LANG["buttons"][6]."' title='".$LANG["buttons"][6]."'>";
							echo "</a></strong>";
							if ($CFG_GLPI["license_deglobalisation"]){
								echo "&nbsp;&nbsp;<a href=\"javascript:confirmAction('".addslashes($LANG["common"][40])."\\n".addslashes($LANG["common"][39])."','".$CFG_GLPI["root_doc"]."/front/software.licenses.php?unglobalize=unglobalize&amp;sID=$sID&amp;ID=$firstID')\" title=\"".$LANG["common"][39]."\">".$LANG["common"][38]."</a>&nbsp;";	
								echo "<img src='".$CFG_GLPI["root_doc"]."/pics/aide.png' alt=\"".$LANG["common"][39]."\" title=\"".$LANG["common"][39]."\">";
							}
						}
						echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong><a href=\"".$CFG_GLPI["root_doc"]."/front/software.licenses.php?form=update&amp;lID=$firstID&amp;sID=$sID\">";
						echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/edit.png\" alt='".$LANG["buttons"][14]."' title='".$LANG["buttons"][14]."'>";
						echo "</a></strong>";
					} else {
						echo "&nbsp;";
					}
				}
			}

			// Add select all checkbox
			if ($show_computers&&$canedit){
				if ($num_inst>0){
					if ($serial!="free"&&$serial!="global"){
						echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$LANG["search"][7].":";
						$rand=mt_rand();
	
						$found_soft=true;
						echo "<input type='checkbox' onclick='toggle$rand();'>";
						echo "<script type='text/javascript' >\n";
						echo "function toggle$rand(){\n";
						while ($data_inst=$DB->fetch_array($result_lic)){
							if ($data_inst['cID']>0){
								echo " var lic=window.document.getElementById('license_".$data_inst["lID"]."');";
								echo " if (lic.checked) \n";
								echo "      lic.checked = false;\n";
								echo " else lic.checked = true;\n";
							}
						}
						echo "}</script>\n";
						$DB->data_seek($result_lic,0);
					} else {
						echo "<input type='checkbox' name='license_".$data['ID']."'>";
					}
				} 
			}		

			echo "</td></tr>";


			// Logiciels install�
			if ($show_computers){
				while ($data_inst=$DB->fetch_array($result_lic)){
					if ($data_inst['cID']>0){
						echo "<tr class='tab_bg_1".(($data["OEM"]&&$data["OEM_COMPUTER"]!=$data_inst["cID"])||$data_inst["deleted"]?"_2":"")."'><td class='center'>";
	
						if ($serial!="free"&&$serial!="global"&&$canedit){
							$found_soft=true;
							echo "<input type='checkbox' name='license_".$data_inst["lID"]."' id='license_".$data_inst["lID"]."'>";
						}
						$ci->getFromDB(COMPUTER_TYPE,$data_inst["cID"]);
						
						echo "&nbsp;<strong>";
						echo $ci->getLink($canshowcomputer);
						echo "</strong></td><td class='center'>";
	
						// Comment
						if (!empty($data_inst["COMMENT"])) {
							echo "<img onmouseout=\"cleanhide('comment_".$data_inst["ID"]."')\" onmouseover=\"cleandisplay('comment_".$data_inst["ID"]."')\" src=\"".$CFG_GLPI["root_doc"]."/pics/aide.png\" alt=''>";
							echo "<div class='over_link' id='comment_".$data_inst["ID"]."'>".nl2br($data_inst["COMMENT"])."</div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
						}
						// delete
						if ($canedit){
							echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/software.licenses.php?uninstall=uninstall&amp;ID=".$data_inst["ID"]."&amp;cID=".$data_inst["cID"]."\">";
							echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/remove.png\" alt='".$LANG["buttons"][5]."' title='".$LANG["buttons"][5]."'>";
							echo "</a>";
						}
	
						if ($serial!="free"&&$serial!="global"){
							echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
							if ($canedit){
								echo "<strong><a href=\"".$CFG_GLPI["root_doc"]."/front/software.licenses.php?form=update&amp;lID=".$data_inst["lID"]."&amp;sID=$sID\">";
								echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/edit.png\" alt='".$LANG["buttons"][14]."' title='".$LANG["buttons"][14]."'>";
								echo "</a></strong>";
							}
							// Display infocoms
							echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>";
							showDisplayInfocomLink(LICENSE_TYPE,$data_inst["lID"],1);
							echo "</strong>";
						}
	
						echo "</td></tr>";
					}
				}
			}
			echo "</table></td>";

			echo "</tr>";

		}
	}	
	echo "</table></div>\n\n";
	if ($found_soft){
		echo "<div>";
		echo "<table width='950px'>";
		echo "<tr><td><img src=\"".$CFG_GLPI["root_doc"]."/pics/arrow-left.png\" alt=''></td><td><a onclick= \"if ( markAllRows('lic_form') ) return false;\" href='".$_SERVER['PHP_SELF']."?ID=$sID&amp;select=all'>".$LANG["buttons"][18]."</a></td>";
	
		echo "<td>/</td><td ><a onclick=\"if ( unMarkAllRows('lic_form') ) return false;\" href='".$_SERVER['PHP_SELF']."?ID=$sID&amp;select=none'>".$LANG["buttons"][19]."</a>";
		echo "</td><td class='left' width='80%'>&nbsp;";
		echo "</td></table></div>";
	}

	echo "</form>";
}


function showLicenseForm($target,$action,$sID,$lID="") {

	global $CFG_GLPI, $LANG;

	if (!haveRight("software","w")) return false;

	$show_infocom=false;

	switch ($action){
		case "add" :
			$title= $LANG["software"][15]." ($sID):";
		$button= $LANG["buttons"][8];
		$ic=new Infocom();

		if ($ic->getFromDBforDevice(SOFTWARE_TYPE,$sID))
			$show_infocom=true;

		break;
		case "update" :
			$title = $LANG["software"][34]." ($lID):";
		$button= $LANG["buttons"][14];
		break;
	}

	// Get previous values or defaults values
	$values=array();
	// defaults values :
	$values['version']='';
	$values['serial']='';
	$values['expire']="0000-00-00";
	$values['oem']=0;
	$values["oem_computer"]='';
	$values["comments"]='';
	$values['buy']=1;

	if (isset($_POST)&&!empty($_POST)){ // Get from post form
		foreach ($values as $key => $val){
			if (isset($_POST[$key])){
				$values[$key]=$_POST[$key];
			}
		}

	} else if (!empty($lID)){ // Get from DB
		$lic=new License();
		$lic->getfromDB($lID);
		$values=$lic->fields;
	}
	
	if (empty($values['expire'])) $values['expire']="0000-00-00";


	echo "<div class='center'><strong>";
	echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/software.form.php?ID=$sID\">";
	echo $LANG["buttons"][13]."</strong>";
	echo "</a><br>";

	echo "<form name='form' method='post' action=\"$target\">";

	echo "<table class='tab_cadre'><tr><th colspan='3'>$title</th></tr>";


	echo "<tr class='tab_bg_1'><td>".$LANG["software"][5]."</td>";
	echo "<td>";
	autocompletionTextField("version","glpi_licenses","version",$values["version"],20);
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$LANG["software"][16]."</td>";
	echo "<td>";

	$readonly="";

	if ($action == "add")
	{
		switch ($CFG_GLPI["licenses_management_restrict"])
		{
			case 2 :
				$readonly="";
			break;
			case 1 : 
				$values["serial"]="global";
				$readonly="readonly";
			break;
			case 0 :
				$values["serial"]="free";
				$readonly="readonly";
			break;	
		}	
	}
	elseif ($values["serial"]=="free"||$values["serial"]=="global") $readonly="readonly";	

	autocompletionTextField("serial","glpi_licenses","serial",$values["serial"],20,$readonly);
	echo "</td></tr>";

	if ($action!="update"){
		echo "<tr class='tab_bg_1'><td>";
		echo $LANG["printers"][26].":</td><td><select name=number>";
		echo "<option value='1' selected>1</option>";
		for ($i=2;$i<=1000;$i++)
			echo "<option value='$i'>$i</option>";
		echo "</select></td></tr>";
	}

	if ($show_infocom){
		echo "<tr class='tab_bg_1'><td>".$LANG["financial"][3].":</td><td>";
		showDisplayInfocomLink(SOFTWARE_TYPE,$sID);
		echo "</td></tr>"; 
	}

	echo "<tr class='tab_bg_1'><td>".$LANG["search"][9].":</td><td>";
	showCalendarForm("form","expire",$values['expire']);
	echo "</td></tr>"; 

	// OEM
	echo "<tr class='tab_bg_1'><td>".$LANG["software"][28]."</td><td>";
	dropdownYesNo("oem",$values['oem']);
	dropdownValue("glpi_computers","oem_computer",$values["oem_computer"]);

	echo "</td></tr>";
	// BUY
	echo "<tr class='tab_bg_1'><td>".$LANG["software"][35]."</td><td>";
	dropdownYesNo("buy",$values['buy']);
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$LANG["common"][25]."</td><td>";
	echo "<textarea name='comments' rows='6' cols='40'>".$values['comments']."</textarea>";
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'>";
	echo "<td align='center' colspan='3'>";
	echo "<input type='hidden' name='sID' value=".$sID.">";
	if ($action=="update")
		echo "<input type='hidden' name='ID' value=".$lID.">";
	echo "<input type='hidden' name='form' value=".$action.">";
	echo "<input type='submit' name='$action' value=\"".$button."\" class='submit'>";
	echo "</td>";

	echo "</table></form></div>";
}



function updateNumberLicenses($likeID,$number,$new_number){
	global $DB;

	$lic=new License();

	// Delete unused licenses
	if ($number>$new_number){
		if ($lic->getFromDB($likeID)){
			$SEARCH_LICENCE="(glpi_licenses.sID = ".$lic->fields["sID"]." AND glpi_licenses.serial = '".$lic->fields["serial"]."'  AND glpi_licenses.oem = '".$lic->fields["oem"]."' AND glpi_licenses.oem_computer = '".$lic->fields["oem_computer"]."'  AND glpi_licenses.buy = '".$lic->fields["buy"]."' ";
			if ($lic->fields["expire"]=="")
				$SEARCH_LICENCE.=" AND glpi_licenses.expire IS NULL)";
			else $SEARCH_LICENCE.=" AND glpi_licenses.expire = '".$lic->fields["expire"]."')";



			for ($i=0;$i<$number-$new_number;$i++){
				$query_first="SELECT glpi_licenses.ID as ID, glpi_inst_software.license as iID FROM glpi_licenses LEFT JOIN glpi_inst_software ON glpi_inst_software.license = glpi_licenses.ID WHERE $SEARCH_LICENCE";

				if ($result_first = $DB->query($query_first)) {			
					if ($lic->fields["serial"]=="free"||$lic->fields["serial"]=="global")
						$ID=$DB->result($result_first,0,"ID");
					else{
						$fin=0;
						while (!$fin&&$temp=$DB->fetch_array($result_first))
							if ($temp["iID"]==NULL){
								$fin=1;
								$ID=$temp["ID"];
							}
					}
					if (!empty($ID)){
						$lic->delete(array("ID"=>$ID));
					}
				}

			}
		}
		// Create new licenses
	} else if ($number<$new_number){ 
		$lic->getFromDB($likeID);
		unset($lic->fields["ID"]);

		if (is_null($lic->fields["expire"]))
			unset($lic->fields["expire"]);

		for ($i=0;$i<$new_number-$number;$i++){
			unset($lic->fields["ID"]);
			$lic->addToDB();
		}


	}

}


function installSoftware($cID,$lID,$sID='',$dohistory=1) {

	global $DB;


	if (!empty($lID)&&$lID>0){
		
		$query_exists = "SELECT ID FROM glpi_inst_software WHERE cID=".$cID." AND license=".$lID;
		$result = $DB->query($query_exists);
		if ($DB->numrows($result) > 0)
			return $DB->result($result,0,"ID");
		else
		{
			$query = "INSERT INTO glpi_inst_software VALUES (NULL,$cID,$lID)";
			if ($result = $DB->query($query)) {
				$newID=$DB->insert_id();
				if ($dohistory){
					$lic=new License();
					$lic->getFromDB($lID);
					$soft=new Software();
					if ($soft->getFromDB($lic->fields["sID"])){
						$changes[0]='0';
						$changes[1]="";
						$changes[2]=addslashes($soft->fields["name"]." (v. ".$lic->fields["version"].")");
						// history log
						historyLog ($cID,COMPUTER_TYPE,$changes,0,HISTORY_INSTALL_SOFTWARE);
						$comp=new Computer();
						$comp->getFromDB($cID);
						$changes[2]=addslashes($comp->fields["name"]." (v. ".$lic->fields["version"].")");
						historyLog ($lic->fields["sID"],SOFTWARE_TYPE,$changes,0,HISTORY_INSTALL_SOFTWARE);
					}
				}
				return $newID;
			} else {
				return false;
			}
		}
	} else if ($lID<0&&!empty($sID)){ // Auto Add a license
		$lic=new License();
		$newinput=array();
		$newinput['buy']=0;
		$newinput['sID']=$sID;
		$newinput['serial']='Automatic Add';
		$lID=$lic->add($newinput);

		$query = "INSERT INTO glpi_inst_software VALUES (NULL,$cID,$lID)";
		if ($result = $DB->query($query)) {
			$newID=$DB->insert_id();
			if ($dohistory){
				$soft=new Software();
				if ($soft->getFromDB($sID)){
					$changes[0]='0';
					$changes[1]="";
					$changes[2]=addslashes($soft->fields["name"]);
					// history log
					historyLog ($cID,COMPUTER_TYPE,$changes,0,HISTORY_INSTALL_SOFTWARE);
					$comp=new Computer();
					$comp->getFromDB($cID);
					$changes[2]=addslashes($comp->fields["name"]);
					historyLog ($sID,SOFTWARE_TYPE,$changes,0,HISTORY_INSTALL_SOFTWARE);
				}
			}

			return $newID;
		} else {
			return false;
		}

	}
}

function uninstallSoftware($ID,$dohistory=1) {

	global $DB;

	// license data for history
	if ($dohistory){
		$query2 = "SELECT * FROM glpi_inst_software WHERE (ID = '$ID')";
		$result2=$DB->query($query2);
		$data=$DB->fetch_array($result2);
		$lic=new License();
		$lic->getFromDB($data["license"]);
	}

	$query = "DELETE FROM glpi_inst_software WHERE (ID = '$ID')";

	if ($result = $DB->query($query)) {
		if ($dohistory){
			$soft=new Software();
			if ($soft->getFromDB($lic->fields["sID"])){
				$changes[0]='0';
				$changes[1]=addslashes($soft->fields["name"]." (v. ".$lic->fields["version"].")");
				$changes[2]="";
				// history log
				historyLog ($data["cID"],COMPUTER_TYPE,$changes,0,HISTORY_UNINSTALL_SOFTWARE);
				$comp=new Computer();
				$comp->getFromDB($data["cID"]);
				$changes[1]=addslashes($comp->fields["name"]." (v. ".$lic->fields["version"].")");
				historyLog ($lic->fields["sID"],SOFTWARE_TYPE,$changes,0,HISTORY_UNINSTALL_SOFTWARE);

			}
		}

		return true;
	} else {
		return false;
	}
}

function showSoftwareInstalled($instID,$withtemplate='') {

	global $DB,$CFG_GLPI, $LANG;
	if (!haveRight("software","r")) return false;
	$comp=new Computer();
	$comp->getFromDB($instID);
	$FK_entities=$comp->fields["FK_entities"];

	$query_cat = "SELECT 1 as TYPE, glpi_dropdown_software_category.name as category, glpi_software.category as category_id, glpi_software.name as softname, glpi_inst_software.license as license, glpi_inst_software.ID as ID,glpi_licenses.expire,glpi_software.deleted, glpi_licenses.sID, GROUP_CONCAT( DISTINCT CONCAT(CONCAT_WS(' - ',glpi_licenses.version, glpi_licenses.serial),'$$',glpi_inst_software.ID) SEPARATOR '$$$$') AS version, glpi_licenses.serial, glpi_licenses.version AS orig_version, glpi_licenses.oem, glpi_licenses.oem_computer, glpi_licenses.buy	
	FROM glpi_inst_software 
	LEFT JOIN glpi_licenses ON ( glpi_inst_software.license = glpi_licenses.ID )
	LEFT JOIN glpi_software ON (glpi_licenses.sID = glpi_software.ID) 
	LEFT JOIN glpi_dropdown_software_category ON (glpi_dropdown_software_category.ID = glpi_software.category)";

	$query_cat.=" WHERE glpi_inst_software.cID = '$instID' AND glpi_software.category > 0 
			GROUP BY glpi_licenses.sID"; 

    $query_nocat = "SELECT 2 as TYPE, glpi_dropdown_software_category.name as category, glpi_software.category as category_id, glpi_software.name as softname, glpi_inst_software.license as license, glpi_inst_software.ID as ID,glpi_licenses.expire,glpi_software.deleted, glpi_licenses.sID, GROUP_CONCAT( DISTINCT CONCAT(CONCAT_WS(' - ',glpi_licenses.version, glpi_licenses.serial),'$$',glpi_inst_software.ID) SEPARATOR '$$$$') AS version, glpi_licenses.serial, glpi_licenses.version AS orig_version, glpi_licenses.oem, glpi_licenses.oem_computer, glpi_licenses.buy  
        FROM glpi_inst_software 
	LEFT JOIN glpi_licenses ON ( glpi_inst_software.license = glpi_licenses.ID ) 
        LEFT JOIN glpi_software ON (glpi_licenses.sID = glpi_software.ID)  
        LEFT JOIN glpi_dropdown_software_category ON (glpi_dropdown_software_category.ID = glpi_software.category)"; 
    $query_nocat.= " WHERE glpi_inst_software.cID = '$instID' AND (glpi_software.category <= 0 OR glpi_software.category IS NULL ) 
		GROUP BY glpi_licenses.sID"; 
    $query="( $query_cat ) UNION ($query_nocat) ORDER BY TYPE, category, softname, version";

	$DB->query("SET SESSION group_concat_max_len = 9999999;");

	$result = $DB->query($query);
	$i = 0;

	echo "<br><br><div class='center'><table class='tab_cadre_fixe'>";
	
	if((empty($withtemplate) || $withtemplate != 2) && haveRight("software","w")) {
		echo "<tr class='tab_bg_1'><td align='center' colspan='5'>";
		echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/front/software.licenses.php\">";
	
		echo "<div class='software-instal'>";
		echo "<input type='hidden' name='cID' value='$instID'>";
		dropdownSoftwareToInstall("licenseID",$withtemplate,$FK_entities);
		echo "<input type='submit' name='install' value=\"".$LANG["buttons"][4]."\" class='submit'>";
		echo "</div>";
		echo "</form>";
		echo "</td></tr>";
	}
	
	echo "<tr><th colspan='6'>".$LANG["software"][17].":</th></tr>";
	
	$cat = -1;
	
	if ($DB->numrows($result)){
		while ($data=$DB->fetch_array($result)) {
			if($data["category_id"] != $cat){
				$cat = displayCategoryHeader($data,$cat);
			}
								
			displaySoftsByCategory($data,$instID,$withtemplate);
		}
	
		echo "</table></div></td></tr>";
			
		$q="SELECT count(*) FROM glpi_software WHERE deleted='0' AND is_template='0'";
		$result = $DB->query($q);
		$nb = $DB->result($result,0,0);
	
	}


	echo "</table></div>";

}


function displayCategoryHeader($data,$cat)
{
	global $LANG,$CFG_GLPI;
	$expirecss='';
	
	// Close old one
	if ($cat != -1){
		echo "</table></div></td></tr>";
	}
		
	$display = "none";
						
	$cat = $data["category_id"];
	$catname=$data["category"];
	if (!$cat){
		$catname=$LANG["softwarecategories"][3];
		$display = $CFG_GLPI["expand_soft_not_categorized"];
	} 
	else
		$display = $CFG_GLPI["expand_soft_categorized"];
	

	echo "	<tr class='tab_bg_2$expirecss'>";
	echo "  	<td align='center' colspan='5'>"; 
	echo "			<a  href=\"javascript:showHideDiv('softcat$cat','imgcat$cat','".GLPI_ROOT."/pics/folder.png','".GLPI_ROOT."/pics/folder-open.png');\">";
	echo "				<img alt='' name='imgcat$cat' src=\"".GLPI_ROOT."/pics/folder".(!$display?'':"-open").".png\">&nbsp;<strong>".$catname."</strong>";
	echo "			</a>"; 
	echo "		</td>"; 
	echo "	</tr>"; 
	echo "<tr class='tab_bg_2$expirecss'>";
	echo "		<td colspan='5'>
			     <div align='center' id='softcat$cat' ".(!$display?"style=\"display:none;\"":'').">"; 
	echo "			<table class='tab_cadre_fixe'>";
	echo "				<tr>"; 
	echo "					<th>".$LANG["common"][16]."</th><th>".$LANG["software"][11]."</th><th>".$LANG["software"][32]."</th><th>".$LANG["software"][28]."</th><th>".$LANG["software"][35]."</th>"; 
	echo "				</tr>";
	return $cat;
}

function displaySoftsByCategory($data,$instID,$withtemplate)
{
	global $LANG,$CFG_GLPI;
	$expirecss='';
	$expirer=0;

	$lID = $data["license"];
	$ID = $data["ID"];
	$multiple=false;

	$today=date("Y-m-d"); 

	if ($data['expire']!=NULL&&$today>$data['expire']) {$expirer=1; $expirecss="_2";}
	
	if ($data['deleted']) {$expirer=1; $expirecss="_2";}

	echo "<tr class='tab_bg_1$expirecss'>";
	echo "<td class='center'><strong><a href=\"".$CFG_GLPI["root_doc"]."/front/software.form.php?ID=".$data['sID']."\">";
	echo $data["softname"].($CFG_GLPI["view_ID"]?" (".$data['ID'].")":"")."</a>";
	echo "</strong>";
	echo "</td>";
	echo "<td>";

	if (!strpos($data["version"],"$$$$")){
		echo $data["serial"]." - ".$data["orig_version"];
		if (empty($withtemplate) || $withtemplate != 2){
			echo " - <a href=\"".$CFG_GLPI["root_doc"]."/front/software.licenses.php?uninstall=uninstall&amp;ID=$ID&amp;cID=$instID\">";
			echo "<strong>".$LANG["buttons"][5]."</strong></a>";
		}
	} else {
		$multiple=true;
		$split=explode("$$$$",$data["version"]);
		$count_display=0;
		$out="";
		for ($k=0;$k<count($split);$k++){
			if ($count_display) echo "<br>";
			$count_display++;
			$split2=explode("$$",$split[$k]);
			echo  $split2[0];
			
			if (isset($split2[1]) && is_numeric($split2[1])
				&& (empty($withtemplate) || $withtemplate != 2)){
				echo " - <a href=\"".$CFG_GLPI["root_doc"]."/front/software.licenses.php?uninstall=uninstall&amp;ID=".$split2[1]."&amp;cID=$instID\">";
				echo "<strong>".$LANG["buttons"][5]."</strong></a>";
			}
		}
	}
	echo "</td>";
	echo "<td class='center'><strong>";
	if ($data['expire']==NULL)
		echo $LANG["software"][26];
	else {
		if ($expirer) echo $LANG["software"][27];
		else echo $LANG["software"][25]."&nbsp;".$data['expire'];
	}
	
	echo "</strong></td>";
	if ($data['serial']!="free"&&$data['serial']!="global"){
		// OEM
		if ($data["oem"]) {
			$comp=new Computer();
			$comp->getFromDB($data["oem_computer"]);
		}
		echo "<td align='center' class='tab_bg_1".($expirer||($data["oem"]&&$comp->fields['ID']!=$instID)?"_2":"")."'>".($data["oem"]?$LANG["choice"][1]:$LANG["choice"][0]);
		if ($data["oem"]) {
			echo "<br><strong>";
			if (isset($comp->fields['ID']))
				echo "<a href='".$CFG_GLPI["root_doc"]."/front/computer.form.php?ID=".$comp->fields['ID']."'>".$comp->fields['name']."</a>";
			else echo "N/A";
			echo "</strong>";
		} 
		echo "</td>";
	
		// BUY
		echo "<td class='center'>".($data["buy"]?$LANG["choice"][1]:$LANG["choice"][0]);
		echo "</td>";								
	}
	else {
		echo "<td>&nbsp;</td><td>&nbsp;</td>";
	}
	echo "</tr>";
}



function unglobalizeLicense($ID){
	global $DB;
	$license=new License();
	$license->getFromDB($ID);
	// Check if it is a real global license
	if ($license->fields["serial"]=="free"||$license->fields["serial"]=="global"){


		$query = "SELECT * FROM glpi_inst_software WHERE license = '$ID'";
		$result=$DB->query($query);

		if (($nb=$DB->numrows($result))>0){
			// Update item to unit management :
			$input=$license->fields;
			$input["serial"]="_".$license->fields["serial"]."_";
			
			// skip first
			$data=$DB->fetch_array($result);
			if ($license->fields["oem"]){
				$input["oem_computer"]=$data["cID"];
			} 
			$license->update($input);

			$input=$license->fields;
			$input["_duplicate_license"]=$ID;
			unset($input["ID"]);
			
			// Get ID of the inst_software
			while ($data=$DB->fetch_array($result)){
				if ($license->fields["oem"]){
					$input["oem_computer"]=$data["cID"];
				} else {
					$input["oem"]=0;
				}

				// Add new Item
				unset($license->fields);
				if ($newID=$license->add($input)){
					// Update inst_software
					$query2="UPDATE glpi_inst_software SET license='$newID' WHERE ID='".$data["ID"]."'";
					$DB->query($query2);
				}
			}
		}
	}

}

function countInstallations($sID,$nohtml=0) {

	global $DB,$CFG_GLPI, $LANG;


	// Get total
	$total = getLicenceNumber($sID);
	$out="";
	if ($total!=0) {

		if (isFreeSoftware($sID)) {
			// Get installed
			$installed = getInstalledLicence($sID);
			if (!$nohtml)
				$out.= "<i>".$LANG["software"][39]."</i>&nbsp;&nbsp;".$LANG["software"][19].": <strong>$installed</strong>";
			else $out.= $LANG["software"][39]."  ".$LANG["software"][19].": $installed";
		} else if (isGlobalSoftware($sID)){
			$installed = getInstalledLicence($sID);
			if (!$nohtml)
				$out.= "<i>".$LANG["software"][38]."</i>&nbsp;&nbsp;".$LANG["software"][19].": <strong>$installed</strong>";
			else $out.= $LANG["software"][38]."  ".$LANG["software"][19].": $installed";

		}
		else {

			// Get installed
			$i=0;
			$installed = getInstalledLicence($sID);

			// Get remaining
			$remaining = max(0,$total - $installed);

			// Output
			if (!$nohtml){
				$out.= "<table cellpadding='2' cellspacing='0'><tr>";
				$out.= "<td width='35%'>".$LANG["software"][19].": <strong>$installed</strong></td>";
			} else $out.= "  ".$LANG["software"][19].": $installed";

			$color="red";

			if ($remaining == 0) {
				$color="green";
			} else {
				$color="blue";
			}			

			if (!$nohtml){
				$remaining = "<span class='$color'>$remaining";
				$remaining .= "</span>";
				$out.= "<td width='20%'>".$LANG["software"][20].": <strong>$remaining</strong></td>";
				$out.= "<td width='20%'>".$LANG["common"][33].": <strong>".$total."</strong></td>";
			} else {
				$out.= "  ".$LANG["software"][20].": $remaining";
				$out.= "  ".$LANG["common"][33].": ".$total;
			}

			$tobuy=getLicenceToBuy($sID);
			if ($tobuy>0){
				if (!$nohtml)
					$out.= "<td width='25%'>".$LANG["software"][37].": <strong><span class='red'>".$tobuy."</span></strong></td>";
				else $out.= "  ".$LANG["software"][37].": ".$tobuy;
			} else {
				if (!$nohtml)
					$out.= "<td width='20%'>&nbsp;</td>";
			}
			if (!$nohtml)
				$out.= "</tr></table>";
		} 
	} else {
		if (!$nohtml)
			$out.= "<div class='center'><i>".$LANG["software"][40]."</i></div>";
		else $out.= $LANG["software"][40];
	}
	return $out;
}	

function moveSimilarLicensesToSoftware($lID,$sID){
	global $DB;
	$lic=new License();
	if ($lic->getFromDB($lID)){
		$query="UPDATE glpi_licenses SET sID='$sID' WHERE version='".addslashes($lic->fields['version'])."'
			AND serial='".addslashes($lic->fields['serial'])."'
			AND oem='".addslashes($lic->fields['oem'])."'
			AND oem_computer='".addslashes($lic->fields['oem_computer'])."'
			AND buy='".addslashes($lic->fields['buy'])."'
			AND sID='".addslashes($lic->fields['sID'])."' ";
		if ($lic->fields['expire']=="")
			$query.=" AND expire IS NULL";
		else $query.=" AND .expire = '".addslashes($lic->fields['expire'])."'";
		$DB->query($query);
	}
}

function deleteSimilarLicenses($lID){
	global $DB;
	$lic=new License();
	if ($lic->getFromDB($lID)){
		
		$query="SELECT ID FROM glpi_licenses WHERE version='".addslashes($lic->fields['version'])."'
			AND serial='".addslashes($lic->fields['serial'])."'
			AND oem='".addslashes($lic->fields['oem'])."'
			AND oem_computer='".addslashes($lic->fields['oem_computer'])."'
			AND buy='".addslashes($lic->fields['buy'])."'
			AND sID='".addslashes($lic->fields['sID'])."' ";
		if ($lic->fields['expire']=="")
			$query.=" AND expire IS NULL";
		else $query.=" AND .expire = '".addslashes($lic->fields['expire'])."'";
		
		if ($result=$DB->query($query)){
			while ($data=$DB->fetch_array($result)){
				$lic->delete(array('ID'=>$data['ID']));
			}
		}
	}
}

function moveLicensesToLicense($tomove=array(),$lID){
	global $DB;
	
	$lic=new License();
	$lic2=new License();

	if (count($tomove)&&$lic->getFromDB($lID)){

		if ($lic->fields['serial']=='free'||$lic->fields['serial']=='global'){
			// Destination is global : Only move inst_software and delete old license if unused
			foreach ($tomove as $moveID){
				if ($moveID!=$lID && $lic2->getFromDB($moveID)){
					$query="UPDATE glpi_inst_software SET license='$lID' WHERE license='$moveID'";
					$DB->query($query);

					if (getInstallionsForLicense($moveID)==0){
						$lic2->delete(array('ID'=>$moveID));
					}
				}
			}
		} else {
			// Individual one : if original is global create a copy else copy license
			foreach ($tomove as $moveID){
				if ($moveID!=$lID && $lic2->getFromDB($moveID)){
					if ($lic2->fields['serial']=='free'||$lic2->fields['serial']=='global'){
						// Create a copy of the original one foreach installation and move inst_software
						$query="SELECT * FROM glpi_inst_software WHERE license='$moveID'";

						$input=$lic->fields;
						unset($input['ID']);

						if ($result=$DB->query($query)){
							while ($data_inst=$DB->fetch_array($result)){
								unset($lic2->fields);
								$newID=$lic2->add($input);
								$query="UPDATE glpi_inst_software SET license='$newID' WHERE ID='".$data_inst['ID']."'";
								$DB->query($query);
							}
						}
					} else {
						// Only update license to be the same as the destination one
						$input=$lic->fields;
						$input["ID"]=$moveID;
						unset($lic2->fields);
						$lic2->update($input);
					}
					if (getInstallionsForLicense($moveID)==0){
						$lic2->delete(array('ID'=>$moveID));
					}

	
				}
			}
			
		}
	}
}

function getInstalledLicence($sID){
	global $DB;
	$query = "SELECT count(*) FROM glpi_licenses INNER JOIN glpi_inst_software ON (glpi_licenses.sID = '$sID' AND glpi_licenses.ID = glpi_inst_software.license ) 
		INNER JOIN glpi_computers ON ( glpi_inst_software.cID=glpi_computers.ID AND glpi_computers.deleted='0' AND glpi_computers.is_template='0' )";

	$result = $DB->query($query);

	if ($DB->numrows($result)!=0){
		return $DB->result($result,0,0);
	} else return 0;

}

function getLicenceToBuy($sID){
	global $DB;
	$query = "SELECT ID FROM glpi_licenses WHERE (sID = '$sID' AND buy ='0'  AND serial <> 'free' AND serial <> 'global')";
	$result = $DB->query($query);
	return $DB->numrows($result);
}

function getLicenceNumber($sID){
	global $DB;
	$query = "SELECT ID,serial FROM glpi_licenses WHERE (sID = '$sID')";
	$result = $DB->query($query);
	return $DB->numrows($result);
}

function isGlobalSoftware($sID){
	global $DB;
	$query = "SELECT ID,serial FROM glpi_licenses WHERE (sID = '$sID' and serial='global')";
	$result = $DB->query($query);

	return ($DB->numrows($result)>0);
}

function isFreeSoftware($sID){
	global $DB;
	$query = "SELECT ID,serial FROM glpi_licenses WHERE (sID = '$sID'  and serial='free')";
	$result = $DB->query($query);
	return ($DB->numrows($result)>0);
}

function getInstallionsForLicense($ID){
	global $DB;
	$query = "SELECT count(*) FROM glpi_inst_software INNER JOIN glpi_computers ON ( glpi_inst_software.cID=glpi_computers.ID ) WHERE glpi_inst_software.license ='$ID' AND glpi_computers.deleted='0' AND glpi_computers.is_template='0' ";

	$result = $DB->query($query);

	if ($DB->numrows($result)!=0){
		return $DB->result($result,0,0);
	} else return 0;

}

?>
