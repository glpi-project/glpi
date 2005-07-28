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
// FUNCTIONS links

/**
* Print a good title for links pages
*
*
*
*
*@return nothing (diplays)
*
**/
function titleLinks(){
                GLOBAL  $lang,$HTMLRel;
                echo "<div align='center'><table border='0'><tr><td>";
                echo "<img src=\"".$HTMLRel."pics/links.png\" alt='".$lang["links"][2]."' title='".$lang["links"][2]."'></td><td><a  class='icon_consol' href=\"links-info-form.php?new=1\"><b>".$lang["links"][2]."</b></a>";
                echo "</td></tr></table></div>";
}

function showLinkOnglets($target,$withtemplate,$actif){
	global $lang, $HTMLRel;

	$template="";
	if(!empty($withtemplate)){
		$template="&withtemplate=$withtemplate";
	}
	
	echo "<div id='barre_onglets'><ul id='onglet'>";
	echo "<li "; if ($actif=="1"){ echo "class='actif'";} echo  "><a href='$target&onglet=1$template'>".$lang["title"][26]."</a></li>";
	
	
	echo "<li class='invisible'>&nbsp;</li>";
	
	if (empty($withtemplate)&&preg_match("/\?ID=([0-9]+)/",$target,$ereg)){
	$ID=$ereg[1];
	$next=getNextItem("glpi_links",$ID);
	$prev=getPreviousItem("glpi_links",$ID);
	$cleantarget=preg_replace("/\?ID=([0-9]+)/","",$target);
	if ($prev>0) echo "<li><a href='$cleantarget?ID=$prev'><img src=\"".$HTMLRel."pics/left.png\" alt='".$lang["buttons"][12]."' title='".$lang["buttons"][12]."'></a></li>";
	if ($next>0) echo "<li><a href='$cleantarget?ID=$next'><img src=\"".$HTMLRel."pics/right.png\" alt='".$lang["buttons"][11]."' title='".$lang["buttons"][11]."'></a></li>";
	}

	echo "</ul></div>";
	
}


/**
* Print search form for links
*
* 
*
*@param $field='' field selected in the search form
*@param $contains='' the search string
*@param $sort='' the "sort by" field value
*@param $phrasetype=''  not used (to be deleted)
*
*@return nothing (diplays)
*
**/
function searchFormLink($field="",$phrasetype= "",$contains="",$sort= "") {
	// Print Search Form
	
	GLOBAL $cfg_install, $cfg_layout, $layout, $lang;

	$option["glpi_links.name"]				= $lang["links"][0];
	$option["glpi_links.ID"]				= $lang["links"][1];

	echo "<form method='get' action=\"".$cfg_install["root"]."/links/links-search.php\">";
	echo "<div align='center'><table  width='750' class='tab_cadre'>";
	echo "<tr><th colspan='2'><b>".$lang["search"][0].":</b></th></tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'>";
	echo "<input type='text' size='15' name=\"contains\" value=\"". $contains ."\" >";
	echo "&nbsp;";
	echo $lang["search"][10]."&nbsp;<select name=\"field\" size='1'>";
        echo "<option value='all' ";
	if($field == "all") echo "selected";
	echo ">".$lang["search"][7]."</option>";
        reset($option);
	foreach ($option as $key => $val) {
		echo "<option value=\"".$key."\""; 
		if($key == $field) echo "selected";
		echo ">". substr($val, 0, 18) ."</option>\n";
	}
	echo "</select>&nbsp;";
	
	echo $lang["search"][4];
	echo "&nbsp;<select name='sort' size='1'>";
	reset($option);
	foreach ($option as $key => $val) {
		echo "<option value=\"".$key."\"";
		if($key == $sort) echo "selected";
		echo ">".$val."</option>\n";
	}
	echo "</select> ";
	echo "</td><td width='80' align='center' class='tab_bg_2'>";
	echo "<input type='submit' value=\"".$lang["buttons"][0]."\" class='submit'>";
	echo "</td></tr></table></div></form>";
}

