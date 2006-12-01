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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

/**
 * Print out a link to add directly a new cartridge from a cartridge type.
 *
 * Print out the link witch make a new cartridge from cartridge type idetified by $ID
 *
 *@param $ID Cartridge type identifier.
 *
 *
 *@return Nothing (displays)
 **/
function showCartridgesAdd($ID) {

	global $cfg_glpi,$lang,$HTMLRel;

	if (!haveRight("cartridge","w")) return false;

	echo "<form method='post'  action=\"".$HTMLRel."front/cartridge.edit.php\">";
	echo "<div align='center'>&nbsp;<table class='tab_cadre_fixe' cellpadding='2'>";
	echo "<tr><td align='center' class='tab_bg_2'><b>";
	echo "<a href=\"".$cfg_glpi["root_doc"]."/front/cartridge.edit.php?add=add&amp;tID=$ID\">";
	echo $lang["cartridges"][17];
	echo "</a></b></td>";
	echo "<td align='center' class='tab_bg_2'>";
	echo "<input type='submit' name='add_several' value=\"".$lang["buttons"][8]."\" class='submit'>";
	echo "<input type='hidden' name='tID' value=\"$ID\">\n";

	echo "&nbsp;&nbsp;<select name='to_add'>";
	for ($i=1;$i<100;$i++)
		echo "<option value='$i'>$i</option>";
	echo "</select>&nbsp;&nbsp;";
	echo $lang["cartridges"][16];
	echo "</td></tr>";
	echo "</table></div>";
	echo "</form><br>";
}
/**
 * Print out the cartridges of a defined type
 *
 * Print out all the cartridges that are issued from the cartridge type identified by $ID
 *
 *@param $tID integer : Cartridge type identifier.
 *@param $show_old boolean : show old cartridges or not. 
 *
 *@return Nothing (displays)
 **/
