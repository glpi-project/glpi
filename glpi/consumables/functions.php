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
* Print a good title for Consumable pages
*
*
*
*
*@return nothing (diplays)
*
**/
function titleConsumable(){

         GLOBAL  $lang,$HTMLRel;
         
         echo "<div align='center'><table border='0'><tr><td>";
         echo "<img src=\"".$HTMLRel."pics/consommables.png\" alt='".$lang["consumables"][6]."' title='".$lang["consumables"][6]."'></td><td><a  class='icon_consol' href=\"consumables-info-form.php\"><b>".$lang["consumables"][6]."</b></a>";
         echo "</td></tr></table></div>";
}

function showConsumableOnglets($target,$withtemplate,$actif){
	global $lang,$HTMLRel;
	
	$template="";
	if(!empty($withtemplate)){
		$template="&amp;withtemplate=$withtemplate";
	}

	echo "<div id='barre_onglets'><ul id='onglet'>";
	echo "<li "; if ($actif=="1"){ echo "class='actif'";} echo  "><a href='$target&amp;onglet=1$template'>".$lang["title"][26]."</a></li>";
	echo "<li "; if ($actif=="4") {echo "class='actif'";} echo "><a href='$target&amp;onglet=4$template'>".$lang["Menu"][26]."</a></li>";
	echo "<li "; if ($actif=="5") {echo "class='actif'";} echo "><a href='$target&amp;onglet=5$template'>".$lang["title"][25]."</a></li>";
	
	if(empty($withtemplate)){
	echo "<li "; if ($actif=="7") {echo "class='actif'";} echo "><a href='$target&amp;onglet=7$template'>".$lang["title"][34]."</a></li>";
	
	echo "<li class='invisible'>&nbsp;</li>";
	echo "<li "; if ($actif=="-1") {echo "class='actif'";} echo "><a href='$target&amp;onglet=-1$template'>".$lang["title"][29]."</a></li>";
	
	}	
	echo "<li class='invisible'>&nbsp;</li>";
	
	if (empty($withtemplate)&&preg_match("/\?ID=([0-9]+)/",$target,$ereg)){
	$ID=$ereg[1];
	$next=getNextItem("glpi_consumables_type",$ID);
	$prev=getPreviousItem("glpi_consumables_type",$ID);
	$cleantarget=preg_replace("/\?ID=([0-9]+)/","",$target);
		if ($prev>0) echo "<li><a href='$cleantarget?ID=$prev'><img src=\"".$HTMLRel."pics/left.png\" alt='".$lang["buttons"][12]."' title='".$lang["buttons"][12]."'></a></li>";
	if ($next>0) echo "<li><a href='$cleantarget?ID=$next'><img src=\"".$HTMLRel."pics/right.png\" alt='".$lang["buttons"][11]."' title='".$lang["buttons"][11]."'></a></li>";
	}
	echo "</ul></div>";
	
}

