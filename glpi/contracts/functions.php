<?php
/*
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2005 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------

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
 ------------------------------------------------------------------------
*/
 
// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");

/**
* Print a good title for contract pages
*
*
*
*
*@return nothing (diplays)
*
**/
function titleContract(){

         GLOBAL  $lang,$HTMLRel;
         
         echo "<div align='center'><table border='0'><tr><td>";
         echo "<img src=\"".$HTMLRel."pics/contracts.png\" alt='".$lang["financial"][0]."' title='".$lang["financial"][0]."'></td><td><a  class='icon_consol' href=\"contracts-info-form.php\"><b>".$lang["financial"][0]."</b></a>";
         echo "</td></tr></table></div>";
}

/**
* Print search form for contracts
*
* 
*
*@param $field='' field selected in the search form
*@param $contains='' the search string
*@param $sort='' the "sort by" field value
*@param $phrasetype=''  not used (to be deleted)
*@param $deleted='' boolean : display deleted items or not.

*@return nothing (diplays)
*
**/
function searchFormContract($field="",$phrasetype= "",$contains="",$sort= "",$deleted="",$link="") {
	// Print Search Form
	
	GLOBAL $cfg_install, $cfg_layout, $layout, $lang,$HTMLRel;

	$option["glpi_contracts.ID"]				= $lang["financial"][28];
	$option["glpi_contracts.name"]			= $lang["financial"][27];
	$option["glpi_contracts.num"]			= $lang["financial"][4];
	$option["glpi_contracts.contract_type"]				= $lang["financial"][37];
	$option["glpi_contracts.begin_date"]			= $lang["financial"][7];	
	$option["glpi_contracts.duration"]			= $lang["financial"][8];
	$option["glpi_contracts.notice"]			= $lang["financial"][10];
	$option["glpi_contracts.bill_type"]			= $lang["financial"][58];
	$option["glpi_contracts.compta_num"]			= $lang["financial"][13];

	echo "<form method=get action=\"".$cfg_install["root"]."/contracts/contracts-search.php\">";
	echo "<div align='center'><table class='tab_cadre' width='800'>";
	echo "<tr><th colspan='4'><b>".$lang["search"][0].":</b></th></tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'>";

	echo "<table>";
	
	for ($i=0;$i<$_SESSION["glpisearchcount"];$i++){
		echo "<tr><td align='right'>";
		if ($i==0){
			echo "<a href='".$cfg_install["root"]."/computers/computers-search.php?add_search_count=1'><img src=\"".$HTMLRel."pics/plus.png\"></a>&nbsp;&nbsp;&nbsp;&nbsp;";
			if ($_SESSION["glpisearchcount"]>1)
			echo "<a href='".$cfg_install["root"]."/computers/computers-search.php?delete_search_count=1'><img src=\"".$HTMLRel."pics/moins.png\"></a>&nbsp;&nbsp;&nbsp;&nbsp;";
		}
		if ($i>0) {
			echo "<select name='link[$i]'>";
			
			echo "<option value='AND' ";
			if(is_array($link)&&isset($link[$i]) && $link[$i] == "AND") echo "selected";
			echo ">AND</option>";
			
			echo "<option value='OR' ";
			if(is_array($link)&&isset($link[$i]) && $link[$i] == "OR") echo "selected";
			echo ">OR</option>";		

			echo "</select>";
		}
		
		echo "<input type='text' size='15' name=\"contains[$i]\" value=\"". (is_array($contains)&&isset($contains[$i])?stripslashes($contains[$i]):"" )."\" >";
		echo "&nbsp;";
		echo $lang["search"][10]."&nbsp;";
	
		echo "<select name=\"field[$i]\" size='1'>";
        	echo "<option value='all' ";
		if(is_array($field)&&isset($field[$i]) && $field[$i] == "all") echo "selected";
		echo ">".$lang["search"][7]."</option>";
        	reset($option);
		foreach ($option as $key => $val) {
			echo "<option value=\"".$key."\""; 
			if(is_array($field)&&isset($field[$i]) && $key == $field[$i]) echo "selected";
			echo ">". $val ."</option>\n";
		}
		echo "</select>&nbsp;";

		
		echo "</td></tr>";
	}
	echo "</table>";
	echo "</td>";

	echo "<td>";

	echo $lang["search"][4];
	echo "&nbsp;<select name='sort' size='1'>";
	reset($option);
	foreach ($option as $key => $val) {
		echo "<option value=\"".$key."\"";
		if($key == $sort) echo "selected";
		echo ">".$val."</option>\n";
	}
	echo "</select> ";
	echo "</td><td><input type='checkbox' name='deleted' ".($deleted=='Y'?" checked ":"").">";
	echo "<img src=\"".$HTMLRel."pics/showdeleted.png\" alt='".$lang["common"][3]."' title='".$lang["common"][3]."'>";
	echo "</td><td width='80' align='center' class='tab_bg_2'>";
	echo "<input type='submit' value=\"".$lang["buttons"][0]."\" class='submit'>";
	echo "</td></tr></table></div></form>";
}