function showCartridges ($tID,$show_old=0) {

	global $db,$cfg_glpi,$lang,$HTMLRel;

	if (!haveRight("cartridge","r")) return false;
	$canedit=haveRight("cartridge","w");

	$query = "SELECT count(ID) AS COUNT  FROM glpi_cartridges WHERE (FK_glpi_cartridges_type = '$tID')";

	if ($result = $db->query($query)) {
		if ($db->result($result,0,0)!=0) { 
			$total=$db->result($result, 0, "COUNT");
			$unused=getUnusedCartridgesNumber($tID);
			$used=getUsedCartridgesNumber($tID);
			$old=getOldCartridgesNumber($tID);

			echo "<br><div align='center'><table cellpadding='2' class='tab_cadre_fixe'>";
			if ($show_old==0){
				echo "<tr><th colspan='7'>";
				echo $total;
				echo "&nbsp;".$lang["cartridges"][16]."&nbsp;-&nbsp;$unused&nbsp;".$lang["cartridges"][13]."&nbsp;-&nbsp;$used&nbsp;".$lang["cartridges"][14]."&nbsp;-&nbsp;$old&nbsp;".$lang["cartridges"][15]."</th>";
				echo "<th colspan='2'>";
				echo "&nbsp;</th></tr>";
			} else { // Old
				echo "<tr><th colspan='8'>";
				echo $lang["cartridges"][35];
				echo "</th>";
				echo "<th colspan='2'>";
				echo "&nbsp;</th></tr>";
			}
			$i=0;
			echo "<tr><th>".$lang["common"][2]."</th><th>".$lang["cartridges"][23]."</th><th>".$lang["cartridges"][24]."</th><th>".$lang["cartridges"][25]."</th><th>".$lang["cartridges"][27]."</th><th>".$lang["search"][9]."</th>";

			if ($show_old==1){
				echo "<th>".$lang["cartridges"][39]."</th>";
			}

			echo "<th>".$lang["financial"][3]."</th>";
			echo "<th colspan='2'>&nbsp;</th>";

			echo "</tr>";
		} else {
			echo "<br><div align='center'><table border='0' width='50%' cellpadding='2'>";
			echo "<tr><th>".$lang["cartridges"][7]."</th></tr>";
			echo "</table></div>";
		}
	}

	if ($show_old==0){ // NEW
		$where= " AND date_out IS NULL";
	} else { //OLD
		$where= " AND date_out IS NOT NULL";
	}

	$stock_time=0;
	$use_time=0;	
	$pages_printed=0;
	$nb_pages_printed=0;

	$query = "SELECT * FROM glpi_cartridges WHERE (FK_glpi_cartridges_type = '$tID') $where ORDER BY date_out ASC, date_use DESC, date_in";

	$pages=array();
	if ($result = $db->query($query)) {			
		$number=$db->numrows($result);
		while ($data=$db->fetch_array($result)) {
			$date_in=convDate($data["date_in"]);
			$date_use=convDate($data["date_use"]);
			$date_out=convDate($data["date_out"]);
			$printer=$data["FK_glpi_printers"];
			$page=$data["pages"];

			echo "<tr  class='tab_bg_1'><td align='center'>";
			echo $data["ID"]; 
			echo "</td><td align='center'>";
			echo getCartridgeStatus($data["ID"]);
			echo "</td><td align='center'>";
			echo $date_in;
			echo "</td><td align='center'>";
			echo $date_use;
			echo "</td><td align='center'>";
			if (!is_null($date_use)){
				$p=new Printer;
				if ($p->getFromDB($data["FK_glpi_printers"])){
					echo "<a href='".$cfg_glpi["root_doc"]."/front/printer.form.php?ID=".$p->fields["ID"]."'><b>".$p->fields["name"]." (".$p->fields["ID"].")</b></a>";
				}	else echo "N/A";
				$tmp_dbeg=split("-",$data["date_in"]);
				$tmp_dend=split("-",$data["date_use"]);
				$stock_time_tmp= mktime(0,0,0,$tmp_dend[1],$tmp_dend[2],$tmp_dend[0]) 
					- mktime(0,0,0,$tmp_dbeg[1],$tmp_dbeg[2],$tmp_dbeg[0]);		
				$stock_time+=$stock_time_tmp;
			}
			echo "</td><td align='center'>";
			echo $date_out;		
			if ($show_old!=0){
				$tmp_dbeg=split("-",$data["date_use"]);
				$tmp_dend=split("-",$data["date_out"]);

				$use_time_tmp= mktime(0,0,0,$tmp_dend[1],$tmp_dend[2],$tmp_dend[0]) 
					- mktime(0,0,0,$tmp_dbeg[1],$tmp_dbeg[2],$tmp_dbeg[0]);		
				$use_time+=$use_time_tmp;
			}
			echo "</td>";
			if ($show_old!=0){
				// Get initial counter page
				if (!isset($pages[$printer])){
					$prn=new Printer;
					$prn->getfromDB($printer);
					$pages[$printer]=$prn->fields['initial_pages'];
				}
				echo "<td align='center'>";
				if ($pages[$printer]<$data['pages']){
					$pages_printed+=$data['pages']-$pages[$printer];
					$nb_pages_printed++;
					echo ($data['pages']-$pages[$printer])." ".$lang["printers"][31];
					$pages[$printer]=$data['pages'];
				}
				echo "</td>";
			}
			echo "<td align='center'>";
			showDisplayInfocomLink(CARTRIDGE_ITEM_TYPE,$data["ID"],1);
			echo "</td>";
			echo "<td align='center'>";
			if (!is_null($date_use)&&$canedit)
				echo "&nbsp;&nbsp;&nbsp;<a href='".$cfg_glpi["root_doc"]."/front/cartridge.edit.php?restore=restore&amp;ID=".$data["ID"]."&amp;tID=$tID'>".$lang["cartridges"][43]."</a>";		
			else echo "&nbsp;";

			echo "</td>";
			echo "<td align='center'>";
			if ($canedit){
				echo "&nbsp;&nbsp;&nbsp;<a href='".$cfg_glpi["root_doc"]."/front/cartridge.edit.php?delete=delete&amp;ID=".$data["ID"]."&amp;tID=$tID'>".$lang["buttons"][6]."</a>";
			} else echo "&nbsp;";
			echo "</td></tr>";
		}	
		if ($show_old!=0&&$number>0){
			if ($nb_pages_printed==0) $nb_pages_printed=1;
			echo "<tr class='tab_bg_2'><td colspan='3'>&nbsp;</td>";
			echo "<td align='center'>".$lang["cartridges"][40].":<br>".round($stock_time/$number/60/60/24/30.5,1)." ".$lang["financial"][57]."</td>";
			echo "<td>&nbsp;</td>";
			echo "<td align='center'>".$lang["cartridges"][41].":<br>".round($use_time/$number/60/60/24/30.5,1)." ".$lang["financial"][57]."</td>";
			echo "<td align='center'>".$lang["cartridges"][42].":<br>".round($pages_printed/$nb_pages_printed)."</td>";
			echo "<td colspan='3'>&nbsp;</td></tr>";
		}
	}	
	echo "</table></div>\n\n";
}