/**
* Print search form for consumables
*
* 
*
*@param $field='' field selected in the search form
*@param $contains='' the search string
*@param $sort='' the "sort by" field value
*@param $deleted='' the deleted value 
*
*@return nothing (diplays)
*
**/
// Plus utilisé
/*
function searchFormConsumable($field="",$phrasetype= "",$contains="",$sort= "",$deleted="",$link="") {
	// Print Search Form
	
	GLOBAL $cfg_install, $cfg_layout, $layout, $lang, $HTMLRel;

	$option["glpi_consumables_type.ID"]				= $lang["consumables"][4];
	$option["glpi_consumables_type.name"]				= $lang["consumables"][1];
	$option["glpi_consumables_type.ref"]			= $lang["consumables"][2];
	$option["glpi_consumables_type.type"]			= $lang["consumables"][3];
	$option["glpi_enterprises.name"]			= $lang["consumables"][8];
	$option["glpi_dropdown_locations.name"]			= $lang["consumables"][36];	
	$option["resptech.name"]			=$lang["common"][10];
	
	echo "<form method=get action=\"".$cfg_install["root"]."/consumables/consumables-search.php\">";
	echo "<div align='center'><table class='tab_cadre' width='800'>";
	echo "<tr><th colspan='4'><b>".$lang["search"][0].":</b></th></tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<td align='center' >";
	echo "<table>";
	
	for ($i=0;$i<$_SESSION["glpisearchcount"];$i++){
		echo "<tr><td align='right'>";
		if ($i==0){
			echo "<a href='".$cfg_install["root"]."/computers/computers-search.php?add_search_count=1'><img src=\"".$HTMLRel."pics/plus.png\" alt='+'></a>&nbsp;&nbsp;&nbsp;&nbsp;";
			if ($_SESSION["glpisearchcount"]>1)
			echo "<a href='".$cfg_install["root"]."/computers/computers-search.php?delete_search_count=1'><img src=\"".$HTMLRel."pics/moins.png\" alt='-'></a>&nbsp;&nbsp;&nbsp;&nbsp;";
		}
		if ($i>0) {
			echo "<select name='link[$i]'>";
			
			echo "<option value='AND' ";
			if(is_array($link)&&isset($link[$i]) && $link[$i] == "AND") echo "selected";
			echo ">AND</option>";
			
			echo "<option value='OR' ";
			if(is_array($link)&&isset($link[$i]) && $link[$i] == "OR") echo "selected";
			echo ">OR</option>";		

			echo "<option value='AND NOT' ";
			if(is_array($link)&&isset($link[$i]) && $link[$i] == "AND NOT") echo "selected";
			echo ">AND NOT</option>";		
			
			echo "<option value='OR NOT' ";
			if(is_array($link)&&isset($link[$i]) && $link[$i] == "OR NOT") echo "selected";
			echo ">OR NOT</option>";
			
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
*/
/**
* Search and list consumables
*
*
* Build the query, make the search and list selected consumables after a search query.
*
*@param $target filename where to go when done.
*@param $username not used to be deleted.
*@param $field the field in witch the search would be done
*@param $phrasetype not used any more (to be deleted)
*@param $contains the search string
*@param $sort the "sort by" field value
*@param $order ASC or DSC (for mysql query)
*@param $start row number from witch we start the query (limit $start,xxx)
*@param $deleted Query on deleted items or not.
*
*
*@return Nothing (display)
*
**/
// Plus utilisé
/*
function showConsumableList($target,$username,$field,$phrasetype,$contains,$sort,$order,$start,$deleted,$link) {

	// Lists ConsumableType

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
			$fields = $db->list_fields("glpi_consumables_type");
			$columns = $db->num_fields($fields);
			
			for ($i = 0; $i < $columns; $i++) {
				if($i != 0) {
					$where .= " OR ";
				}
				$coco = $db->field_name($fields, $i);
				if ($coco=="location"){
					$where .= getRealSearchForTreeItem("glpi_dropdown_locations",$contains[$k]);		
				} else if ($coco=="FK_glpi_enterprise"){
					$where.="glpi_enterprises.name LIKE '%".$contains[$k]."%'";
				} else if ($coco=="tech_num"){
					$where .= " resptech.name LIKE '%".$contains[$k]."%'";
				} else 
				$where .= "glpi_consumables_type.".$coco . " LIKE '%".$contains[$k]."%'";
			}
		}
		else {
					if ($f=="glpi_dropdown_locations.name"){
				$where .= getRealSearchForTreeItem("glpi_dropdown_locations",$contains[$k]);
			}		
			else if ($phrasetype == "contains") {
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
	
	$query = "SELECT glpi_consumables_type.*, glpi_enterprises.name FROM glpi_consumables_type ";
	$query.= " LEFT JOIN glpi_enterprises ON glpi_enterprises.ID = glpi_consumables_type.FK_glpi_enterprise ";
	$query.= " LEFT JOIN glpi_dropdown_locations ON glpi_dropdown_locations.ID = glpi_consumables_type.location ";
	$query.= " LEFT JOIN glpi_users as resptech ON (resptech.ID = glpi_consumables_type.tech_num ) ";
	$query.= " where ";
	if (!empty($where)) $query .= " $where AND ";
	$query .= " glpi_consumables_type.deleted='$deleted'  ORDER BY $sort $order";

	
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
			// Pager
			$parameters="sort=$sort&amp;order=$order".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains);
			printPager($start,$numrows,$target,$parameters);

			// Produce headline
			echo "<div align='center'><table class='tab_cadre' width='750'><tr>";

			// Name
			echo "<th>";
			if ($sort=="glpi_consumables_type.name") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=glpi_consumables_type.name&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["consumables"][1]."</a></th>";

			// Ref			
			echo "<th>";
			if ($sort=="glpi_consumables_type.ref") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=glpi_consumables_type.ref&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["consumables"][2]."</a></th>";

			// Type		
			echo "<th>";
			if ($sort=="glpi_consumables_type.type") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=glpi_consumables_type.type&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["consumables"][3]."</a></th>";

			// Manufacturer		
			echo "<th>";
			if ($sort=="glpi_enterprises.name") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=glpi_enterprises.name&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["consumables"][8]."</a></th>";

			// Location		
			echo "<th>";
			if ($sort=="glpi_dropdown_locations.completename") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=glpi_dropdown_locations.completename&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["consumables"][36]."</a></th>";
			
			// Cartouches
			echo "<th>".$lang["consumables"][0]."</th>";
		

			echo "</tr>";

			for ($i=0; $i < $numrows_limit; $i++) {
				$ID = $db->result($result_limit, $i, "ID");

				$ct = new ConsumableType;
				$ct->getfromDB($ID);

				echo "<tr class='tab_bg_2' align='center'>";
				echo "<td><b>";
				echo "<a href=\"".$cfg_install["root"]."/consumables/consumables-info-form.php?ID=$ID\">";
				echo $ct->fields["name"]." (".$ct->fields["ID"].")";
				echo "</a></b></td>";
				echo "<td>".$ct->fields["ref"]."</td>";
				echo "<td>".getDropdownName("glpi_dropdown_consumable_type",$ct->fields["type"])."</td>";
				echo "<td>". getDropdownName("glpi_enterprises",$ct->fields["FK_glpi_enterprise"]) ."</td>";
				echo "<td>". getDropdownName("glpi_dropdown_locations",$ct->fields["location"]) ."</td>";
				
				$highlight="";
				if (getUnusedConsumablesNumber($ct->fields["ID"])<=$ct->fields["alarm"])
				$highlight="class='tab_bg_1_2'";
				
				echo "<td $highlight>";
					countConsumables($ct->fields["ID"]);
				echo "</td>";
				echo "</tr>";
			}

			// Close Table
			echo "</table></div>";

			// Pager
			echo "<br>";
			//$parameters="sort=$sort&amp;order=$order".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains);
			printPager($start,$numrows,$target,$parameters);

		} else {
			echo "<div align='center'><b>".$lang["consumables"][7]."</b></div>";
		}
	}
}
*/