/**
* Search and list links
*
*
* Build the query, make the search and list links after a search.
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
*
*@return Nothing (display)
*
**/
function showLinkList($target,$username,$field,$phrasetype,$contains,$sort,$order,$start) {

	// Lists links

	GLOBAL $cfg_install, $cfg_layout, $cfg_features, $lang, $HTMLRel;

	$db = new DB;

	// Build query
	if($field=="all") {
		$where = " (";
		$fields = $db->list_fields("glpi_links");
		$columns = $db->num_fields($fields);
		
		for ($i = 0; $i < $columns; $i++) {
			if($i != 0) {
				$where .= " OR ";
			}
			$coco = $db->field_name($fields, $i);

			$where .= "glpi_links.".$coco . " LIKE '%".$contains."%'";
		}
		$where .= ")";
	}
	else {
		if ($phrasetype == "contains") {
			$where = "($field LIKE '%".$contains."%')";
		}
		else {
			$where = "($field LIKE '".$contains."')";
		}
	}

	if (!$start) {
		$start = 0;
	}
	if (!$order) {
		$order = "ASC";
	}
	$query = "select * from glpi_links ";
	$query .= "where $where ORDER BY $sort $order";
	// Get it from database	
	if ($result = $db->query($query)) {
		$numrows =  $db->numrows($result);

		// Limit the result, if no limit applies, use prior result
		if ($numrows > $cfg_features["list_limit"]) {
			$query_limit = $query ." LIMIT $start,".$cfg_features["list_limit"]." ";
			$result_limit = $db->query($query_limit);
			$numrows_limit = $db->numrows($result_limit);
		} else {
			$numrows_limit = $numrows;
			$result_limit = $result;
		}
		

		if ($numrows_limit>0) {
			// Pager
			$parameters="field=$field&phrasetype=$phrasetype&contains=$contains&sort=$sort&order=$order";
			printPager($start,$numrows,$target,$parameters);

			// Produce headline
			echo "<div align='center'><table  class='tab_cadre' width='750'><tr>";
			
			// ID
			echo "<th>";
			if ($sort=="glpi_links.ID") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_links.name&order=".($order=="ASC"?"DESC":"ASC")."&start=$start\">";
			echo $lang["links"][0]."</a></th>";
			
			// Name
			echo "<th>";
			if ($sort=="glpi_links.name") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_links.name&order=".($order=="ASC"?"DESC":"ASC")."&start=$start\">";
			echo $lang["links"][1]."</a></th>";


			echo "</tr>";

			for ($i=0; $i < $numrows_limit; $i++) {
				$ID = $db->result($result_limit, $i, "ID");
				$con = new Link;
				$con->getfromDB($ID);
				echo "<tr class='tab_bg_2'>";
				echo "<td align='center'><b>";
				echo "<a href=\"".$cfg_install["root"]."/links/links-info-form.php?ID=$ID\">";
				echo $ID;
				echo "</a></b></td>";

				echo "<td align='center'><b>";
				echo "<a href=\"".$cfg_install["root"]."/links/links-info-form.php?ID=$ID\">";
				echo $con->fields["name"];
				echo "</a></b></td>";
				echo "</tr>";
			}

			// Close Table
			echo "</table></div>";

			// Pager
			echo "<br>";
//			$parameters="field=$field&phrasetype=$phrasetype&contains=$contains&sort=$sort&order=$order";
			printPager($start,$numrows,$target,$parameters);

		} else {
			echo "<div align='center'><b>".$lang["financial"][38]."</b></div>";
		}
	}
}