/**
 * Show the printer types that are compatible with a cartridge type
 *
 * Show the printer types that are compatible with the cartridge type identified by $instID
 *
 *@param $instID : cartridge type identifier
 *
 *@return nothing (display)
 *
 **/
function showCompatiblePrinters($instID) {
	global $db,$cfg_glpi, $lang;

	if (!haveRight("cartridge","r")) return false;

	$query = "SELECT glpi_dropdown_model_printers.name as type, glpi_cartridges_assoc.ID as ID FROM glpi_cartridges_assoc, glpi_dropdown_model_printers WHERE glpi_cartridges_assoc.FK_glpi_dropdown_model_printers=glpi_dropdown_model_printers.ID AND glpi_cartridges_assoc.FK_glpi_cartridges_type = '$instID' order by glpi_dropdown_model_printers.name";

	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;

	echo "<form method='post' action=\"".$cfg_glpi["root_doc"]."/front/cartridge.form.php\">";
	echo "<br><br><div align='center'><table class='tab_cadre_fixe'>";
	echo "<tr><th colspan='3'>".$lang["cartridges"][32].":</th></tr>";
	echo "<tr><th>".$lang["common"][2]."</th><th>".$lang["common"][22]."</th><th>&nbsp;</th></tr>";

	while ($i < $number) {
		$ID=$db->result($result, $i, "ID");
		$type=$db->result($result, $i, "type");
		echo "<tr class='tab_bg_1'><td align='center'>$ID</td>";
		echo "<td align='center'>$type</td>";
		echo "<td align='center' class='tab_bg_2'><a href='".$_SERVER['PHP_SELF']."?deletetype=deletetype&amp;ID=$ID'><b>".$lang["buttons"][6]."</b></a></td></tr>";
		$i++;
	}
	if (haveRight("cartridge","w")){
		echo "<tr class='tab_bg_1'><td>&nbsp;</td><td align='center'>";
		echo "<div class='software-instal'><input type='hidden' name='tID' value='$instID'>";
		dropdown("glpi_dropdown_model_printers","model");
		echo "</div></td><td align='center' class='tab_bg_2'>";
		echo "<input type='submit' name='addtype' value=\"".$lang["buttons"][8]."\" class='submit'>";
		echo "</td></tr>";
	}

	echo "</table></div></form>"    ;
}

/**
 * Show installed cartridges
 *
 * Show installed cartridge for the printer type $instID
 *
 *@param $instID integer: printer type identifier.
 *@param $old boolean : old cartridges or not ?
 *
 *@return nothing (display)
 *
 **/