/**
* Search and list contacts
*
*
* Build the query, make the search and list contacts after a search.
*
*@param $target filename where to go when done.
*@param $username not used to be deleted.
*@param $field the field in witch the search would be done
*@param $contains the search string
*@param $sort the "sort by" field value
*@param $order ASC or DSC (for mysql query)
*@param $start row number from witch we start the query (limit $start,xxx)
*@param $deleted Query on deleted items or not.
*@param $phrasetype='' not used (to be deleted)
*@param $deleted='' boolean : display deleted items or not
*
*@return Nothing (display)
*
**/
function showContractList($target,$username,$field,$phrasetype,$contains,$sort,$order,$start,$deleted,$link) {

	// Lists Contract

	GLOBAL $cfg_install, $cfg_layout, $cfg_features, $lang, $HTMLRel;

	$db = new DB;

	$where ="";
	
	foreach ($field as $k => $f)
	if ($k<$_SESSION["glpisearchcount"])
	if ($contains[$k]==""){
		if ($k>0) $where.=" ".$link[$k]." ";
		$where.=" ('1'='1') ";
		}
	else {
		if ($k>0) $where.=" ".$link[$k]." ";
		$where.="( ";
		// Build query
		if($f == "all") {
			$fields = $db->list_fields("glpi_contracts");
			$columns = $db->num_fields($fields);
		
			for ($i = 0; $i < $columns; $i++) {
				if($i != 0) {
					$where .= " OR ";
				}
				$coco = $db->field_name($fields, $i);
				$where .= "glpi_contracts.".$coco . " LIKE '%".$contains[$k]."%'";
			}
		}
		else {
			if ($phrasetype == "contains") {
				$where .= "($f LIKE '%".$contains[$k]."%')";
			}
			else {
				$where .= "($f LIKE '".$contains[$k]."')";
			}
		}
	$where.=" )";
	}


	if (!$start) {
		$start = 0;
	}
	if (!$order) {
		$order = "ASC";
	}
	
	$query = "SELECT glpi_contracts.ID as ID FROM glpi_contracts ";
	
	$query.= " where ";
	if (!empty($where)) $query .= " $where AND ";
	$query .= " glpi_contracts.deleted='$deleted'  ORDER BY $sort $order";
//	echo $query;
	// Get it from database	
	if ($result = $db->query($query)) {
		$numrows = $db->numrows($result);

		// Limit the result, if no limit applies, use prior result
		if ($numrows>$cfg_features["list_limit"]) {
			$query_limit = $query." LIMIT $start,".$cfg_features["list_limit"]." ";
			$result_limit = $db->query($query_limit);
			$numrows_limit = $db->numrows($result_limit);
		} else {
			$numrows_limit = $numrows;
			$result_limit = $result;
		}

		if ($numrows_limit>0) {
			// Produce headline
			echo "<div align='center'><table class='tab_cadre' width='750'><tr>";

			// Type
			echo "<th>";
			if ($sort=="glpi_contracts.contract_type") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=glpi_contracts.contract_type&order=".($order=="ASC"?"DESC":"ASC")."&start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["financial"][37]."</a></th>";

			
			// nom
			echo "<th>";
			if ($sort=="glpi_contracts.name") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=glpi_contracts.name&order=".($order=="ASC"?"DESC":"ASC")."&start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["financial"][27]."</a></th>";
			
			// num
			echo "<th>";
			if ($sort=="glpi_contracts.num") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=glpi_contracts.num&order=".($order=="ASC"?"DESC":"ASC")."&start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["financial"][4]."</a></th>";

			// Begin date
			echo "<th>";
			if ($sort=="glpi_contracts.begin_date") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=glpi_contracts.begin_date&order=".($order=="ASC"?"DESC":"ASC")."&start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["financial"][7]."</a></th>";

			// Duration		
			echo "<th>";
			if ($sort=="glpi_contracts.duration") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=glpi_contracts.duration&order=".($order=="ASC"?"DESC":"ASC")."&start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["financial"][8]."</a></th>";

			// notice
			echo "<th>";
			if ($sort=="glpi_contracts.notice") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=glpi_contracts.notice&order=".($order=="ASC"?"DESC":"ASC")."&start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["financial"][10]."</a></th>";

			// Cost
			echo "<th>";
			if ($sort=="glpi_contracts.cost") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=glpi_contracts.cost&order=".($order=="ASC"?"DESC":"ASC")."&start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["financial"][5]."</a></th>";

			// Bill type
			echo "<th>";
			if ($sort=="glpi_contracts.bill_type") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=glpi_contracts.bill_type&order=".($order=="ASC"?"DESC":"ASC")."&start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["financial"][58]."</a></th>";

			echo "</tr>";

			for ($i=0; $i < $numrows_limit; $i++) {
				$ID = $db->result($result_limit, $i, "ID");

				$ct = new Contract;
				$ct->getfromDB($ID);

				echo "<tr class='tab_bg_2' align='center'>";
				echo "<td>".getDropdownName("glpi_dropdown_contract_type",$ct->fields["contract_type"])."</td>";
				echo "<td><b>";
				echo "<a href=\"".$cfg_install["root"]."/contracts/contracts-info-form.php?ID=$ID\">";
				echo $ct->fields["name"]." (".$ct->fields["ID"].")";
				echo "</a></b></td>";
				echo "<td>".$ct->fields["num"]."</td>";
				echo "<td>".$ct->fields["begin_date"]."</td>";
				echo "<td>".$ct->fields["duration"]." ".$lang["financial"][57]."</td>";
				echo "<td>".$ct->fields["notice"]." ".$lang["financial"][57]."</td>";				
				echo "<td>".$ct->fields["cost"]."</td>";				
				echo "<td>".$ct->fields["bill_type"]."</td>";				
				
				echo "</tr>";
			}

			// Close Table
			echo "</table></div>";

			// Pager
			$parameters="sort=$sort&order=$order".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains);
			printPager($start,$numrows,$target,$parameters);

		} else {
			echo "<div align='center'><b>".$lang["financial"][40]."</b></div>";
			
		}
	}
}