/**
* Print the link form
*
*
* Print général link form
*
*@param $target filename : where to go when done.
*@param $ID Integer : Id of the link to print
*
*
*@return Nothing (display)
*
**/
function showLinkForm ($target,$ID) {

	GLOBAL $cfg_install, $cfg_layout, $lang,$HTMLRel;

	$con = new Link;

	echo "<form method='post' name=form action=\"$target\"><div align='center'>";
	
	echo "<table class='tab_cadre' cellpadding='2' width='700'>";
	echo "<tr><th colspan='2'><b>";
	if (empty($ID)) {
		echo $lang["links"][3].":";
		$con->getEmpty();
	} else {
		$con->getfromDB($ID);
		echo $lang["links"][1]." ID $ID:";
	}		
	echo "</b></th></tr>";
	
	echo "<tr class='tab_bg_1'><td>".$lang["links"][6].":	</td>";
	echo "<td>[ID], [NAME], [LOCATION], [LOCATIONID], [IP], [MAC]</td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["links"][1].":	</td>";
	echo "<td><input type='text' name='name' value=\"".$con->fields["name"]."\" size='30'></td>";
	echo "</tr>";

	if ($ID=="") {

		echo "<tr>";
		echo "<td class='tab_bg_2' valign='top' colspan='2'>";
		echo "<div align='center'><input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'></div>";
		echo "</td>";
		echo "</tr>";

		echo "</table></div></form>";

	} else {

		echo "<tr>";
		echo "<td class='tab_bg_2' valign='top'>";
		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
		echo "<div align='center'><input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit' ></div>";
		echo "</td>\n\n";
		echo "<td class='tab_bg_2' valign='top'>\n";
		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
		echo "<div align='center'><input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit' ></div>";
		echo "</td>";
		echo "</tr>";

		echo "</table></div></form>";

	}
}

/**
* Update some elements of a link in the database
*
* Update some elements of a link in the database.
*
*@param $input array : the _POST vars returned bye the link form when press update (see showlinkform())
*
*
*@return Nothing (call to the class member)
*
**/
function updateLink($input) {
	// Update a Link in the database

	$con = new Link;
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
	if(isset($updates))
		$con->updateInDB($updates);

}

/**
* Add a link in the database.
*
* Add a link in the database with all it's items.
*
*@param $input array : the _POST vars returned bye the link form when press add(see showlinkform())
*
*
*@return Nothing (call to classes members)
*
**/
function addLink($input) {
	// Add Link, nasty hack until we get PHP4-array-functions

	$con = new Link;
	unset($input['referer']);
	// dump status
	$null = array_pop($input);
	
	// fill array for udpate
	foreach ($input as $key => $val) {
		if (!isset($con->fields[$key]) || $con->fields[$key] != $input[$key]) {
			$con->fields[$key] = $input[$key];
		}
	}
	
	return $con->addToDB();

}
/**
* Delete a link in the database.
*
* Delete a link in the database.
*
*@param $input array : the _POST vars returned bye the link form when press delete(see showlinkform())
*
*
*@return Nothing ()
*
**/
function deleteLink($input) {
	// Delete Link
	
	$con = new Link;
	$con->deleteFromDB($input["ID"]);
	
} 

/**
* Print the HTML array for device on link
*
* Print the HTML array for device on link for link $instID
*
*@param $instID array : Link identifier.
*
*@return Nothing (display)
*
**/
function showLinkDevice($instID) {
	GLOBAL $cfg_layout,$cfg_install, $lang,$HTMLRel;

    $db = new DB;
	$query = "SELECT * from glpi_links_device WHERE FK_links='$instID' ORDER BY device_type";
	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;
	
    echo "<form method='post' action=\"".$cfg_install["root"]."/links/links-info-form.php\">";
	echo "<br><br><div align='center'><table class='tab_cadre' width='90%'>";
	echo "<tr><th colspan='2'>".$lang["links"][4].":</th></tr>";
	echo "<tr><th>".$lang['links'][5]."</th>";
	echo "<th>&nbsp;</th></tr>";

	while ($i < $number) {
		$ID=$db->result($result, $i, "ID");
		$device_type=$db->result($result, $i, "device_type");
	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'>".getDeviceTypeName($device_type)."</td>";
	echo "<td align='center' class='tab_bg_2'><a href='".$_SERVER["PHP_SELF"]."?deletedevice=deletedevice&ID=$ID'><b>".$lang["buttons"][6]."</b></a></td></tr>";
	$i++;
	}
	echo "<tr class='tab_bg_1'><td>&nbsp;</td><td align='center'>";
	echo "<div class='software-instal'><input type='hidden' name='lID' value='$instID'>";
	dropdownDeviceType("device_type",0);
	
			
	
	echo "&nbsp;&nbsp;<input type='submit' name='adddevice' value=\"".$lang["buttons"][8]."\" class='submit'>";
	echo "</div>";
	echo "</td>";
	
	echo "</tr>";
	
	echo "</table></div></form>"    ;
	
}