function showCartridgeInstalled($instID,$old=0) {

	global $db,$cfg_glpi, $lang,$HTMLRel;

	if (!haveRight("cartridge","r")) return false;
	$canedit=haveRight("cartridge","w");

	$query = "SELECT glpi_cartridges_type.ID as tID, glpi_cartridges_type.deleted as deleted, glpi_cartridges_type.ref as ref, glpi_cartridges_type.name as type, glpi_cartridges.ID as ID, glpi_cartridges.pages as pages, glpi_cartridges.date_use as date_use, glpi_cartridges.date_out as date_out, glpi_cartridges.date_in as date_in";
	if ($old==0)
		$query.= " FROM glpi_cartridges, glpi_cartridges_type WHERE glpi_cartridges.date_out IS NULL AND glpi_cartridges.FK_glpi_printers= '$instID' AND glpi_cartridges.FK_glpi_cartridges_type  = glpi_cartridges_type.ID ORDER BY glpi_cartridges.date_out ASC, glpi_cartridges.date_use DESC, glpi_cartridges.date_in";
	else 
		$query.= " FROM glpi_cartridges, glpi_cartridges_type WHERE glpi_cartridges.date_out IS NOT NULL AND glpi_cartridges.FK_glpi_printers= '$instID' AND glpi_cartridges.FK_glpi_cartridges_type  = glpi_cartridges_type.ID ORDER BY glpi_cartridges.date_out ASC, glpi_cartridges.date_use DESC, glpi_cartridges.date_in";


	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;
	$p=new Printer;
	$p->getFromDB($instID);
	$pages=$p->fields['initial_pages'];

	echo "<br><br><div align='center'><table class='tab_cadre_fixe'>";
	if ($old==0)
		echo "<tr><th colspan='7'>".$lang["cartridges"][33].":</th></tr>";
	else echo "<tr><th colspan='8'>".$lang["cartridges"][35].":</th></tr>";

	echo "<tr><th>".$lang["common"][2]."</th><th>".$lang["cartridges"][12]."</th><th>".$lang["cartridges"][23]."</th><th>".$lang["cartridges"][24]."</th><th>".$lang["cartridges"][25]."</th><th>".$lang["search"][9]."</th>";
	if ($old!=0)
		echo "<th>".$lang["cartridges"][39]."</th>";

	echo "<th>&nbsp;</th></tr>";

	$stock_time=0;
	$use_time=0;	
	$pages_printed=0;
	$nb_pages_printed=0;
	$ci=new CommonItem();
	while ($data=$db->fetch_array($result)) {
		$date_in=convDate($data["date_in"]);
		$date_use=convDate($data["date_use"]);
		$date_out=convDate($data["date_out"]);
		echo "<tr  class='tab_bg_1".($data["deleted"]=='Y'?"_2":"")."'><td align='center'>";
		echo $data["ID"]; 
		echo "</td><td align='center'><b>";
		$ci->getFromDB(CARTRIDGE_TYPE,$data["tID"]);
		echo $ci->getLink();
		echo "</b></td><td align='center'>";
		echo getCartridgeStatus($data["ID"]);
		echo "</td><td align='center'>";
		echo $date_in;
		echo "</td><td align='center'>";
		echo $date_use;

		$tmp_dbeg=split("-",$date_in);
		$tmp_dend=split("-",$date_use);

		$stock_time_tmp= mktime(0,0,0,$tmp_dend[1],$tmp_dend[2],$tmp_dend[0]) 
			- mktime(0,0,0,$tmp_dbeg[1],$tmp_dbeg[2],$tmp_dbeg[0]);
		$stock_time+=$stock_time_tmp;

		echo "</td><td align='center'>";
		echo $date_out;		

		if ($old!=0){
			$tmp_dbeg=split("-",$date_use);
			$tmp_dend=split("-",$date_out);

			$use_time_tmp= mktime(0,0,0,$tmp_dend[1],$tmp_dend[2],$tmp_dend[0]) 
				- mktime(0,0,0,$tmp_dbeg[1],$tmp_dbeg[2],$tmp_dbeg[0]);		
			$use_time+=$use_time_tmp;
		}

		echo "</td><td align='center'>";
		if ($old!=0){
			if ($canedit){
				echo "<form method='post' action=\"".$cfg_glpi["root_doc"]."/front/cartridge.edit.php\">";
				echo "<input type='hidden' name='cID' value='".$data['ID']."'>";
			}
			echo "<input type='text' name='pages' value=\"".$data['pages']."\" size='10'>";
			if ($canedit){
				echo "<input type='image' name='update_pages' value='update_pages' src='".$HTMLRel."pics/actualiser.png' class='calendrier'>";
				echo "</form>";
			}
			if ($pages<$data['pages']){
				$pages_printed+=$data['pages']-$pages;
				$nb_pages_printed++;
				echo ($data['pages']-$pages)." ".$lang["printers"][31];
				$pages=$data['pages'];
			}
			echo "</td><td align='center'>";
		}
		if ($canedit)
			if (is_null($date_out))
				echo "&nbsp;&nbsp;&nbsp;<a href='".$cfg_glpi["root_doc"]."/front/cartridge.edit.php?uninstall=uninstall&amp;ID=".$data["ID"]."'>".$lang["cartridges"][29]."</a>";
			else echo "&nbsp;&nbsp;&nbsp;<a href='".$cfg_glpi["root_doc"]."/front/cartridge.edit.php?delete=delete&amp;ID=".$data["ID"]."'>".$lang["buttons"][6]."</a>";
			echo "</td></tr>";

	}	
	if ($old==0&&$canedit){
		echo "<tr class='tab_bg_1'><td>&nbsp;</td><td align='center'>";
		echo "<form method='post' action=\"".$cfg_glpi["root_doc"]."/front/cartridge.edit.php\">";

		echo "<div class='software-instal'><input type='hidden' name='pID' value='$instID'>";
		dropdownCompatibleCartridges($instID);
		echo "<input type='submit' name='install' value=\"".$lang["buttons"][4]."\" class='submit'>";

		echo "</div></form></td><td align='center' class='tab_bg_2'>&nbsp;";
		echo "</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>";
		echo "</tr>";
	} else { // Print average
		if ($number>0){
			if ($nb_pages_printed==0) $nb_pages_printed=1;
			echo "<tr class='tab_bg_2'><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>";

			echo "<td align='center'>".$lang["cartridges"][40].":<br>".round($stock_time/$number/60/60/24/30.5,1)." ".$lang["financial"][57]."</td>";
			echo "<td align='center'>".$lang["cartridges"][41].":<br>".round($use_time/$number/60/60/24/30.5,1)." ".$lang["financial"][57]."</td>";
			echo "<td align='center'>".$lang["cartridges"][42].":<br>".round($pages_printed/$nb_pages_printed)."</td>";
			echo "<td>&nbsp;</td></tr>";
		}
	}
	echo "</table></div>";
}