/**
* Print the contract form
*
*
* Print général contract form
*
*@param $target filename : where to go when done.
*@param $ID Integer : Id of the contact to print
*@param $search : not used (to be deleted)
*
*@return Nothing (display)
*
**/
function showContractForm ($target,$ID,$search) {
	// Show Contract or blank form
	
	GLOBAL $cfg_layout,$cfg_install,$lang,$HTMLRel;

	$con = new Contract;

	echo "<form name='form' method='post' action=\"$target\"><div align='center'>";
	echo "<table class='tab_cadre'>";
	echo "<tr><th colspan='4'><b>";
	if (!$ID) {
		echo $lang["financial"][36].":";
		$con->getEmpty();
	} else {
		$con->getfromDB($ID);
		echo $lang["financial"][1]." ID $ID:";
	}		
	echo "</b></th></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["financial"][6].":		</td><td >";
	dropdownValue("glpi_dropdown_contract_type","contract_type",$con->fields["contract_type"]);
	echo "</td>";

	echo "<td>".$lang["financial"][27].":		</td>";
	echo "<td><input type='text' name='name' value=\"".$con->fields["name"]."\" size='25'></td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["financial"][4].":		</td>";
	echo "<td><input type='text' name='num' value=\"".$con->fields["num"]."\" size='25'></td>";

	echo "<td>".$lang["financial"][7].":	</td>";
	echo "<td>";
	showCalendarForm("form","begin_date",$con->fields["begin_date"]);	
    	echo "</td>";
	echo "</tr>";


	echo "<tr class='tab_bg_1'><td>".$lang["financial"][5].":		</td>";
	echo "<td><input type='text' name='cost' value=\"".$con->fields["cost"]."\" size='10'></td>";

	echo "<td>".$lang["financial"][13].":		</td>";
	echo "<td><input type='text' name='compta_num' value=\"".$con->fields["compta_num"]."\" size='25'></td>";
	echo "</tr>";


	echo "<tr class='tab_bg_1'><td>".$lang["financial"][8].":		</td><td>";
	dropdownContractTime("duration",$con->fields["duration"]);
	echo " ".$lang["financial"][57];
	if ($con->fields["begin_date"]!=''&&$con->fields["begin_date"]!="0000-00-00")
	echo " -> ".getWarrantyExpir($con->fields["begin_date"],$con->fields["duration"]);
	echo "</td>";

	echo "<td>".$lang["financial"][10].":		</td><td>";
	dropdownContractTime("notice",$con->fields["notice"]);
	echo " ".$lang["financial"][57];
	if ($con->fields["begin_date"]!=''&&$con->fields["begin_date"]!="0000-00-00")
	echo " -> ".getWarrantyExpir($con->fields["begin_date"],$con->fields["duration"]-$con->fields["notice"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["financial"][69].":		</td><td>";
	dropdownContractPeriodicity("periodicity",$con->fields["periodicity"]);
	echo "</td>";


	echo "<td>".$lang["financial"][11].":		</td>";
	echo "<td>";
		dropdownContractPeriodicity("facturation",$con->fields["facturation"]);
	echo "</td></tr>";


	echo "<tr class='tab_bg_1'><td>".$lang["financial"][83].":		</td><td>";
	dropdownContractTime("device_countmax",$con->fields["device_countmax"]);
	echo "</td>";


	echo "<td>&nbsp;</td>";
	echo "<td>&nbsp;</td></tr>";



	echo "<tr class='tab_bg_1'><td valign='top'>";
	echo $lang["financial"][12].":	</td>";
	echo "<td align='center' colspan='3'><textarea cols='50' rows='4' name='comments' >".$con->fields["comments"]."</textarea>";
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td>".$lang["financial"][59].":		</td>";
	echo "<td colspan='3'>&nbsp;</td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["financial"][60].":		</td><td colspan='3'>";
	echo $lang["financial"][63].":";
	dropdownHours("week_begin_hour",$con->fields["week_begin_hour"]);	
	echo $lang["financial"][64].":";
	dropdownHours("week_end_hour",$con->fields["week_end_hour"]);	
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["financial"][61].":		</td><td colspan='3'>";
	dropdownYesNo("saturday",$con->fields["saturday"]);
	echo $lang["financial"][63].":";
	dropdownHours("saturday_begin_hour",$con->fields["saturday_begin_hour"]);	
	echo $lang["financial"][64].":";
	dropdownHours("saturday_end_hour",$con->fields["saturday_end_hour"]);	
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["financial"][62].":		</td><td colspan='3'>";
	dropdownYesNo("monday",$con->fields["monday"]);
	echo $lang["financial"][63].":";
	dropdownHours("monday_begin_hour",$con->fields["monday_begin_hour"]);	
	echo $lang["financial"][64].":";
	dropdownHours("monday_end_hour",$con->fields["monday_end_hour"]);	
	echo "</td></tr>";
	
	if (!$ID) {

		echo "<tr>";
		echo "<td class='tab_bg_2' valign='top' colspan='4'>";
		echo "<div align='center'><input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'></div>";
		echo "</td>";
		echo "</tr>";

		echo "</table></div></form>";

	} else {

		echo "<tr>";
                echo "<td class='tab_bg_2'></td>";
                echo "<td class='tab_bg_2' valign='top'>";
		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
		echo "<div align='center'><input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'></div>";
		echo "</td>\n\n";
		
		echo "<td class='tab_bg_2' valign='top'  colspan='2'>\n";
		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
		if ($con->fields["deleted"]=='N')
		echo "<div align='center'><input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'></div>";
		else {
		echo "<div align='center'><input type='submit' name='restore' value=\"".$lang["buttons"][21]."\" class='submit'>";
		
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$lang["buttons"][22]."\" class='submit'></div>";
		}
		
		echo "</td>";
		echo "</tr>";

		echo "</table></div>";
		echo "</form>";
	}

}

/**
* Update some elements of a contract in the database
*
* Update some elements of a contract in the database.
*
*@param $input array : the _POST vars returned bye the contract form when press update (see showcontractform())
*
*
*@return Nothing (call to the class member)
*
**/
function updateContract($input) {
	// Update Software in the database

	$con = new Contract;
	$con->getFromDB($input["ID"]);

	// Fill the update-array with changes
	$x=0;
	foreach ($input as $key => $val) {
		if (array_key_exists($key,$con->fields) && $con->fields[$key] != $input[$key]) {
			$con->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}
	if(!empty($updates)) {
	
		$con->updateInDB($updates);
	}
}

/**
* Add a contract in the database.
*
* Add a contract in the database with all it's items.
*
*@param $input array : the _POST vars returned bye the contact form when press add(see showcontractform())
*
*
*@return boolean : true or false
*
**/
function addContract($input) {
	
	$con = new Contract;

	// dump status
	$null = array_pop($input);

	// fill array for update
	foreach ($input as $key => $val) {
		if (empty($con->fields[$key]) || $con->fields[$key] != $input[$key]) {
			$con->fields[$key] = $input[$key];
		}
	}

	return $con->addToDB();
}

/**
* Delete a contract in the database.
*
* Delete a contract in the database.
*
*@param $input array : the _POST vars returned bye the contact form when press delete(see showcontractform())
*@param $force=0 boolean : int : how far the contract is deleted (moved to trash or purged from db).
*
*@return Nothing ()
*
**/
function deleteContract($input,$force=0) {
	// Delete Contract
	
	$con = new Contract;
	$con->deleteFromDB($input["ID"],$force);
} 

/**
* Restore a contract trashed in the database.
*
* Restore a contract trashed in the database.
*
*@param $input array : the _POST vars returned bye the contract form when press restore(see showcontractform())
*
*@return Nothing ()
*
**/
function restoreContract($input) {
	// Restore Contract
	
	$con = new Contract;
	$con->restoreInDB($input["ID"]);
} 

/**
* Print the HTML array for contract on devices
*
* Print the HTML array for contract on devices $instID
*
*@param $instID array : Contract identifier.
*@param $search='' not used (to be deleted)
*
*@return Nothing (display)
*
**/
function showDeviceContract($instID,$search='') {
	GLOBAL $cfg_layout,$cfg_install, $lang;

    $db = new DB;
	$query = "SELECT * FROM glpi_contract_device WHERE glpi_contract_device.FK_contract = '$instID' AND glpi_contract_device.is_template='0' order by device_type, FK_device";
//echo $query;	
	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;
	
    echo "<form method='post' action=\"".$cfg_install["root"]."/contracts/contracts-info-form.php\">";
	echo "<br><br><div align='center'><table class='tab_cadre' width='90%'>";
	echo "<tr><th colspan='3'>".$lang["financial"][49].":</th></tr>";
	echo "<tr><th>".$lang['financial'][37]."</th>";
	echo "<th>".$lang['financial'][27]."</th>";
	echo "<th>&nbsp;</th></tr>";

	while ($i < $number) {
		$device_ID=$db->result($result, $i, "FK_device");
		$ID=$db->result($result, $i, "ID");
		$type=$db->result($result, $i, "device_type");
		$con=new CommonItem;
		$con->getFromDB($type,$device_ID);
	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'>".$con->getType()."</td>";
	echo "<td align='center' ".(isset($con->obj->fields['deleted'])&&$con->obj->fields['deleted']=='Y'?"class='tab_bg_2_2'":"").">".$con->getLink()."</td>";
	echo "<td align='center' class='tab_bg_2'><a href='".$_SERVER["PHP_SELF"]."?deleteitem=deleteitem&ID=$ID'><b>".$lang["buttons"][6]."</b></a></td></tr>";
	$i++;
	}
	echo "<tr class='tab_bg_1'><td>&nbsp;</td><td align='center'>";
	echo "<div class='software-instal'><input type='hidden' name='conID' value='$instID'>";
		dropdownAllItems("item",0,$search);
	echo "&nbsp;<input type='submit' name='additem' value=\"".$lang["buttons"][8]."\" class='submit'></div>";
	echo "</form>";
	echo "</td>";
	
	
	echo "<td align='center' class='tab_bg_2'>";
	echo "<form method='get' action=\"".$cfg_install["root"]."/contracts/contracts-info-form.php?ID=$instID\">";	
	echo "<input type='text' name='search' value=\"".$search."\" size='15'>";
	echo "<input type='hidden' name='ID' value='$instID'>";
	echo "&nbsp;<input type='submit' name='bsearch' value=\"".$lang["buttons"][0]."\" class='submit'>";
	echo "</td></tr>";
	
	echo "</table></div></form>"    ;
	
}

/**
* Link a contract to a device
*
* Link the contract $conID to the device $ID witch device type is $type. 
*
*@param $conID integer : contract identifier.
*@param $type integer : device type identifier.
*@param $ID integer : device identifier.
*
*@return Nothing ()
*
**/
function addDeviceContract($conID,$type,$ID,$template=0){

if ($ID>0&&$conID>0){
	$db = new DB;
	$query="INSERT INTO glpi_contract_device (FK_contract,FK_device, device_type, is_template ) VALUES ('$conID','$ID','$type','$template');";
	$result = $db->query($query);
}
}

/**
* Delete a contract device
*
* Delete the contract device $ID
*
*@param $ID integer : contract device identifier.
*
*@return Nothing ()
*
**/
function deleteDeviceContract($ID){

$db = new DB;
$query="DELETE FROM glpi_contract_device WHERE ID= '$ID';";
//echo $query;
$result = $db->query($query);
}

/**
* Print the HTML array for contract on entreprises
*
* Print the HTML array for contract on entreprises for contract $instID
*
*@param $instID array : Contract identifier.
*
*@return Nothing (display)
*
**/
function showEnterpriseContract($instID) {
	GLOBAL $cfg_layout,$cfg_install, $lang,$HTMLRel;

    $db = new DB;
	$query = "SELECT glpi_contract_enterprise.ID as ID, glpi_enterprises.ID as entID, glpi_enterprises.name as name, glpi_enterprises.website as website, glpi_enterprises.phonenumber as phone, glpi_enterprises.type as type";
	$query.= " FROM glpi_enterprises,glpi_contract_enterprise WHERE glpi_contract_enterprise.FK_contract = '$instID' AND glpi_contract_enterprise.FK_enterprise = glpi_enterprises.ID";
	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;
	
    echo "<form method='post' action=\"".$cfg_install["root"]."/contracts/contracts-info-form.php\">";
	echo "<br><br><div align='center'><table class='tab_cadre' width='90%'>";
	echo "<tr><th colspan='5'>".$lang["financial"][65].":</th></tr>";
	echo "<tr><th>".$lang['financial'][26]."</th>";
	echo "<th>".$lang['financial'][79]."</th>";
	echo "<th>".$lang['financial'][29]."</th>";
	echo "<th>".$lang['financial'][45]."</th>";
	echo "<th>&nbsp;</th></tr>";

	while ($i < $number) {
		$ID=$db->result($result, $i, "ID");
		$website=$db->result($result, $i, "glpi_enterprises.website");
		if (!empty($website)){
			$website=$db->result($result, $i, "website");
			if (!ereg("https*://",$website)) $website="http://".$website;
			$website="<a target=_blank href='$website'>".$db->result($result, $i, "website")."</a>";
		}
	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'>".getDropdownName("glpi_enterprises",$db->result($result, $i, "entID"))."</td>";
	echo "<td align='center'>".getDropdownName("glpi_dropdown_enttype",$db->result($result, $i, "type"))."</td>";
	echo "<td align='center'>".$db->result($result, $i, "phone")."</td>";
	echo "<td align='center'>".$website."</td>";
	echo "<td align='center' class='tab_bg_2'><a href='".$_SERVER["PHP_SELF"]."?deleteenterprise=deleteenterprise&ID=$ID'><b>".$lang["buttons"][6]."</b></a></td></tr>";
	$i++;
	}
	echo "<tr class='tab_bg_1'><td>&nbsp;</td><td align='center'>";
	echo "<div class='software-instal'><input type='hidden' name='conID' value='$instID'>";
		dropdown("glpi_enterprises","entID");
	echo "</div></td><td align='center'>";
	echo "<input type='submit' name='addenterprise' value=\"".$lang["buttons"][8]."\" class='submit'>";
	echo "</td><td>&nbsp;</td><td>&nbsp;</td>";
	
	echo "</tr>";
	
	echo "</table></div></form>"    ;
	
}

/**
* Link a contract to an entreprise
*
* Link the contract $conID to the entreprise $ID witch device type is $type. 
*
*@param $conID integer : contract identifier.
*@param $ID integer : entreprise identifier.
*
*@return Nothing ()
*
**/
function addEnterpriseContract($conID,$ID){
if ($conID>0&&$ID>0){
	$db = new DB;
	$query="INSERT INTO glpi_contract_enterprise (FK_contract,FK_enterprise ) VALUES ('$conID','$ID');";
	$result = $db->query($query);
}
}

/**
* Delete a contract entreprise
*
* Delete the contract entreprise $ID
*
*@param $ID integer : contract entreprise identifier.
*
*@return Nothing ()
*
**/
function deleteEnterpriseContract($ID){

$db = new DB;
$query="DELETE FROM glpi_contract_enterprise WHERE ID= '$ID';";
$result = $db->query($query);
}

/**
* Print a select with contract time options
*
* Print a select named $name with contract time options and selected value $value
*
*@param $name string : HTML select name
*@param $value=0 integer : HTML select selected value
*
*@return Nothing (display)
*
**/
function dropdownContractTime($name,$value=0){
	global $lang;
	
	echo "<select name='$name'>";
	for ($i=0;$i<=120;$i+=1)
	echo "<option value='$i' ".($value==$i?" selected ":"").">$i</option>";	
	echo "</select>";	
}

/**
* Print a select with contract priority
*
* Print a select named $name with contract priority options and selected value $value
*
*@param $name string : HTML select name
*@param $value=0 integer : HTML select selected value
*
*@return Nothing (display)
*
**/
function dropdownContractPeriodicity($name,$value=0){
	global $lang;
	
	echo "<select name='$name'>";
	echo "<option value='0' ".($value==0?" selected ":"").">-------------</option>";
	echo "<option value='1' ".($value==1?" selected ":"").">".$lang["financial"][70]."</option>";
	echo "<option value='2' ".($value==2?" selected ":"").">".$lang["financial"][71]."</option>";
	echo "<option value='3' ".($value==3?" selected ":"").">".$lang["financial"][72]."</option>";
	echo "<option value='4' ".($value==4?" selected ":"").">".$lang["financial"][73]."</option>";
	echo "<option value='5' ".($value==5?" selected ":"").">".$lang["financial"][74]."</option>";
	echo "<option value='6' ".($value==6?" selected ":"").">".$lang["financial"][75]."</option>";
	echo "</select>";	
}

/**	
* Get from dicts the Contract periodicity string
*
* Get the contract periodicity identified bye $value from dicts.
*
*@param $value integer : contract periodicity value.
*
*
*@return string : dict entry
*
**/
function getContractPeriodicity($value){
	global $lang;
	
	switch ($value){
	case 1 :
		return $lang["financial"][70];
		break;
	case 2 :
		return $lang["financial"][71];
		break;
	case 3 :
		return $lang["financial"][72];
		break;
	case 4 :
		return $lang["financial"][73];
		break;
	case 5 :
		return $lang["financial"][74];
		break;
	case 6 :
		return $lang["financial"][75];
		break;
	case 0 :
		return "";
		break;
	
	}	
}


/**
* Print a select with hours
*
* Print a select named $name with hours options and selected value $value
*
*@param $name string : HTML select name
*@param $value=0 integer : HTML select selected value
*
*@return Nothing (display)
*
**/
function dropdownHours($name,$value){

	echo "<select name='$name'>";
	for ($i=0;$i<10;$i++){
	$tmp="0".$i;
	$val=$tmp.":00";
	echo "<option value='$val' ".($value==$val.":00"?" selected ":"").">$val</option>";
	}
	for ($i=10;$i<24;$i++){
	$val=$i.":00";
	echo "<option value='$val' ".($value==$val.":00"?" selected ":"").">$val</option>";
	}
	echo "</select>";	
}	


/**
* Get the entreprise identifier from a contract
*
* Get the entreprise identifier for the contract $ID
*
*@param $ID integer : Contract entreprise identifier
*
*@return integer enterprise identifier
*
**/
function getContractEnterprises($ID){
	global $HTMLRel;
    $db = new DB;
	$query = "SELECT glpi_enterprises.* FROM glpi_contract_enterprise, glpi_enterprises WHERE glpi_contract_enterprise.FK_enterprise = glpi_enterprises.ID AND glpi_contract_enterprise.FK_contract = '$ID'";
	$result = $db->query($query);
	$out="";
	while ($data=$db->fetch_array($result)){
		$out.= getDropdownName("glpi_enterprises",$data['ID'])."<br>";
		
	}
	return $out;
}

/**
* Print a select with contracts
*
* Print a select named $name with contracts options and selected value $value
*
*@param $name string : HTML select name
*
*@return Nothing (display)
*
**/
function dropdownContracts($name){

	$db=new DB;
	$query="SELECT * from glpi_contracts WHERE deleted = 'N' order by begin_date DESC";
	$result=$db->query($query);
	echo "<select name='$name'>";
	echo "<option value='-1'>-----</option>";
	while ($data=$db->fetch_array($result)){
	if ($data["device_countmax"]==0||$data["device_countmax"]>countDeviceForContract($data['ID'])){
		echo "<option value='".$data["ID"]."'>";
		echo $data["begin_date"]." - ".$data["name"];
		echo "</option>";
	}
	}

	echo "</select>";	
	
	
	
}

/**
* Print an HTML array with contracts associated to a device
*
* Print an HTML array with contracts associated to the device identified by $ID from device type $device_type 
*
*@param $device_type string : HTML select name
*@param $ID integer device ID
*@param $withtemplate='' not used (to be deleted)
*
*@return Nothing (display)
*
**/
function showContractAssociated($device_type,$ID,$withtemplate=''){

	GLOBAL $cfg_layout,$cfg_install, $lang,$HTMLRel;

    $db = new DB;
	$query = "SELECT * FROM glpi_contract_device WHERE glpi_contract_device.FK_device = '$ID' AND glpi_contract_device.device_type = '$device_type' ";

	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;
	
    if ($withtemplate!=2) echo "<form method='post' action=\"".$cfg_install["root"]."/contracts/contracts-info-form.php\">";
	echo "<br><br><div align='center'><table class='tab_cadre' width='90%'>";
	echo "<tr><th colspan='7'>".$lang["financial"][66].":</th></tr>";
	echo "<tr><th>".$lang['financial'][27]."</th>";
	echo "<th>".$lang['financial'][4]."</th>";
	echo "<th>".$lang['financial'][6]."</th>";
	echo "<th>".$lang['financial'][26]."</th>";
	echo "<th>".$lang['financial'][7]."</th>";	
	echo "<th>".$lang['financial'][8]."</th>";	
	if ($withtemplate!=2)echo "<th>&nbsp;</th>";
	echo "</tr>";

	while ($i < $number) {
		$cID=$db->result($result, $i, "FK_contract");
		$assocID=$db->result($result, $i, "ID");
		$con=new Contract;
		$con->getFromDB($cID);
	echo "<tr class='tab_bg_1".($con->fields["deleted"]=='Y'?"_2":"")."'>";
	echo "<td align='center'><a href='".$HTMLRel."contracts/contracts-info-form.php?ID=$cID'><b>".$con->fields["name"]." (".$con->fields["ID"].")</b></a></td>";
	echo "<td align='center'>".$con->fields["num"]."</td>";
	echo "<td align='center'>".getDropdownName("glpi_dropdown_contract_type",$con->fields["contract_type"])."</td>";
	echo "<td align='center'>".getContractEnterprises($cID)."</td>";	
	echo "<td align='center'>".$con->fields["begin_date"]."</td>";
	echo "<td align='center'>".$con->fields["duration"]." ".$lang["financial"][57];
	if ($con->fields["begin_date"]!=''&&$con->fields["begin_date"]!="0000-00-00") echo " -> ".getWarrantyExpir($con->fields["begin_date"],$con->fields["duration"]);
	echo "</td>";

	if ($withtemplate!=2)echo "<td align='center' class='tab_bg_2'><a href='".$HTMLRel."contracts/contracts-info-form.php?deleteitem=deleteitem&ID=$assocID'><b>".$lang["buttons"][6]."</b></a></td></tr>";
	$i++;
	}
	$q="SELECT * FROM glpi_contracts WHERE deleted='N'";
	$result = $db->query($q);
	$nb = $db->numrows($result);
	
	if ($withtemplate!=2&&$nb>0){
		echo "<tr class='tab_bg_1'><td>&nbsp;</td><td align='center'>";
		echo "<div class='software-instal'><input type='hidden' name='ID' value='$ID'><input type='hidden' name='type' value='$device_type'>";
		dropdownContracts("conID");
		echo "</div></td><td align='center'>";
		echo "<input type='submit' name='additem' value=\"".$lang["buttons"][8]."\" class='submit'>";
		echo "</td>";
		
		echo "<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
	}
	if (!empty($withtemplate))
	echo "<input type='hidden' name='is_template' value='1'>";
	echo "</table></div>"    ;
	echo "</form>";
	
}


/**
* Print an HTML array with contracts associated to a device
*
* Print an HTML array with contracts associated to the device identified by $ID from device type $device_type 
*
*@param $device_type string : HTML select name
*@param $ID integer device ID
*@param $withtemplate='' not used (to be deleted)
*
*@return Nothing (display)
*
**/
function showContractAssociatedEnterprise($ID){

	GLOBAL $cfg_layout,$cfg_install, $lang,$HTMLRel;

    $db = new DB;
	$query = "SELECT * FROM glpi_contract_enterprise WHERE glpi_contract_enterprise.FK_enterprise = '$ID'";

	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;
	
    echo "<form method='post' action=\"".$cfg_install["root"]."/contracts/contracts-info-form.php\">";
	echo "<br><br><div align='center'><table class='tab_cadre' width='90%'>";
	echo "<tr><th colspan='7'>".$lang["financial"][66].":</th></tr>";
	echo "<tr><th>".$lang['financial'][27]."</th>";
	echo "<th>".$lang['financial'][4]."</th>";
	echo "<th>".$lang['financial'][6]."</th>";
	echo "<th>".$lang['financial'][26]."</th>";
	echo "<th>".$lang['financial'][7]."</th>";	
	echo "<th>".$lang['financial'][8]."</th>";	
	echo "<th>&nbsp;</th>";
	echo "</tr>";

	while ($i < $number) {
		$cID=$db->result($result, $i, "FK_contract");
		$assocID=$db->result($result, $i, "ID");
		$con=new Contract;
		$con->getFromDB($cID);
	echo "<tr class='tab_bg_1".($con->fields["deleted"]=='Y'?"_2":"")."'>";
	echo "<td align='center'><a href='".$HTMLRel."contracts/contracts-info-form.php?ID=$cID'><b>".$con->fields["name"]." (".$con->fields["ID"].")</b></a></td>";
	echo "<td align='center'>".$con->fields["num"]."</td>";
	echo "<td align='center'>".getDropdownName("glpi_dropdown_contract_type",$con->fields["contract_type"])."</td>";
	echo "<td align='center'>".getContractEnterprises($cID)."</td>";	
	echo "<td align='center'>".$con->fields["begin_date"]."</td>";
	echo "<td align='center'>".$con->fields["duration"]." ".$lang["financial"][57];
	if ($con->fields["begin_date"]!=''&&$con->fields["begin_date"]!="0000-00-00") echo " -> ".getWarrantyExpir($con->fields["begin_date"],$con->fields["duration"]);
	echo "</td>";

	echo "<td align='center' class='tab_bg_2'><a href='".$HTMLRel."contracts/contracts-info-form.php?deleteenterprise=deleteenterprise&ID=$assocID'><b>".$lang["buttons"][6]."</b></a></td></tr>";
	$i++;
	}
	$q="SELECT * FROM glpi_contracts WHERE deleted='N'";
	$result = $db->query($q);
	$nb = $db->numrows($result);
	
	if ($nb>0){
		echo "<tr class='tab_bg_1'><td>&nbsp;</td><td align='center'>";
		echo "<div class='software-instal'><input type='hidden' name='entID' value='$ID'>";
		dropdownContracts("conID");
		echo "</div></td><td align='center'>";
		echo "<input type='submit' name='addenterprise' value=\"".$lang["buttons"][8]."\" class='submit'>";
		echo "</td>";
		
		echo "<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
	}
	echo "</table></div>"    ;
	echo "</form>";
	
}

function addContractOptionFieldsToResearch($option){
global $lang;
$option["glpi_contracts.name"]=$lang["financial"][27]." ".$lang["financial"][1];
$option["glpi_contracts.num"]=$lang["financial"][4]." ".$lang["financial"][1];
return $option;

}

function getContractSearchToRequest($table,$type){
return " LEFT JOIN glpi_contract_device ON ($table.ID = glpi_contract_device.FK_device AND glpi_contract_device.device_type='".$type."') LEFT JOIN glpi_contracts ON (glpi_contracts.ID = glpi_contract_device.FK_contract)";

}

function getContractSearchToViewAllRequest($contains){
return " OR glpi_contracts.name LIKE '%".$contains."%' OR glpi_contracts.num LIKE '%".$contains."%' ";
}

function countDeviceForContract($ID){
    $db = new DB;
	$query = "SELECT * FROM glpi_contract_device WHERE FK_contract = '$ID' AND is_template='0'";

	$result = $db->query($query);
	return $db->numrows($result);
	
}
?>