function deleteLinkDevice($ID){

$db = new DB;
$query="DELETE FROM glpi_links_device WHERE ID= '$ID';";
$result = $db->query($query);
}

function addLinkDevice($tID,$lID){
if ($tID>0&&$lID>0){
	$db = new DB;
	$query="INSERT INTO glpi_links_device (device_type,FK_links ) VALUES ('$tID','$lID');";
	$result = $db->query($query);
}
}

function showLinkOnDevice($type,$ID){
global $lang;
	$db=new DB;
	$query="SELECT glpi_links.name as name from glpi_links INNER JOIN glpi_links_device ON glpi_links.ID= glpi_links_device.FK_links WHERE glpi_links_device.device_type='$type' ORDER BY glpi_links.name";
	$result=$db->query($query);

	echo "<br>";
	
	$ci=new CommonItem;
	if ($db->numrows($result)>0){
		echo "<div align='center'><table class='tab_cadre'><tr><th>".$lang["title"][33]."</th></tr>";

		while ($data=$db->fetch_array($result)){
		$link=$data["name"];
		$ci->getFromDB($type,$ID);
		if (ereg("\[NAME\]",$link)){
			$link=ereg_replace("\[NAME\]",$ci->getName(),$link);
		}
		if (ereg("\[ID\]",$link)){
			$link=ereg_replace("\[ID\]",$ID,$link);
		}
		if (ereg("\[LOCATIONID\]",$link)){
			$link=ereg_replace("\[LOCATIONID\]",$ci->obj->fields["location"],$link);
		}
		if (ereg("\[LOCATION\]",$link)){
			$link=ereg_replace("\[LOCATION\]",getDropdownName("glpi_dropdown_locations",$ci->obj->fields["location"]),$link);
		}
		$ipmac=array();
		$i=0;
		if (ereg("\[IP\]",$link)||ereg("\[MAC\]",$link)){
			$query2 = "SELECT ifaddr,ifmac FROM glpi_networking_ports WHERE (on_device = $ID AND device_type = $type) ORDER BY logical_number";
			$result2=$db->query($query2);
			if ($db->numrows($result2)>0)
			while ($data2=$db->fetch_array($result2)){
			$ipmac[$i]['ifaddr']=$data2["ifaddr"];
			$ipmac[$i]['ifmac']=$data2["ifmac"];
			$i++;
			}
		}

		if (ereg("\[IP\]",$link)||ereg("\[MAC\]",$link)){
		if (count($ipmac)>0){
			foreach ($ipmac as $key => $val){
				$tmplink=$link;
				$tmplink=ereg_replace("\[IP\]",$val['ifaddr'],$tmplink);
				$tmplink=ereg_replace("\[MAC\]",$val['ifmac'],$tmplink);
				echo "<tr class='tab_bg_2'><td><a href='$tmplink'>$tmplink</a></td></tr>";
			}
		}} else 
		echo "<tr class='tab_bg_2'><td><a href='$link'>$link</a></td></tr>";
		
	

		}
		echo "</table></div>";
	} else echo "<div align='center'><b>".$lang["links"][7]."</b></div>";

}

?>