/**
 * Print a select with compatible cartridge
 *
 * Print a select that contains compatibles cartridge for a printer model $pID
 *
 *@param $pID integer: printer type identifier.
 *
 *@return nothing (display)
 *
 **/
function dropdownCompatibleCartridges($pID) {

	global $db,$lang;

	$p=new Printer;
	$p->getFromDB($pID);

	$query = "SELECT glpi_cartridges_type.ref as ref, glpi_cartridges_type.name as name, glpi_cartridges_type.ID as tID FROM glpi_cartridges_type, glpi_cartridges_assoc WHERE glpi_cartridges_type.ID = glpi_cartridges_assoc.FK_glpi_cartridges_type AND glpi_cartridges_assoc.FK_glpi_dropdown_model_printers = '".$p->fields["model"]."' order by glpi_cartridges_type.name, glpi_cartridges_type.ref";
	$result = $db->query($query);
	$number = $db->numrows($result);

	$i = 0;
	echo "<select name=tID size=1>";
	while ($i < $number) {
		$ref = $db->result($result, $i, "ref");
		$name = $db->result($result, $i, "name");
		$tID = $db->result($result, $i, "tID");
		$nb = getUnusedCartridgesNumber($tID);
		echo  "<option value=$tID>$name - $ref ($nb ".$lang["cartridges"][13].")</option>";
		$i++;
	}
	echo "</select>";
}

