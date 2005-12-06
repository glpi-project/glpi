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
* Print a good title for Cartridge pages
*
*
*
*
*@return nothing (diplays)
*
**/
function titleCartridge(){

         GLOBAL  $lang,$HTMLRel;
         
         echo "<div align='center'><table border='0'><tr><td>";
         echo "<img src=\"".$HTMLRel."pics/cartouches.png\" alt='".$lang["cartridges"][6]."' title='".$lang["cartridges"][6]."'></td><td><a  class='icon_consol' href=\"cartridges-info-form.php\"><b>".$lang["cartridges"][6]."</b></a>";
         echo "</td></tr></table></div>";
}

function showCartridgeOnglets($target,$withtemplate,$actif){
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
	$next=getNextItem("glpi_cartridges_type",$ID);
	$prev=getPreviousItem("glpi_cartridges_type",$ID);
	$cleantarget=preg_replace("/\?ID=([0-9]+)/","",$target);
		if ($prev>0) echo "<li><a href='$cleantarget?ID=$prev'><img src=\"".$HTMLRel."pics/left.png\" alt='".$lang["buttons"][12]."' title='".$lang["buttons"][12]."'></a></li>";
	if ($next>0) echo "<li><a href='$cleantarget?ID=$next'><img src=\"".$HTMLRel."pics/right.png\" alt='".$lang["buttons"][11]."' title='".$lang["buttons"][11]."'></a></li>";
	}
	echo "</ul></div>";
	
}

/**
* Print search form for cartridges
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
function searchFormCartridge($field="",$phrasetype= "",$contains="",$sort= "",$deleted="",$link="") {
	// Print Search Form
	
	GLOBAL $cfg_install, $cfg_layout, $layout, $lang, $HTMLRel;

	$option["glpi_cartridges_type.ID"]				= $lang["cartridges"][4];
	$option["glpi_cartridges_type.name"]				= $lang["cartridges"][1];
	$option["glpi_cartridges_type.ref"]			= $lang["cartridges"][2];
	$option["glpi_cartridges_type.type"]			= $lang["cartridges"][3];
	$option["glpi_enterprises.name"]			= $lang["cartridges"][8];
	$option["glpi_dropdown_locations.name"]			= $lang["cartridges"][36];	
	$option["resptech.name"]			=$lang["common"][10];
	
	echo "<form method=get action=\"".$cfg_install["root"]."/cartridges/cartridges-search.php\">";
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
* Search and list computers
*
*
* Build the query, make the search and list selected cartridges after a search query.
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
function showCartridgeList($target,$username,$field,$phrasetype,$contains,$sort,$order,$start,$deleted,$link) {

	// Lists CartridgeType

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
			$fields = $db->list_fields("glpi_cartridges_type");
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
				$where .= "glpi_cartridges_type.".$coco . " LIKE '%".$contains[$k]."%'";
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
	
	$query = "SELECT glpi_cartridges_type.*, glpi_enterprises.name FROM glpi_cartridges_type ";
	$query.= " LEFT JOIN glpi_enterprises ON glpi_enterprises.ID = glpi_cartridges_type.FK_glpi_enterprise ";
	$query.= " LEFT JOIN glpi_dropdown_locations ON glpi_dropdown_locations.ID = glpi_cartridges_type.location ";
	$query.= " LEFT JOIN glpi_users as resptech ON (resptech.ID = glpi_cartridges_type.tech_num ) ";
	$query.= " where ";
	if (!empty($where)) $query .= " $where AND ";
	$query .= " glpi_cartridges_type.deleted='$deleted'  ORDER BY $sort $order";

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
			echo "<div align='center'><table class='tab_cadre' width='800'><tr>";

			// Name
			echo "<th>";
			if ($sort=="glpi_cartridges_type.name") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=glpi_cartridges_type.name&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["cartridges"][1]."</a></th>";

			// Ref			
			echo "<th>";
			if ($sort=="glpi_cartridges_type.ref") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=glpi_cartridges_type.ref&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["cartridges"][2]."</a></th>";

			// Type		
			echo "<th>";
			if ($sort=="glpi_cartridges_type.type") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=glpi_cartridges_type.type&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["cartridges"][3]."</a></th>";

			// Manufacturer		
			echo "<th>";
			if ($sort=="glpi_enterprises.name") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=glpi_enterprises.name&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["cartridges"][8]."</a></th>";

			// Location		
			echo "<th>";
			if ($sort=="glpi_dropdown_locations.completename") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=glpi_dropdown_locations.completename&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["cartridges"][36]."</a></th>";
			
			// Cartouches
			echo "<th>".$lang["cartridges"][0]."</th>";
		

			echo "</tr>";

			for ($i=0; $i < $numrows_limit; $i++) {
				$ID = $db->result($result_limit, $i, "ID");

				$ct = new CartridgeType;
				$ct->getfromDB($ID);

				echo "<tr class='tab_bg_2' align='center'>";
				echo "<td><b>";
				echo "<a href=\"".$cfg_install["root"]."/cartridges/cartridges-info-form.php?ID=$ID\">";
				echo $ct->fields["name"]." (".$ct->fields["ID"].")";
				echo "</a></b></td>";
				echo "<td>".$ct->fields["ref"]."</td>";
				echo "<td>".getDropdownName("glpi_dropdown_cartridge_type",$ct->fields["type"])."</td>";
				echo "<td>". getDropdownName("glpi_enterprises",$ct->fields["FK_glpi_enterprise"]) ."</td>";
				echo "<td>". getDropdownName("glpi_dropdown_locations",$ct->fields["location"]) ."</td>";
				
				$highlight="";
				if (getUnusedCartridgesNumber($ct->fields["ID"])<=$ct->fields["alarm"])
				$highlight="class='tab_bg_1_2'";
				
				echo "<td $highlight width='280'>";
					countCartridges($ct->fields["ID"]);
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
			echo "<div align='center'><b>".$lang["cartridges"][7]."</b></div>";
		}
	}
}
*/