/**
* Print the consumable type form
*
*
* Print général consumable type form
*
*@param $target filename : where to go when done.
*@param $ID Integer : Id of the consumable type
*
*
*@return Nothing (display)
*
**/
function showConsumableTypeForm ($target,$ID) {
	// Show ConsumableType or blank form
	
	GLOBAL $cfg_layout,$cfg_install,$lang;

	$ct = new ConsumableType;
	$ct_spotted=false;
	
	if (!$ID) {
		
		if($ct->getEmpty()) $ct_spotted = true;
	} else {
		if($ct->getfromDB($ID)) $ct_spotted = true;
	}
	
	if ($ct_spotted){
	
	echo "<form method='post' action=\"$target\"><div align='center'>\n";
	echo "<table class='tab_cadre' width='800'>\n";
	echo "<tr><th colspan='3'><b>\n";
	if (!$ID) {
		echo $lang["consumables"][6].":";
	} else {
		echo $lang["consumables"][12]." ID $ID:";
	}		
	echo "</b></th></tr>\n";

	echo "<tr class='tab_bg_1'><td>".$lang["consumables"][1].":		</td>\n";
	echo "<td colspan='2'>";
	autocompletionTextField("name","glpi_consumables_type","name",$ct->fields["name"],25);	
	echo "</td></tr>\n";

	echo "<tr class='tab_bg_1'><td>".$lang["consumables"][2].":		</td>\n";
	echo "<td colspan='2'>";
	autocompletionTextField("ref","glpi_consumables_type","ref",$ct->fields["ref"],25);	
	echo "</td></tr>\n";

	echo "<tr class='tab_bg_1'><td>".$lang["consumables"][3].": 	</td><td colspan='2'>\n";
		dropdownValue("glpi_dropdown_consumable_type","type",$ct->fields["type"]);
	echo "</td></tr>\n";

	echo "<tr class='tab_bg_1'><td>".$lang["consumables"][8].": 	</td><td colspan='2'>\n";
		dropdownValue("glpi_enterprises","FK_glpi_enterprise",$ct->fields["FK_glpi_enterprise"]);
	echo "</td></tr>\n";

	echo "<tr class='tab_bg_1'><td>".$lang["common"][10].": 	</td><td colspan='2'>\n";
		dropdownUsersID("tech_num", $ct->fields["tech_num"]);
	echo "</td></tr>\n";

	echo "<tr class='tab_bg_1'><td>".$lang["consumables"][36].": 	</td><td colspan='2'>\n";
		dropdownValue("glpi_dropdown_locations","location",$ct->fields["location"]);
	echo "</td></tr>\n";

	echo "<tr class='tab_bg_1'><td>".$lang["consumables"][38].":</td><td colspan='2'><select name='alarm'>\n";
	for ($i=0;$i<=100;$i++)
		echo "<option value='$i' ".($i==$ct->fields["alarm"]?" selected ":"").">$i</option>";
	echo "</select></td></tr>\n";
	
	
	echo "<tr class='tab_bg_1'><td valign='top'>\n";
	echo $lang["consumables"][5].":	</td>";
	echo "<td align='center' colspan='2'><textarea cols='35' rows='4' name='comments' >".$ct->fields["comments"]."</textarea>";
	echo "</td></tr>\n";
	
	if (!$ID) {

		echo "<tr>\n";
		echo "<td class='tab_bg_2' valign='top' colspan='3'>\n";
		echo "<div align='center'><input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'></div>";
		echo "</td>";
		echo "</tr>\n";

		echo "</table></div></form>";

	} else {

		echo "<tr>\n";
                echo "<td class='tab_bg_2'></td>";
                echo "<td class='tab_bg_2' valign='top'>";
		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
		echo "<div align='center'><input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'></div>";
		echo "</td>";
//		echo "</form>\n\n";
//		echo "<form action=\"$target\" method='post'>\n";
		echo "<td class='tab_bg_2' valign='top'>\n";
//		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
		echo "<div align='center'>";
		if ($ct->fields["deleted"]=='N')
		echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
		else {
		echo "<input type='submit' name='restore' value=\"".$lang["buttons"][21]."\" class='submit'>";
		
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$lang["buttons"][22]."\" class='submit'>\n";
		}
		echo "</div>";
		echo "</td>";
		echo "</tr>\n";

		echo "</table></div></form>";
		
	}
	
	} else {
	
	echo "<div align='center'><b>".$lang["consumables"][7]."</b></div>";
	return false;
	}
	return true;
}
/**
* Update some elements of a consumable type in the database.
*
* Update some elements of a consumable type in the database.
*
*@param $input array : the _POST vars returned bye the cartrdge form when press update (see showconsumabletype())
*
*
*@return Nothing (call to the class member)
*TODO : error reporting.
**/
function updateConsumableType($input) {
	// Update ConsumableType in the database

	$sw = new ConsumableType;
	$sw->getFromDB($input["ID"]);
 
	// Fill the update-array with changes
	$x=0;
	foreach ($input as $key => $val) {
		if (array_key_exists($key,$sw->fields) && $sw->fields[$key] != $input[$key]) {
			$sw->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}
	if(!empty($updates)) {
	
		$sw->updateInDB($updates);
	}
}
/**
* Add  a consumable type in the database.
*
* Add from $input, a consumable type in the database. return true if it has been added correcly, false elsewhere.
*
*@param $input array : the _POST vars returned bye the cartrdge form when press add (see showconsumabletype())
*
*
*@return boolean true or false.
*
**/
function addConsumableType($input) {
	
	$sw = new ConsumableType;
	
	// dump status
	unset($input['add']);
	
	// fill array for update
	foreach ($input as $key => $val) {
		if ($key[0]!='_'&&(!isset($sw->fields[$key]) || $sw->fields[$key] != $input[$key])) {
			$sw->fields[$key] = $input[$key];
		}
	}
	return $sw->addToDB();
}

/**
* Delete a consumable type in the database.
*
* Delete a consumable type in the database.
*
*@param $input array : the _POST vars returned bye the consumable form when press delete (see showconsumabletype())
*@param $force=0 int : how far the consumable is deleted (moved to trash or purged from db).
*
*@return Nothing (call to the class member)
*TODO : error reporting.
**/
function deleteConsumableType($input,$force=0) {
	// Delete ConsumableType
	
	$ct = new ConsumableType;
	$ct->deleteFromDB($input["ID"],$force);
} 

/**
* restore some elements of a consumable type in the database.
*
* restore some elements of a consumable type witch has been deleted but not purged in the database.
*
*@param $input array : the _POST vars returned bye the consumable form when press restore (see showconsumabletype())
*
*
*@return Nothing (call to the class member)
*TODO : error reporting.
**/
function restoreConsumableType($input) {
	// Restore ConsumableType
	
	$ct = new ConsumableType;
	$ct->restoreInDB($input["ID"]);
} 

/**
* Print out a link to add directly a new consumable from a consumable type.
*
* Print out the link witch make a new consumable from consumable type idetified by $ID
*
*@param $ID Consumable type identifier.
*
*
*@return Nothing (displays)
**/
function showConsumableAdd($ID) {
	
	GLOBAL $cfg_layout,$cfg_install,$lang,$HTMLRel;
	
	
	echo "<form method='post'  action=\"".$HTMLRel."consumables/consumables-edit.php\">";
	echo "<div align='center'>&nbsp;<table class='tab_cadre' width='90%' cellpadding='2'>";
	echo "<tr><td align='center' class='tab_bg_2'><b>";
	echo "<a href=\"".$cfg_install["root"]."/consumables/consumables-edit.php?add=add&amp;tID=$ID\">";
	echo $lang["consumables"][17];
	echo "</a></b></td>";
	echo "<td align='center' class='tab_bg_2'>";
	echo "<input type='submit' name='add_several' value=\"".$lang["buttons"][8]."\" class='submit'>";
	echo "<input type='hidden' name='tID' value=\"$ID\">\n";

	echo "&nbsp;&nbsp;<select name='to_add'>";
	for ($i=1;$i<100;$i++)
	echo "<option value='$i'>$i</option>";
	echo "</select>&nbsp;&nbsp;";
	echo $lang["consumables"][16];
	echo "</td></tr>";
	echo "</table></div>";
	echo "</form><br>";
}
/**
* Print out the consumables of a defined type
*
* Print out all the consumables that are issued from the consumable type identified by $ID
*
*@param $ID integer : Consumable type identifier.
*@param $show_old=0 boolean : show old consumables or not. 
*
*@return Nothing (displays)
**/
function showConsumables ($tID,$show_old=0) {

	GLOBAL $cfg_layout, $cfg_install,$lang,$HTMLRel;
	
	$db = new DB;

	$query = "SELECT count(ID) AS COUNT  FROM glpi_consumables WHERE (FK_glpi_consumables_type = '$tID')";

	if ($result = $db->query($query)) {
		if ($db->result($result,0,0)!=0) { 
			$total=$db->result($result, 0, "COUNT");
			$unused=getUnusedConsumablesNumber($tID);
			$old=getOldConsumablesNumber($tID);

			echo "<br><div align='center'><table cellpadding='2' class='tab_cadre' width='90%'>";
			if ($show_old==0){
				echo "<tr><th colspan='6'>";
				echo $total;
				echo "&nbsp;".$lang["consumables"][16]."&nbsp;-&nbsp;$unused&nbsp;".$lang["consumables"][13]."&nbsp;-&nbsp;$old&nbsp;".$lang["consumables"][15]."</th>";
				echo "<th colspan='1'>";
				echo "&nbsp;</th></tr>";
			}
			else { // Old
				echo "<tr><th colspan='6'>";
				echo $lang["consumables"][35];
				echo "</th>";
				echo "<th colspan='2'>";
				echo "&nbsp;</th></tr>";
				
			}
			$i=0;
			echo "<tr><th>".$lang["consumables"][4]."</th><th>".$lang["consumables"][23]."</th><th>".$lang["consumables"][24]."</th><th>".$lang["consumables"][26]."</th>";

			echo "<th>".$lang["financial"][3]."</th>";
			
				echo "<th>&nbsp;</th>";
				echo "<th>&nbsp;</th></tr>";
			} else {

				echo "<br><div align='center'><table border='0' width='50%' cellpadding='2'>";
				echo "<tr><th>".$lang["consumables"][7]."</th></tr>";
				echo "</table></div>";
		}
	}

if ($show_old==0){ // NEW
$where= " AND date_out IS NULL";
} else { //OLD
$where= " AND date_out IS NOT NULL";
}

$query = "SELECT * FROM glpi_consumables WHERE (FK_glpi_consumables_type = '$tID') $where ORDER BY date_out ASC, date_in";

//echo $query;
	$pages=array();
	if ($result = $db->query($query)) {			
	$number=$db->numrows($result);
	while ($data=$db->fetch_array($result)) {
		$date_in=$data["date_in"];
		$date_out=$data["date_out"];
						
		echo "<tr  class='tab_bg_1'><td align='center'>";
		echo $data["ID"]; 
		echo "</td><td align='center'>";
		echo getConsumableStatus($data["ID"]);
		echo "</td><td align='center'>";
		echo $date_in;
		echo "</td><td align='center'>";
		echo $date_out;		
		echo "</td>";

		echo "<td align='center'>";
		showDisplayInfocomLink(CONSUMABLE_ITEM_TYPE,$data["ID"],1);
		echo "</td>";

				
		if ($show_old==0){
			echo "<td align='center'>";
			echo "<a href='".$cfg_install["root"]."/consumables/consumables-edit.php?out=out&amp;ID=".$data["ID"]."&amp;tID=$tID'>".$lang["consumables"][32]."</a>";
			echo "</td>";
		}

		if ($show_old!=0){
			echo "<td align='center'>";
			echo "<a href='".$cfg_install["root"]."/consumables/consumables-edit.php?restore=restore&amp;ID=".$data["ID"]."&amp;tID=$tID'>".$lang["consumables"][37]."</a>";
			echo "</td>";
		}						
		
		echo "<td align='center'>";
		
		echo "<a href='".$cfg_install["root"]."/consumables/consumables-edit.php?delete=delete&amp;ID=".$data["ID"]."&amp;tID=$tID'>".$lang["consumables"][31]."</a>";
		echo "</td></tr>";
		
	}	
	}	
echo "</table></div>\n\n";
	
}

/**
* Add  a consumable in the database.
*
* Add a new consumable that type is identified by $ID
*
*@param $tID : consumable type identifier
*
*
*@return boolean true or false.
*
**/
function addConsumable($tID) {
	$c = new Consumable;
	
	$input["FK_glpi_consumables_type"]=$tID;
	$input["date_in"]=date("Y-m-d");
	
	// fill array for update
	foreach ($input as $key => $val) {
		if (empty($c->fields[$key])) {
			$c->fields[$key] = $input[$key];
		}
	}
	$newID=$c->addToDB();
	
	// Add infocoms if exists for the licence
	$ic=new Infocom();
	if ($ic->getFromDB(CONSUMABLE_TYPE,$c->fields["FK_glpi_consumables_type"])){
		unset($ic->fields["ID"]);
		$ic->fields["FK_device"]=$newID;
		$ic->fields["device_type"]=CONSUMABLE_ITEM_TYPE;
		$ic->addToDB();
	}
	return $newID;
}
/**
* delete a consumable in the database.
*
* delete a consumable that is identified by $ID
*
*@param $tID : consumable type identifier
*
*
*@return nothing
*TODO error reporting
*
**/
function deleteConsumable($ID) {
	// Delete consumable
	
	$lic = new Consumable;
	$lic->deleteFromDB($ID);
	
} 

/**
* UnLink a consumable linked to a printer
*
* UnLink the consumable identified by $ID
*
*@param $ID : consumable identifier
*
*@return boolean
*
**/
function outConsumable($ID) {

	$db = new DB;
	$query = "UPDATE glpi_consumables SET date_out = '".date("Y-m-d")."' WHERE ID='$ID'";
//	echo $query;
	if ($result = $db->query($query)) {
		return true;
	} else {
		return false;
	}
}

function restoreConsumable($ID){
	$db = new DB;
	$query = "UPDATE glpi_consumables SET date_out = NULL WHERE ID='$ID'";
//	echo $query;
	if ($result = $db->query($query)) {
		return true;
	} else {
		return false;
	}
}


/**
* Print the consumable count HTML array for a defined consumable type
*
* Print the consumable count HTML array for the consumable type $tID
*
*@param $tID integer: consumable type identifier.
*
*@return nothing (display)
*
**/
function countConsumables($tID,$alarm) {
	
	GLOBAL $cfg_layout, $lang;
	
	$db = new DB;
	
	// Get total
	$total = getConsumablesNumber($tID);

	if ($total!=0) {
	$unused=getUnusedConsumablesNumber($tID);
	$old=getOldConsumablesNumber($tID);

	$highlight="";
	if ($unused<=$alarm)
		$highlight="class='tab_bg_1_2'";

	echo "<div $highlight><b>".$lang["consumables"][30].":&nbsp;$total</b>&nbsp;&nbsp;&nbsp;".$lang["consumables"][13].": $unused&nbsp;&nbsp;&nbsp;".$lang["consumables"][15].": $old</div>";			

	} else {
			echo "<div class='tab_bg_1_2'><i>".$lang["consumables"][9]."</i></div>";
	}
}	

/**
* count how many consumable for a consumable type
*
* count how many consumable for the consumable type $tID
*
*@param $tID integer: consumable type identifier.
*
*@return integer : number of consumable counted.
*
**/
function getConsumablesNumber($tID){
	$db=new DB;
	$query = "SELECT ID FROM glpi_consumables WHERE ( FK_glpi_consumables_type = '$tID')";
	$result = $db->query($query);
	return $db->numrows($result);
}

/**
* count how many old consumable for a consumable type
*
* count how many old consumable for the consumable type $tID
*
*@param $tID integer: consumable type identifier.
*
*@return integer : number of old consumable counted.
*
**/
function getOldConsumablesNumber($tID){
	$db=new DB;
	$query = "SELECT ID FROM glpi_consumables WHERE ( FK_glpi_consumables_type = '$tID'  AND date_out IS NOT NULL)";
	$result = $db->query($query);
	return $db->numrows($result);
}
/**
* count how many consumable unused for a consumable type
*
* count how many consumable unused for the consumable type $tID
*
*@param $tID integer: consumable type identifier.
*
*@return integer : number of consumable unused counted.
*
**/
function getUnusedConsumablesNumber($tID){
	$db=new DB;
	$query = "SELECT ID FROM glpi_consumables WHERE ( FK_glpi_consumables_type = '$tID'  AND date_out IS NULL)";
	$result = $db->query($query);
	return $db->numrows($result);
}


/**
* To be commented
*
* 
*
*@param $cID integer : consumable type.
*
*@return 
*
**/
function isNewConsumable($cID){
$db=new DB;
$query = "SELECT ID FROM glpi_consumables WHERE ( ID= '$cID' AND date_out IS NULL)";
$result = $db->query($query);
return ($db->numrows($result)==1);
}

/**
* To be commented
*
* 
*
*@param $cID integer : consumable type.
*
*@return 
*
**/
function isOldConsumable($cID){
$db=new DB;
$query = "SELECT ID FROM glpi_consumables WHERE ( ID= '$cID' AND date_out IS NOT NULL)";
$result = $db->query($query);
return ($db->numrows($result)==1);
}

/**
* Get the dict value for the status of a consumable
*
* 
*
*@param $cID integer : consumable ID.
*
*@return string : dict value for the consumable status.
*
**/
function getConsumableStatus($cID){
global $lang;
if (isNewConsumable($cID)) return $lang["consumables"][20];
else if (isOldConsumable($cID)) return $lang["consumables"][22];
}

?>