/**
 * Print the cartridge count HTML array for a defined cartridge type
 *
 * Print the cartridge count HTML array for the cartridge type $tID
 *
 *@param $tID integer: cartridge type identifier.
 *@param $alarm integer: threshold alarm value.
 *@param $nohtml integer: Return value without HTML tags.
 *
 *@return string to display
 *
 **/
function countCartridges($tID,$alarm,$nohtml=0) {
	global $db,$cfg_glpi, $lang;

	// Get total
	$total = getCartridgesNumber($tID);
	$out="";
	if ($total!=0) {
		$unused=getUnusedCartridgesNumber($tID);
		$used=getUsedCartridgesNumber($tID);
		$old=getOldCartridgesNumber($tID);

		$highlight="";
		if ($unused<=$alarm)
			$highlight="class='tab_bg_1_2'";

		if (!$nohtml)
			$out.= "<div $highlight>".$lang["common"][33].":&nbsp;$total&nbsp;&nbsp;&nbsp;<strong>".$lang["cartridges"][13].": $unused</strong>&nbsp;&nbsp;&nbsp;".$lang["cartridges"][14].": $used&nbsp;&nbsp;&nbsp;".$lang["cartridges"][15].": $old</div>";
		else 	$out.= $lang["common"][33].": $total   ".$lang["cartridges"][13].": $unused   ".$lang["cartridges"][14].": $used   ".$lang["cartridges"][15].": $old";		

	} else {
		if (!$nohtml)
			$out.= "<div class='tab_bg_1_2'><i>".$lang["cartridges"][9]."</i></div>";
		else $out.= $lang["cartridges"][9];
	}
	return $out;
}	

/**
 * count how many cartbridge for a cartbridge type
 *
 * count how many cartbridge for the cartbridge type $tID
 *
 *@param $tID integer: cartridge type identifier.
 *
 *@return integer : number of cartridge counted.
 *
 **/
function getCartridgesNumber($tID){
	global $db;
	$query = "SELECT ID FROM glpi_cartridges WHERE ( FK_glpi_cartridges_type = '$tID')";
	$result = $db->query($query);
	return $db->numrows($result);
}

/**
 * count how many cartridge used for a cartbridge type
 *
 * count how many cartridge used for the cartbridge type $tID
 *
 *@param $tID integer: cartridge type identifier.
 *
 *@return integer : number of cartridge used counted.
 *
 **/
function getUsedCartridgesNumber($tID){
	global $db;
	$query = "SELECT ID FROM glpi_cartridges WHERE ( FK_glpi_cartridges_type = '$tID' AND date_use IS NOT NULL AND date_out IS NULL)";
	$result = $db->query($query);
	return $db->numrows($result);
}

/**
 * count how many old cartbridge for a cartbridge type
 *
 * count how many old cartbridge for the cartbridge type $tID
 *
 *@param $tID integer: cartridge type identifier.
 *
 *@return integer : number of old cartridge counted.
 *
 **/
function getOldCartridgesNumber($tID){
	global $db;
	$query = "SELECT ID FROM glpi_cartridges WHERE ( FK_glpi_cartridges_type = '$tID'  AND date_out IS NOT NULL)";
	$result = $db->query($query);
	return $db->numrows($result);
}
/**
 * count how many cartbridge unused for a cartbridge type
 *
 * count how many cartbridge unused for the cartbridge type $tID
 *
 *@param $tID integer: cartridge type identifier.
 *
 *@return integer : number of cartridge unused counted.
 *
 **/