/**
* Print the cartridge type form
*
*
* Print général cartridge type form
*
*@param $target filename : where to go when done.
*@param $ID Integer : Id of the cartridge type
*
*
*@return Nothing (display)
*
**/
function showCartridgeTypeForm ($target,$ID) {
	// Show CartridgeType or blank form
	
	GLOBAL $cfg_layout,$cfg_install,$lang;

	$ct = new CartridgeType;
	$ct_spotted = false;
	
	if (empty($ID)) {
		
		if($ct->getEmpty()) $ct_spotted = true;
	} else {
		if($ct->getfromDB($ID)) $ct_spotted = true;
	}		
	
	if ($ct_spotted){
	
	echo "<form method='post' action=\"$target\"><div align='center'>\n";
	
	echo "<table class='tab_cadre' width='800'>\n";
	echo "<tr><th colspan='3'><b>\n";
	if (!$ID) 
		echo $lang["cartridges"][6].":";
	else echo $lang["cartridges"][12]." ID $ID:";
	
	echo "</b></th></tr>\n";

	echo "<tr class='tab_bg_1'><td>".$lang["cartridges"][1].":		</td>\n";
	echo "<td colspan='2'>";
	autocompletionTextField("name","glpi_cartridges_type","name",$ct->fields["name"],25);
	echo "</td></tr>\n";

	echo "<tr class='tab_bg_1'><td>".$lang["cartridges"][2].":		</td>\n";
	echo "<td colspan='2'>";
	autocompletionTextField("ref","glpi_cartridges_type","ref",$ct->fields["ref"],25);	
	echo "</td></tr>\n";

	echo "<tr class='tab_bg_1'><td>".$lang["cartridges"][3].": 	</td><td colspan='2'>\n";
		dropdownValue("glpi_dropdown_cartridge_type","type",$ct->fields["type"]);
	echo "</td></tr>\n";

	echo "<tr class='tab_bg_1'><td>".$lang["cartridges"][8].": 	</td><td colspan='2'>\n";
		dropdownValue("glpi_enterprises","FK_glpi_enterprise",$ct->fields["FK_glpi_enterprise"]);
	echo "</td></tr>\n";

	echo "<tr class='tab_bg_1'><td>".$lang["common"][10].": 	</td><td colspan='2'>\n";
		dropdownUsersID("tech_num", $ct->fields["tech_num"]);
	echo "</td></tr>\n";

	echo "<tr class='tab_bg_1'><td>".$lang["cartridges"][36].": 	</td><td colspan='2'>\n";
		dropdownValue("glpi_dropdown_locations","location",$ct->fields["location"]);
	echo "</td></tr>\n";

	echo "<tr class='tab_bg_1'><td>".$lang["cartridges"][38].":</td><td colspan='2'><select name='alarm'>\n";
	for ($i=0;$i<=100;$i++)
		echo "<option value='$i' ".($i==$ct->fields["alarm"]?" selected ":"").">$i</option>";
	echo "</select></td></tr>\n";
	
	
	echo "<tr class='tab_bg_1'><td valign='top'>\n";
	echo $lang["cartridges"][5].":	</td>";
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
	}
	else {
	echo "<div align='center'><b>".$lang["cartridges"][7]."</b></div>";
	return false;
	}
	return true;
}
/**
* Update some elements of a cartridge type in the database.
*
* Update some elements of a cartridge type in the database.
*
*@param $input array : the _POST vars returned bye the cartrdge form when press update (see showcartridgetype())
*
*
*@return Nothing (call to the class member)
*TODO : error reporting.
**/
function updateCartridgeType($input) {
	// Update CartridgeType in the database

	$sw = new CartridgeType;
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
* Add  a cartridge type in the database.
*
* Add from $input, a cartridge type in the database. return true if it has been added correcly, false elsewhere.
*
*@param $input array : the _POST vars returned bye the cartrdge form when press add (see showcartridgetype())
*
*
*@return boolean true or false.
*
**/
function addCartridgeType($input) {
	
	$sw = new CartridgeType;

	// dump status
	unset($input['add']);

	// fill array for update
	foreach ($input as $key => $val) {
		if ($key[0]!='_'&&(empty($sw->fields[$key]) || $sw->fields[$key] != $input[$key])) {
			$sw->fields[$key] = $input[$key];
		}
	}

	return $sw->addToDB();
}

/**
* Delete a cartridge type in the database.
*
* Delete a cartridge type in the database.
*
*@param $input array : the _POST vars returned bye the cartridge form when press delete (see showcartridgetype())
*@param $force=0 int : how far the cartridge is deleted (moved to trash or purged from db).
*
*@return Nothing (call to the class member)
*TODO : error reporting.
**/
function deleteCartridgeType($input,$force=0) {
	// Delete CartridgeType
	
	$ct = new CartridgeType;
	$ct->deleteFromDB($input["ID"],$force);
} 

/**
* restore some elements of a cartridge type in the database.
*
* restore some elements of a cartridge type witch has been deleted but not purged in the database.
*
*@param $input array : the _POST vars returned bye the cartridge form when press restore (see showcartridgetype())
*
*
*@return Nothing (call to the class member)
*TODO : error reporting.
**/
function restoreCartridgeType($input) {
	// Restore CartridgeType
	
	$ct = new CartridgeType;
	$ct->restoreInDB($input["ID"]);
} 

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
	
	GLOBAL $cfg_layout,$cfg_install,$lang,$HTMLRel;
	
	
	echo "<form method='post'  action=\"".$HTMLRel."cartridges/cartridges-edit.php\">";
	echo "<div align='center'>&nbsp;<table class='tab_cadre' width='90%' cellpadding='2'>";
	echo "<tr><td align='center' class='tab_bg_2'><b>";
	echo "<a href=\"".$cfg_install["root"]."/cartridges/cartridges-edit.php?add=add&amp;tID=$ID\">";
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
*@param $ID integer : Cartridge type identifier.
*@param $show_old=0 boolean : show old cartridges or not. 
*
*@return Nothing (displays)
**/
function showCartridges ($tID,$show_old=0) {

	GLOBAL $cfg_layout, $cfg_install,$lang,$HTMLRel;
	
	$db = new DB;

	$query = "SELECT count(ID) AS COUNT  FROM glpi_cartridges WHERE (FK_glpi_cartridges_type = '$tID')";

	if ($result = $db->query($query)) {
		if ($db->result($result,0,0)!=0) { 
			$total=$db->result($result, 0, "COUNT");
			$unused=getUnusedCartridgesNumber($tID);
			$used=getUsedCartridgesNumber($tID);
			$old=getOldCartridgesNumber($tID);

			echo "<br><div align='center'><table cellpadding='2' class='tab_cadre' width='90%'>";
			if ($show_old==0){
				echo "<tr><th colspan='7'>";
				echo $total;
				echo "&nbsp;".$lang["cartridges"][16]."&nbsp;-&nbsp;$unused&nbsp;".$lang["cartridges"][13]."&nbsp;-&nbsp;$used&nbsp;".$lang["cartridges"][14]."&nbsp;-&nbsp;$old&nbsp;".$lang["cartridges"][15]."</th>";
				echo "<th colspan='2'>";
				echo "&nbsp;</th></tr>";
			}
			else { // Old
				echo "<tr><th colspan='8'>";
				echo $lang["cartridges"][35];
				echo "</th>";
				echo "<th colspan='2'>";
				echo "&nbsp;</th></tr>";
				
			}
			$i=0;
			echo "<tr><th>".$lang["cartridges"][4]."</th><th>".$lang["cartridges"][23]."</th><th>".$lang["cartridges"][24]."</th><th>".$lang["cartridges"][25]."</th><th>".$lang["cartridges"][27]."</th><th>".$lang["cartridges"][26]."</th>";

			if ($show_old==1)
				echo "<th>".$lang["cartridges"][39]."</th>";
			
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

//echo $query;
	$pages=array();
	if ($result = $db->query($query)) {			
	$number=$db->numrows($result);
	while ($data=$db->fetch_array($result)) {
		$date_in=$data["date_in"];
		$date_use=$data["date_use"];
		$date_out=$data["date_out"];
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
			if ($p->getFromDB($data["FK_glpi_printers"]))
				echo "<a href='".$cfg_install["root"]."/printers/printers-info-form.php?ID=".$p->fields["ID"]."'><b>".$p->fields["name"]." (".$p->fields["ID"].")</b></a>";
			else echo "N/A";

		$tmp_dbeg=split("-",$date_in);
		$tmp_dend=split("-",$date_use);

		$stock_time_tmp= mktime(0,0,0,$tmp_dend[1],$tmp_dend[2],$tmp_dend[0]) 
					  - mktime(0,0,0,$tmp_dbeg[1],$tmp_dbeg[2],$tmp_dbeg[0]);		
		$stock_time+=$stock_time_tmp;

		}
		
		
		echo "</td><td align='center'>";
		echo $date_out;		

		if ($show_old!=0){
			$tmp_dbeg=split("-",$date_use);
			$tmp_dend=split("-",$date_out);

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
		if (!is_null($date_use))
		echo "&nbsp;&nbsp;&nbsp;<a href='".$cfg_install["root"]."/cartridges/cartridges-edit.php?restore=restore&amp;ID=".$data["ID"]."&amp;tID=$tID'>".$lang["cartridges"][43]."</a>";		
		else
		echo "&nbsp;";

		echo "</td>";


		echo "<td align='center'>";
		
		echo "&nbsp;&nbsp;&nbsp;<a href='".$cfg_install["root"]."/cartridges/cartridges-edit.php?delete=delete&amp;ID=".$data["ID"]."&amp;tID=$tID'>".$lang["cartridges"][31]."</a>";
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
* Add  a cartridge in the database.
*
* Add a new cartridge that type is identified by $ID
*
*@param $tID : cartridge type identifier
*
*
*@return boolean true or false.
*
**/
function addCartridge($tID) {
	$c = new Cartridge;
	
	$input["FK_glpi_cartridges_type"]=$tID;
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
	
	if ($ic->getFromDB(CARTRIDGE_TYPE,$c->fields["FK_glpi_cartridges_type"])){
		unset($ic->fields["ID"]);
		$ic->fields["FK_device"]=$newID;
		$ic->fields["device_type"]=CARTRIDGE_ITEM_TYPE;
		$ic->addToDB();
	}
	return $newID;

}
/**
* delete a cartridge in the database.
*
* delete a cartridge that is identified by $ID
*
*@param $tID : cartridge type identifier
*
*
*@return nothing
*TODO error reporting
*
**/
function deleteCartridge($ID) {
	// Delete cartridge
	
	$lic = new Cartridge;
	$lic->deleteFromDB($ID);
	
} 
/**
* Link a cartridge to a printer.
*
* Link the first unused cartridge of type $Tid to the printer $pID
*
*@param $tID : cartridge type identifier
*@param $pID : printer identifier
*
*@return nothing
*TODO error reporting
*
**/
function installCartridge($pID,$tID) {
	global $lang;
	$db = new DB;
	// Get first unused cartridge
	$query = "SELECT ID FROM glpi_cartridges WHERE FK_glpi_cartridges_type = '$tID' AND date_use IS NULL";
	$result = $db->query($query);
	if ($db->numrows($result)>0){
	// Mise a jour cartouche en prenant garde aux insertion multiples	
	$query = "UPDATE glpi_cartridges SET date_use = '".date("Y-m-d")."', FK_glpi_printers = '$pID' WHERE ID='".$db->result($result,0,0)."' AND date_use IS NULL";
	if ($result = $db->query($query)) {
		return true;
	} else {
		return false;
	}
	} else {
		 $_SESSION["MESSAGE_AFTER_REDIRECT"]=$lang["cartridges"][34];
		return false;
		
	}

}

/**
* UnLink a cartridge linked to a printer
*
* UnLink the cartridge identified by $ID
*
*@param $ID : cartridge identifier
*
*@return boolean
*
**/
function uninstallCartridge($ID) {

	$db = new DB;
	$query = "UPDATE glpi_cartridges SET date_out = '".date("Y-m-d")."' WHERE ID='$ID'";
//	echo $query;
	if ($result = $db->query($query)) {
		return true;
	} else {
		return false;
	}
}


function restoreCartridge($ID) {

	$db = new DB;
	$query = "UPDATE glpi_cartridges SET date_out = NULL, date_use = NULL , FK_glpi_printers= NULL WHERE ID='$ID'";
	if ($result = $db->query($query)) {
		return true;
	} else {
		return false;
	}
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
	GLOBAL $cfg_layout,$cfg_install, $lang;

    $db = new DB;
	$query = "SELECT glpi_type_printers.name as type, glpi_cartridges_assoc.ID as ID FROM glpi_cartridges_assoc, glpi_type_printers WHERE glpi_cartridges_assoc.FK_glpi_type_printer=glpi_type_printers.ID AND glpi_cartridges_assoc.FK_glpi_cartridges_type = '$instID' order by glpi_type_printers.name";
	
	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;
	
    echo "<form method='post' action=\"".$cfg_install["root"]."/cartridges/cartridges-info-form.php\">";
	echo "<br><br><div align='center'><table class='tab_cadre' width='90%'>";
	echo "<tr><th colspan='3'>".$lang["cartridges"][32].":</th></tr>";
	echo "<tr><th>".$lang['cartridges'][4]."</th><th>".$lang["printers"][9]."</th><th>&nbsp;</th></tr>";

	while ($i < $number) {
		$ID=$db->result($result, $i, "ID");
		$type=$db->result($result, $i, "type");
	echo "<tr class='tab_bg_1'><td align='center'>$ID</td>";
	echo "<td align='center'>$type</td>";
	echo "<td align='center' class='tab_bg_2'><a href='".$_SERVER["PHP_SELF"]."?deletetype=deletetype&amp;ID=$ID'><b>".$lang["buttons"][6]."</b></a></td></tr>";
	$i++;
	}
	echo "<tr class='tab_bg_1'><td>&nbsp;</td><td align='center'>";
	echo "<div class='software-instal'><input type='hidden' name='tID' value='$instID'>";
		dropdown("glpi_type_printers","type");
	echo "</div></td><td align='center' class='tab_bg_2'>";
	echo "<input type='submit' name='addtype' value=\"".$lang["buttons"][8]."\" class='submit'>";
	echo "</td></tr>";
	
	echo "</table></div></form>"    ;
	
}

/**
* Add a compatible printer type for a cartridge type
*
* Add the compatible printer $type type for the cartridge type $tID
*
*@param $tID integer: cartridge type identifier
*@param $type integer: printer type identifier
*@return nothing ()
*TODO error_reporting
*
**/
function addCompatibleType($tID,$type){

if ($tID>0&&$type>0){
	$db = new DB;
	$query="INSERT INTO glpi_cartridges_assoc (FK_glpi_cartridges_type,FK_glpi_type_printer ) VALUES ('$tID','$type');";
	$result = $db->query($query);
}
}

/**
* delete a compatible printer associated to a cartridge
*
* Delete a compatible printer associated to a cartridge with assoc identifier $ID
*
*@param $ID integer: glpi_cartridge_assoc identifier.
*
*@return nothing ()
*TODO error_reporting
*
**/
function deleteCompatibleType($ID){

$db = new DB;
$query="DELETE FROM glpi_cartridges_assoc WHERE ID= '$ID';";
$result = $db->query($query);
}

/**
* Show installed cartridges
*
* Show installed cartridge for the printer type $instID
*
*@param $instID integer: printer type identifier.
*
*@return nothing (display)
*
**/
function showCartridgeInstalled($instID,$old=0) {

	GLOBAL $cfg_layout,$cfg_install, $lang,$HTMLRel;

    $db = new DB;
	
	$query = "SELECT glpi_cartridges_type.ID as tID, glpi_cartridges_type.deleted as deleted, glpi_cartridges_type.ref as ref, glpi_cartridges_type.name as type, glpi_cartridges.ID as ID, glpi_cartridges.pages as pages, glpi_cartridges.date_use as date_use, glpi_cartridges.date_out as date_out, glpi_cartridges.date_in as date_in";
	if ($old==0)
	$query.= " FROM glpi_cartridges, glpi_cartridges_type WHERE glpi_cartridges.date_out IS NULL AND glpi_cartridges.FK_glpi_printers= '$instID' AND glpi_cartridges.FK_glpi_cartridges_type  = glpi_cartridges_type.ID ORDER BY glpi_cartridges.date_out ASC, glpi_cartridges.date_use DESC, glpi_cartridges.date_in";
	else 
	$query.= " FROM glpi_cartridges, glpi_cartridges_type WHERE glpi_cartridges.date_out IS NOT NULL AND glpi_cartridges.FK_glpi_printers= '$instID' AND glpi_cartridges.FK_glpi_cartridges_type  = glpi_cartridges_type.ID ORDER BY glpi_cartridges.date_out ASC, glpi_cartridges.date_use DESC, glpi_cartridges.date_in";


//	echo $query;	
	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;
	$p=new Printer;
	$p->getFromDB($instID);
	$pages=$p->fields['initial_pages'];

	echo "<br><br><center><table class='tab_cadre' width='90%'>";
	if ($old==0)
	echo "<tr><th colspan='7'>".$lang["cartridges"][33].":</th></tr>";
	else echo "<tr><th colspan='8'>".$lang["cartridges"][35].":</th></tr>";
	echo "<tr><th>".$lang["cartridges"][4]."</th><th>".$lang["cartridges"][12]."</th><th>".$lang["cartridges"][23]."</th><th>".$lang["cartridges"][24]."</th><th>".$lang["cartridges"][25]."</th><th>".$lang["cartridges"][26]."</th>";
	if ($old!=0)
	echo "<th>".$lang["cartridges"][39]."</th>";
	
	echo "<th>&nbsp;</th></tr>";

	$stock_time=0;
	$use_time=0;	
	$pages_printed=0;
	$nb_pages_printed=0;
	$ci=new CommonItem();
	while ($data=$db->fetch_array($result)) {
		$date_in=$data["date_in"];
		$date_use=$data["date_use"];
		$date_out=$data["date_out"];
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
			
			echo "<form method='post' action=\"".$cfg_install["root"]."/cartridges/cartridges-edit.php\">";
			echo "<input type='hidden' name='cID' value='".$data['ID']."'>";
			echo "<input type='text' name='pages' value='".$data['pages']."' size='10'>";
			echo "<input type='image' name='update_pages' value='update_pages' src='".$HTMLRel."pics/actualiser.png' class='calendrier'>";
			echo "</form>";
			
			if ($pages<$data['pages']){
				$pages_printed+=$data['pages']-$pages;
				$nb_pages_printed++;
				echo ($data['pages']-$pages)." ".$lang["printers"][31];
				$pages=$data['pages'];
			}
			echo "</td><td align='center'>";
		}
		if (is_null($date_out))
		echo "&nbsp;&nbsp;&nbsp;<a href='".$cfg_install["root"]."/cartridges/cartridges-edit.php?uninstall=uninstall&amp;ID=".$data["ID"]."'>".$lang["cartridges"][29]."</a>";
		else echo "&nbsp;&nbsp;&nbsp;<a href='".$cfg_install["root"]."/cartridges/cartridges-edit.php?delete=delete&amp;ID=".$data["ID"]."'>".$lang["cartridges"][31]."</a>";
		echo "</td></tr>";
		
	}	
	if ($old==0){
		echo "<tr class='tab_bg_1'><td>&nbsp;</td><td align='center'>";
		echo "<form method='post' action=\"".$cfg_install["root"]."/cartridges/cartridges-edit.php\">";
	
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
        echo "</table></center>";
	

}

function updateCartridgePages($ID,$pages){

$db=new DB;
$query="UPDATE glpi_cartridges SET pages='$pages' WHERE ID='$ID'";
$db->query($query);

}

/**
* Print a select with compatible cartridge
*
* Print a select that contains compatibles cartridge for a printer type $pID
*
*@param $pID integer: printer type identifier.
*
*@return nothing (display)
*
**/
function dropdownCompatibleCartridges($pID) {
	
	global $lang;
	
	$db = new DB;
	$p=new Printer;
	$p->getFromDB($pID);
	
	$query = "SELECT glpi_cartridges_type.ref as ref, glpi_cartridges_type.name as name, glpi_cartridges_type.ID as tID FROM glpi_cartridges_type, glpi_cartridges_assoc WHERE glpi_cartridges_type.ID = glpi_cartridges_assoc.FK_glpi_cartridges_type AND glpi_cartridges_assoc.FK_glpi_type_printer = '".$p->fields["type"]."' order by glpi_cartridges_type.name, glpi_cartridges_type.ref";
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
*
*@return nothing (display)
*
**/
function countCartridges($tID,$alarm) {
	
	GLOBAL $cfg_layout, $lang;
	
	$db = new DB;

	// Get total
	$total = getCartridgesNumber($tID);

	if ($total!=0) {
	$unused=getUnusedCartridgesNumber($tID);
	$used=getUsedCartridgesNumber($tID);
	$old=getOldCartridgesNumber($tID);

	$highlight="";
	if ($unused<=$alarm)
		$highlight="class='tab_bg_1_2'";

	echo "<div $highlight><b>".$lang["cartridges"][30].":&nbsp;$total</b>&nbsp;&nbsp;&nbsp;".$lang["cartridges"][13].": $unused&nbsp;&nbsp;&nbsp;".$lang["cartridges"][14].": $used&nbsp;&nbsp;&nbsp;".$lang["cartridges"][15].": $old</div>";			

	} else {
			echo "<div class='tab_bg_1_2'><i>".$lang["cartridges"][9]."</i></div>";
	}
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
	$db=new DB;
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
	$db=new DB;
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
	$db=new DB;
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
	$db=new DB;
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
$db=new DB;
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
$db=new DB;
$query = "SELECT ID FROM glpi_cartridges WHERE ( ID= '$cID' AND date_use IS NOT NULL AND date_out IS NULL)";
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
$db=new DB;
$query = "SELECT ID FROM glpi_cartridges WHERE ( ID= '$cID' AND date_out IS NOT NULL)";
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

?>
