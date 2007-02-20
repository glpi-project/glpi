<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.

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





function showLicensesAdd($ID) {

	global $cfg_glpi,$lang;

	if (!haveRight("software","w")) return false;

	echo "<div align='center'>&nbsp;<table class='tab_cadre_fixe' cellpadding='2'>";
	echo "<tr><td align='center' class='tab_bg_2'><strong>";
	echo "<a href=\"".$cfg_glpi["root_doc"]."/front/software.licenses.php?form=add&amp;sID=$ID\">";
	echo $lang["software"][12];
	echo "</a></strong></td></tr>";
	echo "</table></div><br>";
}

function showLicenses ($sID,$show_computers=0) {

	global $db,$cfg_glpi, $HTMLRel, $lang;

	if (!haveRight("software","r")) return false;
	$canedit=haveRight("software","w");

	$query = "SELECT count(ID) AS COUNT  FROM glpi_licenses WHERE (sID = '$sID')";
	$query_update = "SELECT count(glpi_licenses.ID) AS COUNT  FROM glpi_licenses, glpi_software WHERE (glpi_software.ID = glpi_licenses.sID AND glpi_software.update_software = '$sID' and glpi_software.is_update='Y')";

	if ($result = $db->query($query)) {
		if ($db->result($result,0,0)!=0) { 
			$nb_licences=$db->result($result, 0, "COUNT");
			$result_update = $db->query($query_update);
			$nb_updates=$db->result($result_update, 0, "COUNT");;
			$installed = getInstalledLicence($sID);
			$tobuy=getLicenceToBuy($sID);
			// As t'on utilisé trop de licences en prenant en compte les mises a jours (double install original + mise à jour)
			// Rien si free software
			$pb="";
			if (($nb_licences-$nb_updates-$installed)<0&&!isFreeSoftware($sID)&&!isGlobalSoftware($sID)) $pb="class='tab_bg_1_2'";

			echo "<form name='lic_form' method='get' action=\"".$cfg_glpi["root_doc"]."/front/software.licenses.php\">";

			echo "<br><div align='center'><table cellpadding='2' class='tab_cadre_fixe'>";
			echo "<tr><th colspan='5' $pb >";
			echo $nb_licences;
			echo "&nbsp;".$lang["software"][13]."&nbsp;-&nbsp;$nb_updates&nbsp;".$lang["software"][36]."&nbsp;-&nbsp;$installed&nbsp;".$lang["software"][19]."&nbsp;-&nbsp;$tobuy&nbsp;".$lang["software"][37]."</th>";
			echo "<th colspan='1'>";
			echo " ".$lang["software"][19]." :</th></tr>";
			$i=0;
			echo "<tr><th>".$lang["common"][19]."</th><th>".$lang["common"][33]."</th><th>".$lang["software"][32]."</th><th>".$lang["software"][28]."</th><th>".$lang["software"][35]."</th>";
			echo "<th>";

			if ($show_computers&&$canedit){
				echo $lang["buttons"][14]."&nbsp;";
				echo "<select name='update_licenses' id='update_licenses_choice'>";
				echo "<option value=''>-----</option>";
				echo "<option value='update_expire'>".$lang["software"][32]."</option>";
				echo "<option value='update_buy'>".$lang["software"][35]."</option>";
				echo "<option value='move'>".$lang["buttons"][20]."</option>";
				echo "</select>";

				echo "<script type='text/javascript' >\n";
				echo "   new Form.Element.Observer('update_licenses_choice', 1, \n";
				echo "      function(element, value) {\n";
				echo "      	new Ajax.Updater('update_licenses_view','".$cfg_glpi["root_doc"]."/ajax/updateLicenses.php',{asynchronous:true, evalScripts:true, \n";
				echo "           method:'post', parameters:'type=' + value+'&sID=$sID'\n";
				echo "})})\n";
				echo "</script>\n";

				echo "<span id='update_licenses_view'>\n";
				echo "&nbsp;";
				echo "</span>\n";	
			} else echo "&nbsp;";

			echo "</th></tr>";
		} else {

			echo "<br><div align='center'><table border='0' width='50%' cellpadding='2'>";
			echo "<tr><th>".$lang["software"][14]."</th></tr>";
			echo "</table></div>";
		}
	}

	$query = "SELECT count(ID) AS COUNT , serial as SERIAL, expire as EXPIRE, oem as OEM, oem_computer as OEM_COMPUTER, buy as BUY  FROM glpi_licenses WHERE (sID = '$sID') GROUP BY serial, expire, oem, oem_computer, buy ORDER BY serial,oem, oem_computer";
	//echo $query;
	if ($result = $db->query($query)) {			
		while ($data=$db->fetch_array($result)) {

			$serial=$data["SERIAL"];
			$num_tot=$data["COUNT"];
			$expire=$data["EXPIRE"];
			$oem=$data["OEM"];
			$oem_computer=$data["OEM_COMPUTER"];
			$buy=$data["BUY"];

			$SEARCH_LICENCE="(glpi_licenses.sID = $sID AND glpi_licenses.serial = '".$serial."'  AND glpi_licenses.oem = '$oem' AND glpi_licenses.oem_computer = '$oem_computer'  AND glpi_licenses.buy = '$buy' ";
			if ($expire=="")
				$SEARCH_LICENCE.=" AND glpi_licenses.expire IS NULL)";
			else $SEARCH_LICENCE.=" AND glpi_licenses.expire = '$expire')";

			$today=date("Y-m-d"); 
			$expirer=0;
			$expirecss="";
			if ($expire!=NULL&&$today>$expire) {$expirer=1; $expirecss="_2";}
			// Get installed licences


			$query_inst = "SELECT glpi_inst_software.ID AS ID, glpi_inst_software.license AS lID, glpi_computers.deleted as deleted, ";
			$query_inst .= " glpi_infocoms.ID as infocoms, glpi_licenses.comments AS COMMENT, ";
			$query_inst .= " glpi_computers.ID AS cID, glpi_computers.name AS cname FROM glpi_licenses";
			$query_inst .= " INNER JOIN glpi_inst_software ";
			$query_inst .= " ON ( glpi_inst_software.license = glpi_licenses.ID )";
			$query_inst .= " INNER JOIN glpi_computers ON (glpi_computers.deleted='N' AND glpi_computers.is_template='0' AND glpi_inst_software.cID= glpi_computers.ID) ";
			$query_inst .= " LEFT JOIN glpi_infocoms ON (glpi_infocoms.device_type='".LICENSE_TYPE."' AND glpi_infocoms.FK_device=glpi_licenses.ID) ";
			$query_inst .= " WHERE $SEARCH_LICENCE ORDER BY cname";

			$result_inst = $db->query($query_inst);
			$num_inst=$db->numrows($result_inst);

			echo "<tr class='tab_bg_1' valign='top'>";
			echo "<td align='center'><strong>".$serial."</strong></td>";
			echo "<td align='center'><strong>";
			echo $num_tot;
			echo "</strong></td>";

			echo "<td align='center' class='tab_bg_1$expirecss'><strong>";
			if ($expire==NULL)
				echo $lang["software"][26];
			else {
				if ($expirer) echo $lang["software"][27];
				else echo $lang["software"][25]."&nbsp;".convDate($expire);
			}

			echo "</strong></td>";
			// OEM
			if ($data["OEM"]=='Y') {
				$comp=new Computer();
				$comp->getFromDB($data["OEM_COMPUTER"]);
			}
			echo "<td align='center' lass='tab_bg_1".($data["OEM"]=='Y'&&!isset($comp->fields['ID'])?"_2":"")."'>".($data["OEM"]=='Y'?$lang["choice"][1]:$lang["choice"][0]);
			if ($data["OEM"]=='Y') {
				echo "<br><strong>";
				if (isset($comp->fields['ID']))
					echo "<a href='".$cfg_glpi["root_doc"]."/front/computer.form.php?ID=".$comp->fields['ID']."'>".$comp->fields['name']."</a>";
				else echo "N/A";
				echo "<strong>";
			} 
			echo "</td>";

			if ($serial!="free"){
				// BUY
				echo "<td align='center'>".($data["BUY"]=='Y'?$lang["choice"][1]:$lang["choice"][0]);
				echo "</td>";
			} else 
				echo "<td>&nbsp;</td>";

			echo "<td align='center'>";


			// Logiciels installés :
			echo "<table width='100%'>";

			// Restant	

			echo "<tr><td align='center'>";

			if (!$show_computers){
				echo $lang["software"][19].": $num_inst&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			}	


			$restant=$num_tot-$num_inst;
			$query_new="SELECT glpi_licenses.ID as ID FROM glpi_licenses WHERE $SEARCH_LICENCE";		
			if ($result_new = $db->query($query_new)) {			
				$IDdup=$db->result($result_new,0,0);

				if ($serial!="free"&&$serial!="global"&&$canedit) {
					echo $lang["software"][20].":";
					echo "<select name='stock_licenses_$IDdup'>";
					if (max(0,$restant-100)>0) echo "<option value='0'>0</option>";
					for ($i=max(0,$restant-100);$i<=$restant+100;$i++)
						echo "<option value='$i' ".($i==$restant?" selected ":"").">$i</option>";
					echo "</select>";
					echo "<input type='hidden' name='nb_licenses_$IDdup' value='$restant'>";
					echo "<input type='image' name='update_stock_licenses' value='$IDdup' src='".$HTMLRel."pics/actualiser.png' class='calendrier'>";
				}
				if (($serial=="free"||$serial=="global")){
					// Display infocoms
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>";
					showDisplayInfocomLink(LICENSE_TYPE,$IDdup,1);
					echo "</strong>";
				}
			}

			if ($restant!=0||$serial=="free"||$serial=="global") {
				// Get first non installed license ID
				$query_first="SELECT glpi_licenses.ID as ID, glpi_inst_software.license as iID FROM glpi_licenses LEFT JOIN glpi_inst_software ON glpi_inst_software.license = glpi_licenses.ID WHERE $SEARCH_LICENCE";
				if ($result_first = $db->query($query_first)) {			
					if ($serial=="free"||$serial=="global")
						$ID=$db->result($result_first,0,"ID");
					else{
						$fin=0;
						while (!$fin&&$temp=$db->fetch_array($result_first))
							if ($temp["iID"]==NULL){
								$fin=1;
								$ID=$temp["ID"];
							}
					}
					if (!empty($ID)){
						echo "</td><td align='center'>";
						if ($canedit){
							if (($serial=="free"||$serial=="global")){
								echo "<strong><a href=\"".$cfg_glpi["root_doc"]."/front/software.licenses.php?delete=delete&amp;ID=$ID\">";
								echo "<img src=\"".$HTMLRel."pics/delete.png\" alt='".$lang["buttons"][6]."' title='".$lang["buttons"][6]."'>";
								echo "</a></strong>";

								echo "&nbsp;&nbsp;<a alt=\"".$lang["common"][39]."\" title=\"".$lang["common"][39]."\" href=\"javascript:confirmAction('".addslashes($lang["common"][40])."\\n".addslashes($lang["common"][39])."','".$cfg_glpi["root_doc"]."/front/software.licenses.php?unglobalize=unglobalize&sID=$sID&ID=$ID')\">".$lang["common"][38]."</a>&nbsp;";	

								echo "<img alt=\"".$lang["common"][39]."\" title=\"".$lang["common"][39]."\" src='".$HTMLRel."pics/aide.png'\">";
							}
							echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong><a href=\"".$cfg_glpi["root_doc"]."/front/software.licenses.php?form=update&amp;lID=$ID&amp;sID=$sID\">";
							echo "<img src=\"".$HTMLRel."pics/edit.png\" alt='".$lang["buttons"][14]."' title='".$lang["buttons"][14]."'>";
							echo "</a></strong>";
						} else echo "&nbsp;";
					}

				}
			}

			// Add select all checkbox
			if ($show_computers&&$canedit){
				if ($num_inst>0&&$serial!="free"&&$serial!="global"){
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$lang["search"][7].":";
					$rand=mt_rand();

					echo "<input type='checkbox' onclick='toggle$rand();'>";
					echo "<script type='text/javascript' >\n";
					echo "function toggle$rand(){\n";
					while ($data_inst=$db->fetch_array($result_inst)){
						echo " var lic=document.getElementById('license_".$data_inst["lID"]."');";
						echo " if (lic.checked) \n";
						echo "      lic.checked = false;\n";
						echo " else lic.checked = true;\n";
					}
					echo "}</script>\n";
					$db->data_seek($result_inst,0);
				}
			}		

			echo "</td></tr>";


			// Logiciels installés
			if ($show_computers)
				while ($data_inst=$db->fetch_array($result_inst)){
					echo "<tr class='tab_bg_1".(($data["OEM"]=='Y'&&$data["OEM_COMPUTER"]!=$data_inst["cID"])||$data_inst["deleted"]=='Y'?"_2":"")."'><td align='center'>";

					if ($serial!="free"&&$serial!="global"&&$canedit) 
						echo "<input type='checkbox' name='license_".$data_inst["lID"]."' id='license_".$data_inst["lID"]."'>";

					echo "<strong><a href=\"".$cfg_glpi["root_doc"]."/front/computer.form.php?ID=".$data_inst["cID"]."\">";
					echo $data_inst["cname"];
					echo "</a></strong></td><td align='center'>";

					// Comment
					if (!empty($data_inst["COMMENT"])) {
						echo "<img onmouseout=\"cleanhide('comment_".$data_inst["ID"]."')\" onmouseover=\"cleandisplay('comment_".$data_inst["ID"]."')\" src=\"".$HTMLRel."pics/aide.png\" alt=''>";
						echo "<div class='over_link' id='comment_".$data_inst["ID"]."'>".nl2br($data_inst["COMMENT"])."</div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
					}
					// delete
					if ($canedit){
						echo "<a href=\"".$cfg_glpi["root_doc"]."/front/software.licenses.php?uninstall=uninstall&amp;ID=".$data_inst["ID"]."&amp;cID=".$data_inst["cID"]."\">";
						echo "<img src=\"".$HTMLRel."pics/remove.png\" alt='".$lang["buttons"][5]."' title='".$lang["buttons"][5]."'>";
						echo "</a>";
					}

					if ($serial!="free"&&$serial!="global"){
						echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
						if ($canedit){
							echo "<strong><a href=\"".$cfg_glpi["root_doc"]."/front/software.licenses.php?form=update&amp;lID=".$data_inst["lID"]."&amp;sID=$sID\">";
							echo "<img src=\"".$HTMLRel."pics/edit.png\" alt='".$lang["buttons"][14]."' title='".$lang["buttons"][14]."'>";
							echo "</a></strong>";
						}
						// Display infocoms
						echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>";
						showDisplayInfocomLink(LICENSE_TYPE,$data_inst["lID"],1);
						echo "</strong>";
					}

					echo "</td></tr>";
				}



			echo "</table></td>";

			echo "</tr>";

		}
	}	
	echo "</table></div>\n\n";
	echo "</form>";
}


function showLicenseForm($target,$action,$sID,$lID="") {

	global $cfg_glpi, $lang,$HTMLRel;

	if (!haveRight("software","w")) return false;

	$show_infocom=false;

	switch ($action){
		case "add" :
			$title= $lang["software"][15]." ($sID):";
		$button= $lang["buttons"][8];
		$ic=new Infocom();

		if ($ic->getFromDBforDevice(SOFTWARE_TYPE,$sID))
			$show_infocom=true;

		break;
		case "update" :
			$title = $lang["software"][34]." ($lID):";
		$button= $lang["buttons"][14];
		break;
	}

	// Get previous values or defaults values
	$values=array();
	// defaults values :
	$values['serial']='';
	$values['expire']="0000-00-00";
	$values['oem']='N';
	$values["oem_computer"]='';
	$values["comments"]='';
	$values['buy']='Y';


	if (isset($_POST)&&!empty($_POST)){ // Get from post form
		foreach ($values as $key => $val)
			if (isset($_POST[$key]))
				$values[$key]=$_POST[$key];

	}
	else if (!empty($lID)){ // Get from DB
		$lic=new License();
		$lic->getfromDB($lID);
		$values=$lic->fields;
	} 

	if (empty($values['expire'])) $values['expire']="0000-00-00";



	echo "<div align='center'><strong>";
	echo "<a href=\"".$cfg_glpi["root_doc"]."/front/software.form.php?ID=$sID\">";
	echo $lang["buttons"][13]."</strong>";
	echo "</a><br>";

	echo "<form name='form' method='post' action=\"$target\">";

	echo "<table class='tab_cadre'><tr><th colspan='3'>$title</th></tr>";


	echo "<tr class='tab_bg_1'><td>".$lang["software"][16]."</td>";
	echo "<td>";
	$readonly="";
	if ($values["serial"]=="free"||$values["serial"]=="global") $readonly="readonly";
	autocompletionTextField("serial","glpi_licenses","serial",$values["serial"],20,$readonly);
	echo "</td></tr>";

	if ($action!="update"){
		echo "<tr class='tab_bg_1'><td>";
		echo $lang["printers"][26].":</td><td><select name=number>";
		echo "<option value='1' selected>1</option>";
		for ($i=2;$i<=1000;$i++)
			echo "<option value='$i'>$i</option>";
		echo "</select></td></tr>";
	}

	if ($show_infocom){
		echo "<tr class='tab_bg_1'><td>".$lang["financial"][3].":</td><td>";
		showDisplayInfocomLink(SOFTWARE_TYPE,$sID);
		echo "</td></tr>"; 
	}

	echo "<tr class='tab_bg_1'><td>".$lang["search"][9].":</td><td>";
	showCalendarForm("form","expire",$values['expire']);
	echo "</td></tr>"; 

	// OEM
	echo "<tr class='tab_bg_1'><td>".$lang["software"][28]."</td><td>";
	echo "<select name='oem'><option value='Y' ".($values['oem']=='Y'?"selected":"").">".$lang["choice"][1]."</option><option value='N' ".($values['oem']=='N'?"selected":"").">".$lang["choice"][0]."</option></select>";
	dropdownValue("glpi_computers","oem_computer",$values["oem_computer"]);

	echo "</td></tr>";
	// BUY
	echo "<tr class='tab_bg_1'><td>".$lang["software"][35]."</td><td>";
	echo "<select name='buy'><option value='Y' ".($values['buy']=='Y'?"selected":"").">".$lang["choice"][1]."</option><option value='N' ".($values['buy']=='N'?"selected":"").">".$lang["choice"][0]."</option></select>";
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["common"][25]."</td><td>";
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
	global $db;

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

				if ($result_first = $db->query($query_first)) {			
					if ($lic->fields["serial"]=="free"||$lic->fields["serial"]=="global")
						$ID=$db->result($result_first,0,"ID");
					else{
						$fin=0;
						while (!$fin&&$temp=$db->fetch_array($result_first))
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

	global $db;


	if (!empty($lID)&&$lID>0){
		$query = "INSERT INTO glpi_inst_software VALUES (NULL,$cID,$lID)";
		if ($result = $db->query($query)) {
			$newID=$db->insert_id();
			if ($dohistory){
				$lic=new License();
				$lic->getFromDB($lID);
				$soft=new Software();
				if ($soft->getFromDB($lic->fields["sID"])){
					$changes[0]='0';
					$changes[1]="";
					$changes[2]=$soft->fields["name"]." (v. ".$soft->fields["version"].")";
					// history log
					historyLog ($cID,COMPUTER_TYPE,$changes,0,HISTORY_INSTALL_SOFTWARE);
				}
			}
			return $newID;
		} else {
			return false;
		}
	} else if ($lID<0&&!empty($sID)){ // Auto Add a license
		$lic=new License();
		$lic->fields['buy']='N';
		$lic->fields['sID']=$sID;
		$lic->fields['serial']='Automatic Add';
		$lID=$lic->addToDB();

		$query = "INSERT INTO glpi_inst_software VALUES (NULL,$cID,$lID)";
		if ($result = $db->query($query)) {
			$newID=$db->insert_id();
			if ($dohistory){
				$soft=new Software();
				if ($soft->getFromDB($sID)){
					$changes[0]='0';
					$changes[1]="";
					$changes[2]=$soft->fields["name"]." (v. ".$soft->fields["version"].")";
					// history log
					historyLog ($cID,COMPUTER_TYPE,$changes,0,HISTORY_INSTALL_SOFTWARE);
				}
			}

			return $newID;
		} else {
			return false;
		}

	}
}

function uninstallSoftware($ID,$dohistory=1) {

	global $db;

	// license data for history
	if ($dohistory){
		$query2 = "SELECT * FROM glpi_inst_software WHERE (ID = '$ID')";
		$result2=$db->query($query2);
		$data=$db->fetch_array($result2);
		$lic=new License();
		$lic->getFromDB($data["license"]);
	}

	$query = "DELETE FROM glpi_inst_software WHERE (ID = '$ID')";

	if ($result = $db->query($query)) {
		if ($dohistory){
			$soft=new Software();
			if ($soft->getFromDB($lic->fields["sID"])){
				$changes[0]='0';
				$changes[1]=$soft->fields["name"]." (v. ".$soft->fields["version"].")";
				$changes[2]="";
				// history log
				historyLog ($data["cID"],COMPUTER_TYPE,$changes,0,HISTORY_UNINSTALL_SOFTWARE);
			}
		}

		return true;
	} else {
		return false;
	}
}

function showSoftwareInstalled($instID,$withtemplate='') {

	global $db,$cfg_glpi, $lang;
	if (!haveRight("software","r")) return false;

	$query = "SELECT glpi_inst_software.license as license, glpi_inst_software.ID as ID,glpi_licenses.expire,glpi_software.deleted, glpi_licenses.sID, glpi_software.version, glpi_licenses.oem, glpi_licenses.oem_computer, glpi_licenses.serial, glpi_licenses.buy FROM glpi_inst_software, glpi_software,glpi_licenses ";
	$query.= "WHERE glpi_inst_software.license = glpi_licenses.ID AND glpi_licenses.sID = glpi_software.ID AND (glpi_inst_software.cID = '$instID') order by glpi_software.name, glpi_software.version";

	$result = $db->query($query);
	$i = 0;

	echo "<br><br><div align='center'><table class='tab_cadre_fixe'>";
	echo "<tr><th colspan='5'>".$lang["software"][17].":</th></tr>";
	echo "<tr><th>".$lang["common"][16]."</th><th>".$lang["software"][32]."</th><th>".$lang["software"][28]."</th><th>".$lang["software"][35]."</th><th>&nbsp;</th></tr>";
	if ($db->numrows($result))
		while ($data=$db->fetch_array($result)) {
			$lID = $data["license"];
			$ID = $data["ID"];

			$today=date("Y-m-d"); 
			$expirer=0;
			$expirecss="";
			if ($data['expire']!=NULL&&$today>$data['expire']) {$expirer=1; $expirecss="_2";}

			$sw = new Software;
			$sw->getFromDB($data['sID']);

			if ($data['deleted']=="Y") {$expirer=1; $expirecss="_2";}

			echo "<tr class='tab_bg_1$expirecss'>";

			echo "<td align='center'><strong><a href=\"".$cfg_glpi["root_doc"]."/front/software.form.php?ID=".$data['sID']."\">";
			echo $sw->fields["name"]." (v. ".$data["version"].")</a>";
			echo "</strong>";
			echo " - ".$data['serial']."</td>";
			echo "<td align='center'><strong>";
			if ($data['expire']==NULL)
				echo $lang["software"][26];
			else {
				if ($expirer) echo $lang["software"][27];
				else echo $lang["software"][25]."&nbsp;".$data['expire'];
			}

			echo "</strong></td>";
			if ($data['serial']!="free"&&$data['serial']!="global"){
				// OEM
				if ($data["oem"]=='Y') {
					$comp=new Computer();
					$comp->getFromDB($data["oem_computer"]);
				}
				echo "<td align='center' class='tab_bg_1".($expirer||($data["oem"]=='Y'&&$comp->fields['ID']!=$instID)?"_2":"")."'>".($data["oem"]=='Y'?$lang["choice"][1]:$lang["choice"][0]);
				if ($data["oem"]=='Y') {
					echo "<br><strong>";
					if (isset($comp->fields['ID']))
						echo "<a href='".$cfg_glpi["root_doc"]."/front/computer.form.php?ID=".$comp->fields['ID']."'>".$comp->fields['name']."</a>";
					else echo "N/A";
					echo "<strong>";
				} 
				echo "</td>";

				// BUY
				echo "<td align='center'>".($data["buy"]=='Y'?$lang["choice"][1]:$lang["choice"][0]);
				echo "</td>";								
			}
			else echo "<td>&nbsp;</td><td>&nbsp;</td>";
			echo "<td align='center' class='tab_bg_2'>";
			if(!empty($withtemplate) && $withtemplate == 2) {
				//do nothing
				echo "&nbsp;";
			} else {
				echo "<a href=\"".$cfg_glpi["root_doc"]."/front/software.licenses.php?uninstall=uninstall&amp;ID=$ID&amp;cID=$instID\">";
				echo "<strong>".$lang["buttons"][5]."</strong></a>";
			}
			echo "</td></tr>";

			$i++;		
		}
	$q="SELECT count(*) FROM glpi_software WHERE deleted='N' AND is_template='0'";
	$result = $db->query($q);
	$nb = $db->result($result,0,0);

	if((!empty($withtemplate) && $withtemplate == 2) || $nb==0||!haveRight("software","w")) {
		echo "</table></div>";
	} else {
		echo "<tr class='tab_bg_1'><td align='center' colspan='5'>";
		echo "<form method='post' action=\"".$cfg_glpi["root_doc"]."/front/software.licenses.php\">";

		echo "<div class='software-instal'>";
		echo "<input type='hidden' name='cID' value='$instID'>";
		dropdownSoftwareToInstall("licenseID",$withtemplate);
		echo "<input type='submit' name='install' value=\"".$lang["buttons"][4]."\" class='submit'>";
		echo "</div>";
		echo "</form>";
		echo "</td></tr>";
		echo "</table></div>";
	}



}

function unglobalizeLicense($ID){
	global $db;
	$license=new License();
	$license->getFromDB($ID);
	// Check if it is a real global license
	if ($license->fields["serial"]=="free"||$license->fields["serial"]=="global"){


		$query = "SELECT * FROM glpi_inst_software WHERE license = '$ID'";
		$result=$db->query($query);

		if (($nb=$db->numrows($result))>0){
			// Update item to unit management :
			$input=array("ID"=>$ID,"serial"=>"_".$license->fields["serial"]."_");

			// skip first
			$data=$db->fetch_array($result);
			if ($license->fields["oem"]=="Y"){
				$input["oem_computer"]=$data["cID"];
			}
			$license->update($input);


			$input=$license->fields;
			$input["_duplicate_license"]=$ID;
			unset($input["ID"]);

			// Get ID of the inst_software
			while ($data=$db->fetch_array($result)){
				unset($input["oem_computer"]);
				if ($license->fields["oem"]=="Y")
					$input["oem_computer"]=$data["cID"];

				// Add new Item
				unset($license->fields["ID"]);
				unset($license->fields["expire"]);
				unset($license->fields["oem_computer"]);
				if ($newID=$license->add($input)){
					// Update inst_software
					$query2="UPDATE glpi_inst_software SET license='$newID' WHERE ID='".$data["ID"]."'";
					$db->query($query2);
				}
			}
		}
	}

}

function countInstallations($sID,$nohtml=0) {

	global $db,$cfg_glpi, $lang;


	// Get total
	$total = getLicenceNumber($sID);
	$out="";
	if ($total!=0) {

		if (isFreeSoftware($sID)) {
			// Get installed
			$installed = getInstalledLicence($sID);
			if (!$nohtml)
				$out.= "<i>".$lang["software"][39]."</i>&nbsp;&nbsp;".$lang["software"][19].": <strong>$installed</strong>";
			else $out.= $lang["software"][39]."  ".$lang["software"][19].": $installed";
		} else if (isGlobalSoftware($sID)){
			$installed = getInstalledLicence($sID);
			if (!$nohtml)
				$out.= "<i>".$lang["software"][38]."</i>&nbsp;&nbsp;".$lang["software"][19].": <strong>$installed</strong>";
			else $out.= $lang["software"][38]."  ".$lang["software"][19].": $installed";

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
				$out.= "<td width='35%'>".$lang["software"][19].": <strong>$installed</strong></td>";
			} else $out.= "  ".$lang["software"][19].": $installed";

			$color="red";

			if ($remaining == 0) {
				$color="green";
			} else {
				$color="blue";
			}			

			if (!$nohtml){
				$remaining = "<span class='$color'>$remaining";
				$remaining .= "</span>";
				$out.= "<td width='20%'>".$lang["software"][20].": <strong>$remaining</strong></td>";
				$out.= "<td width='20%'>".$lang["common"][33].": <strong>".$total."</strong></td>";
			} else {
				$out.= "  ".$lang["software"][20].": $remaining";
				$out.= "  ".$lang["common"][33].": ".$total;
			}

			$tobuy=getLicenceToBuy($sID);
			if ($tobuy>0){
				if (!$nohtml)
					$out.= "<td width='25%'>".$lang["software"][37].": <strong><span class='red'>".$tobuy."</span></strong></td>";
				else $out.= "  ".$lang["software"][37].": ".$tobuy;
			} else {
				if (!$nohtml)
					$out.= "<td width='20%'>&nbsp;</td>";
			}
			if (!$nohtml)
				$out.= "</tr></table>";
		} 
	} else {
		if (!$nohtml)
			$out.= "<div align='center'><i>".$lang["software"][40]."</i></div>";
		else $out.= $lang["software"][40];
	}
	return $out;
}	

function getInstalledLicence($sID){
	global $db;
	$query = "SELECT count(*) FROM glpi_licenses INNER JOIN glpi_inst_software ON (glpi_licenses.sID = '$sID' AND glpi_licenses.ID = glpi_inst_software.license ) 
		INNER JOIN glpi_computers ON ( glpi_inst_software.cID=glpi_computers.ID AND glpi_computers.deleted='N' AND glpi_computers.is_template='0' )";

	$result = $db->query($query);

	if ($db->numrows($result)!=0){
		return $db->result($result,0,0);
	} else return 0;

}

function getLicenceToBuy($sID){
	global $db;
	$query = "SELECT ID FROM glpi_licenses WHERE (sID = '$sID' AND buy ='N')";
	$result = $db->query($query);
	return $db->numrows($result);
}

function getLicenceNumber($sID){
	global $db;
	$query = "SELECT ID,serial FROM glpi_licenses WHERE (sID = '$sID')";
	$result = $db->query($query);
	return $db->numrows($result);
}

function isGlobalSoftware($sID){
	global $db;
	$query = "SELECT ID,serial FROM glpi_licenses WHERE (sID = '$sID' and serial='global')";
	$result = $db->query($query);

	return ($db->numrows($result)>0);
}

function isFreeSoftware($sID){
	global $db;
	$query = "SELECT ID,serial FROM glpi_licenses WHERE (sID = '$sID'  and serial='free')";
	$result = $db->query($query);
	return ($db->numrows($result)>0);
}

function getInstallionsForLicense($ID){
	global $db;
	$query = "SELECT count(*) FROM glpi_inst_software INNER JOIN glpi_computers ON ( glpi_inst_software.cID=glpi_computers.ID ) WHERE glpi_inst_software.license ='$ID' AND glpi_computers.deleted='N' AND glpi_computers.is_template='0' ";

	$result = $db->query($query);

	if ($db->numrows($result)!=0){
		return $db->result($result,0,0);
	} else return 0;

}


?>