function getUnusedCartridgesNumber($tID){
	global $db;
	$query = "SELECT ID FROM glpi_cartridges WHERE ( FK_glpi_cartridges_type = '$tID'  AND date_use IS NULL)";
	$result = $db->query($query);
	return $db->numrows($result);
}


/**
 * To be commented
 *
 * 
 *
 *@param $cID integer : cartridge type.
 *
 *@return 
 *
 **/
function isNewCartridge($cID){
	global $db;
	$query = "SELECT ID FROM glpi_cartridges WHERE ( ID= '$cID' AND date_use IS NULL)";
	$result = $db->query($query);
	return ($db->numrows($result)==1);
}

/**
 * To be commented
 *
 * 
 *
 *@param $cID integer : cartridge type.
 *
 *@return 
 *
 **/
function isUsedCartridge($cID){
	global $db;
	$query = "SELECT ID 
		FROM glpi_cartridges 
		WHERE ( ID= '$cID' 
				AND date_use IS NOT NULL 
				AND date_out IS NULL)";
	$result = $db->query($query);
	return ($db->numrows($result)==1);
}

/**
 * To be commented
 *
 * 
 *
 *@param $cID integer : cartridge type.
 *
 *@return 
 *
 **/
function isOldCartridge($cID){
	global $db;
	$query = "SELECT ID 
		FROM glpi_cartridges 
		WHERE ( ID= '$cID' 
				AND date_out IS NOT NULL)";
	$result = $db->query($query);
	return ($db->numrows($result)==1);
}

/**
 * Get the dict value for the status of a cartridge
 *
 * 
 *
 *@param $cID integer : cartridge ID.
 *
 *@return string : dict value for the cartridge status.
 *
 **/
function getCartridgeStatus($cID){
	global $lang;
	if (isNewCartridge($cID)) return $lang["cartridges"][20];
	else if (isUsedCartridge($cID)) return $lang["cartridges"][21];
	else if (isOldCartridge($cID)) return $lang["cartridges"][22];
}

function cron_cartridge(){
	global $db,$cfg_glpi,$lang;

	// Get cartridges type with alarm activated and last warning > 7 days
	// TODO -> last warning delay to config
	$query="SELECT glpi_cartridges_type.ID AS cartID, glpi_cartridges_type.ref as cartref, glpi_cartridges_type.name AS cartname, glpi_cartridges_type.alarm AS threshold, glpi_alerts.ID AS alertID, glpi_alerts.date FROM glpi_cartridges_type LEFT JOIN glpi_alerts ON (glpi_cartridges_type.ID = glpi_alerts.FK_device AND glpi_alerts.device_type='".CARTRIDGE_TYPE."') WHERE glpi_cartridges_type.deleted='N' AND glpi_cartridges_type.alarm>='0' AND (glpi_alerts.date IS NULL OR (glpi_alerts.date+".$cfg_glpi["cartridges_alert"].") < CURRENT_TIMESTAMP()) ;";

	$result=$db->query($query);
	if ($db->numrows($result)>0){
		$message="";
		while ($data=$db->fetch_array($result)){
			if (($unused=getUnusedCartridgesNumber($data["cartID"]))<=$data["threshold"]){
				// define message alert
				$message.=$lang["mailing"][34]." ".$data["cartname"]." - ".$lang["cartridges"][2].": ".$data["cartref"]." - ".$lang["software"][20].": ".$unused."<br>\n";

				// Mark alert as done
				$alert=new Alert();
				//// if alert exists -> delete 
				if (!empty($data["alertID"])){
					$alert->delete(array("ID"=>$data["alertID"]));
				}

				$alert=new Alert();
				//// add alert
				$input["type"]=ALERT_THRESHOLD;
				$input["device_type"]=CARTRIDGE_TYPE;
				$input["FK_device"]=$data["cartID"];

				$alert->add($input);
			}
		}

		if (!empty($message)){
			$mail=new MailingAlert("alertcartridge",$message);
			$mail->send();
			return 1;
		}
	}
	return 0;
}

?>
