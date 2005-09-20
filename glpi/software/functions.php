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
 
// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");


function titleSoftware(){

         GLOBAL  $lang,$HTMLRel;
         
         echo "<div align='center'><table border='0'><tr><td>";
         echo "<img src=\"".$HTMLRel."pics/logiciels.png\" alt='".$lang["software"][0]."' title='".$lang["software"][0]."'></td>\n";
         echo "<td><a class='icon_consol' href=\"software-add-select.php\"><strong>".$lang["software"][0]."</strong></a>\n";
                echo "</td>";
                echo "<td><a class='icon_consol'  href='".$HTMLRel."setup/setup-templates.php?type=".SOFTWARE_TYPE."'>".$lang["common"][8]."</a></td>";
                echo "</tr></table></div>";

}

function showSoftwareOnglets($target,$withtemplate,$actif){
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
	echo "<li "; if ($actif=="6") {echo "class='actif'";} echo "><a href='$target&amp;onglet=6$template'>".$lang["title"][28]."</a></li>";
	echo "<li "; if ($actif=="7") {echo "class='actif'";} echo "><a href='$target&amp;onglet=7$template'>".$lang["title"][34]."</a></li>";
	echo "<li class='invisible'>&nbsp;</li>";
	echo "<li "; if ($actif=="-1") {echo "class='actif'";} echo "><a href='$target&amp;onglet=-1$template'>".$lang["title"][29]."</a></li>";
	}
	
	echo "<li class='invisible'>&nbsp;</li>";
	
	if (empty($withtemplate)&&preg_match("/\?ID=([0-9]+)/",$target,$ereg)){
	$ID=$ereg[1];
	$next=getNextItem("glpi_software",$ID);
	$prev=getPreviousItem("glpi_software",$ID);
	$cleantarget=preg_replace("/\?ID=([0-9]+)/","",$target);
		if ($prev>0) echo "<li><a href='$cleantarget?ID=$prev'><img src=\"".$HTMLRel."pics/left.png\" alt='".$lang["buttons"][12]."' title='".$lang["buttons"][12]."'></a></li>";
	if ($next>0) echo "<li><a href='$cleantarget?ID=$next'><img src=\"".$HTMLRel."pics/right.png\" alt='".$lang["buttons"][11]."' title='".$lang["buttons"][11]."'></a></li>";
	}
	echo "</ul></div>";
	
}


function searchFormSoftware($field="",$phrasetype= "",$contains="",$sort= "",$deleted="",$link="") {
	global $HTMLRel;
	// Print Search Form
	
	GLOBAL $cfg_install, $cfg_layout, $layout, $lang;

	$option["glpi_software.ID"]				= $lang["software"][1];
	$option["glpi_software.name"]				= $lang["software"][2];
	$option["glpi_dropdown_os.name"]			= $lang["software"][3];
	$option["glpi_dropdown_locations.name"]			= $lang["software"][4];
	$option["glpi_software.version"]			= $lang["software"][5];
	$option["glpi_software.comments"]			= $lang["software"][6];
	$option["glpi_enterprises.name"]			= $lang["common"][5];
	$option["resptech.name"]			=$lang["common"][10];
	$option["glpi_licenses.serial"]			=$lang["software"][11];
	
	$option=addInfocomOptionFieldsToResearch($option);
	$option=addContractOptionFieldsToResearch($option);

	echo "<form method=get action=\"".$cfg_install["root"]."/software/software-search.php\">";
	echo "<center><table class='tab_cadre' width='850'>";
	echo "<tr><th colspan='4'><strong>".$lang["search"][0].":</strong></th></tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'>";
	
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
	echo "</td></tr></table></center></form>";
}

function showSoftwareList($target,$username,$field,$phrasetype,$contains,$sort,$order,$start,$deleted,$link) {

	// Lists Software

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
			$fields = $db->list_fields("glpi_software");
			$columns = $db->num_fields($fields);
			
			for ($i = 0; $i < $columns; $i++) {
				if($i != 0) {
					$where .= " OR ";
				}
				$coco = $db->field_name($fields, $i);
					if($coco == "platform") {
					$where .= " glpi_dropdown_os.name LIKE '%".$contains[$k]."%'";
				}
				elseif($coco == "location") {
					$where .= getRealSearchForTreeItem("glpi_dropdown_locations",$contains[$k]);
				}
				elseif($coco == "FK_glpi_enterprise") {
					$where .= "glpi_enterprises.name LIKE '%".$contains[$k]."%'";
				}
				else if ($coco=="tech_num"){
					$where .= " resptech.name LIKE '%".$contains[$k]."%'";
				} 
				
				else {
	   				$where .= "glpi_software.".$coco . " LIKE '%".$contains[$k]."%'";
				}
			}
			$where.=" OR glpi_enterprises.name LIKE '%".$contains[$k]."%'";
			$where .= " OR resptech.name LIKE '%".$contains[$k]."%'";
			$where .= " OR glpi_licenses.serial LIKE '%".$contains[$k]."%'";
			$where .= getInfocomSearchToViewAllRequest($contains[$k]);
			$where .= getContractSearchToViewAllRequest($contains[$k]);
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
	
	$query = "SELECT DISTINCT glpi_software.ID as ID FROM glpi_software ";
	$query .= "LEFT JOIN glpi_dropdown_os on glpi_software.platform=glpi_dropdown_os.ID ";
	$query.= " LEFT JOIN glpi_dropdown_locations on glpi_software.location=glpi_dropdown_locations.ID ";
	$query.= " LEFT JOIN glpi_enterprises ON (glpi_enterprises.ID = glpi_software.FK_glpi_enterprise ) ";
	$query.= " LEFT JOIN glpi_users as resptech ON (resptech.ID = glpi_software.tech_num ) ";
	$query.= " LEFT JOIN glpi_licenses ON (glpi_licenses.sID = glpi_software.ID ) ";
	$query.= getInfocomSearchToRequest("glpi_software",SOFTWARE_TYPE);
	$query.= getContractSearchToRequest("glpi_software",SOFTWARE_TYPE);
	$query.= " where ";
	if (!empty($where)) $query .= " $where AND ";
	$query .= " glpi_software.deleted='$deleted' AND glpi_software.is_template = '0'  ORDER BY $sort $order";

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
			if ($sort=="glpi_software.name") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=glpi_software.name&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["software"][2]."</a></th>";

			
			// Manufacturer		
			echo "<th>";
			if ($sort=="glpi_enterprises.name") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=glpi_enterprises.name&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["common"][5]."</a></th>";
			
			// Version			
			echo "<th>";
			if ($sort=="glpi_software.version") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=glpi_software.version&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["software"][5]."</a></th>";

			// Platform		
			echo "<th>";
			if ($sort=="glpi_dropdown_os.name") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?sort=glpi_dropdown_os.name&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start".getMultiSearchItemForLink("field",$field).getMultiSearchItemForLink("link",$link).getMultiSearchItemForLink("contains",$contains)."\">";
			echo $lang["software"][3]."</a></th>";

			// Licenses
			echo "<th>".$lang["software"][11]."</th>";
		
			echo "</tr>";

			for ($i=0; $i < $numrows_limit; $i++) {
				$ID = $db->result($result_limit, $i, "ID");

				$sw = new Software;
				$sw->getfromDB($ID);

				echo "<tr class='tab_bg_2'>";
				echo "<td align='center'><strong>";
				echo "<a href=\"".$cfg_install["root"]."/software/software-info-form.php?ID=$ID\">";
				echo $sw->fields["name"]." (".$sw->fields["ID"].")";
				echo "</a></strong></td>";
				echo "<td>". getDropdownName("glpi_enterprises",$sw->fields["FK_glpi_enterprise"]) ."</td>";
				echo "<td align='center'>".$sw->fields["version"]."</td>";
				echo "<td align='center'>". getDropdownName("glpi_dropdown_os",$sw->fields["platform"]) ."</td>";
				echo "<td>";
					countInstallations($sw->fields["ID"]);
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
			echo "<div align='center'><strong>".$lang["software"][22]."</strong></div>";
			//echo "<hr noshade>";
			//searchFormSoftware();
		}
	}
}



function showSoftwareForm ($target,$ID,$search_software="",$withtemplate='') {
	// Show Software or blank form
	
	GLOBAL $cfg_layout,$cfg_install,$lang;
	
	
	$sw = new Software;

	$sw_spotted = false;

	if(empty($ID) && $withtemplate == 1) {
		if($sw->getEmpty()) $sw_spotted = true;
	} else {
		if($sw->getfromDB($ID)) $sw_spotted = true;
	}

	if($sw_spotted) {
		if(!empty($withtemplate) && $withtemplate == 2) {
			$template = "newcomp";
			$datestring = $lang["computers"][14].": ";
			$date = date("Y-m-d H:i:s");
		} elseif(!empty($withtemplate) && $withtemplate == 1) { 
			$template = "newtemplate";
			$datestring = $lang["computers"][14].": ";
			$date = date("Y-m-d H:i:s");
		} else {
			$datestring = $lang["computers"][11]." : ";
			$date = $sw->fields["date_mod"];
			$template = false;
		}


	echo "<div align='center'><form method='post' action=\"$target\">";
		if(strcmp($template,"newtemplate") === 0) {
			echo "<input type=\"hidden\" name=\"is_template\" value=\"1\" />";
		}

	echo "<table width='800' class='tab_cadre'>";

		echo "<tr><th align='center' >";
		if(!$template) {
			echo $lang["software"][41].": ".$sw->fields["ID"];
		}elseif (strcmp($template,"newcomp") === 0) {
			echo $lang["software"][42].": ".$sw->fields["tplname"];
		}elseif (strcmp($template,"newtemplate") === 0) {
			echo $lang["common"][6]."&nbsp;: <input type='text' name='tplname' value=\"".$sw->fields["tplname"]."\" size='20'>";
		}
		
		echo "</th><th  colspan='2' align='center'>".$datestring.$date;
		echo "</th></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["software"][2].":		</td>";
	echo "<td colspan='2'><input type='text' name='name' value=\"".$sw->fields["name"]."\" size='25'></td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["software"][4].": 	</td><td colspan='2'>";
		dropdownValue("glpi_dropdown_locations", "location", $sw->fields["location"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["common"][10].": 	</td><td colspan='2'>";
		dropdownUsersID( $sw->fields["tech_num"],"tech_num");
	echo "</td></tr>";
	
	echo "<tr class='tab_bg_1'><td>".$lang["common"][5].": 	</td><td colspan='2'>";
		dropdownValue("glpi_enterprises","FK_glpi_enterprise",$sw->fields["FK_glpi_enterprise"]);
	echo "</td></tr>";
	
	echo "<tr class='tab_bg_1'><td>".$lang["software"][3].": 	</td><td colspan='2'>";
		dropdownValue("glpi_dropdown_os", "platform", $sw->fields["platform"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["software"][5].":		</td>";
	echo "<td colspan='2'><input type='text' name='version' value=\"".$sw->fields["version"]."\" size='5'></td>";
	echo "</tr>";

	// UPDATE
	echo "<tr class='tab_bg_1'><td>".$lang["software"][29]."</td><td colspan='2'>";
	echo "<select name='is_update'><option value='Y' ".($ID&&$sw->fields['is_update']=='Y'?"selected":"").">".$lang['choice'][0]."</option><option value='N' ".(!$ID||$sw->fields['is_update']=='N'?"selected":"").">".$lang['choice'][1]."</option></select>";
	echo "&nbsp;".$lang["pager"][2]."&nbsp;";
	dropdownValueSearch("glpi_software","update_software",$sw->fields["update_software"],$search_software);
/*	if (empty($withtemplate)){
        echo "<input type='text' size='10'  name='search_software' value='$search_software'>";
		echo "<input type='submit' value=\"".$lang["buttons"][0]."\" name='Modif_Interne' class='submit'>";
		}
*/
	echo "</td></tr>";


	echo "<tr class='tab_bg_1'><td valign='top'>";
	echo $lang["software"][6].":	</td>";
	echo "<td align='center' colspan='2'><textarea cols='35' rows='4' name='comments' >".$sw->fields["comments"]."</textarea>";
	echo "</td></tr>";
	
	echo "<tr>";

	if ($template) {

			if (empty($ID)||$withtemplate==2){
			echo "<td class='tab_bg_2' align='center' colspan='2'>\n";
			echo "<input type='hidden' name='ID' value=$ID>";
			echo "<input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'>";
			echo "</td>\n";
			} else {
			echo "<td class='tab_bg_2' align='center' colspan='3'>\n";
			echo "<input type='hidden' name='ID' value=$ID>";
			echo "<input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'>";
			echo "</td>\n";
			}


	} else {


                echo "<td class='tab_bg_2'></td>";
                echo "<td class='tab_bg_2' valign='top'>";
		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
		echo "<div align='center'><input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'></div>";
		echo "</td>";
//		echo "</form>\n\n";
		//echo "<form action=\"$target\" method='post'>\n";
		echo "<td class='tab_bg_2' valign='top'>\n";
//		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
		echo "<div align='center'>";
		if ($sw->fields["deleted"]=='N')
		echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
		else {
		echo "<input type='submit' name='restore' value=\"".$lang["buttons"][21]."\" class='submit'>";
		
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$lang["buttons"][22]."\" class='submit'>";
		}
		echo "</div>";
		echo "</td>";
		
	}
		echo "</tr>";

		echo "</table></form></div>";

		return true;	
	}
	else {
                echo "<div align='center'><b>".$lang["printers"][17]."</b></div>";
                echo "<hr noshade>";
                searchFormSoftware();
                return false;
        }

}

function updateSoftware($input) {
	// Update Software in the database

	$sw = new Software;
	$sw->getFromDB($input["ID"]);
 
	if ($input['is_update']=='N') $input['update_software']=-1;

	// set new date and make sure it gets updated
	$updates[0]= "date_mod";
	$sw->fields["date_mod"] = date("Y-m-d H:i:s");
	
	// Fill the update-array with changes
	$x=1;
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

function addSoftware($input) {
	$db=new DB;
		
	$sw = new Software;

	$oldID=$input["ID"];

	// dump status
	$null = array_pop($input);
	$null = array_pop($input);

	if ($input['is_update']=='N') $input['update_software']=-1;

 	// set new date.
 	$sw->fields["date_mod"] = date("Y-m-d H:i:s");

	// fill array for update
	foreach ($input as $key => $val) {
		if ($key[0]!='_'&&(empty($sw->fields[$key]) || $sw->fields[$key] != $input[$key])) {
			$sw->fields[$key] = $input[$key];
		}
	}

	$newID=$sw->addToDB();
	
	// ADD Infocoms
	$ic= new Infocom();
	if ($ic->getFromDB(SOFTWARE_TYPE,$oldID)){
		$ic->fields["FK_device"]=$newID;
		unset ($ic->fields["ID"]);
		$ic->addToDB();
	}
	

	// ADD Contract				
	$query="SELECT FK_contract from glpi_contract_device WHERE FK_device='$oldID' AND device_type='".SOFTWARE_TYPE."';";
	$result=$db->query($query);
	if ($db->numrows($result)>0){
		
		while ($data=$db->fetch_array($result))
			addDeviceContract($data["FK_contract"],SOFTWARE_TYPE,$newID);
	}
	
	// ADD Documents			
	$query="SELECT FK_doc from glpi_doc_device WHERE FK_device='$oldID' AND device_type='".SOFTWARE_TYPE."';";
	$result=$db->query($query);
	if ($db->numrows($result)>0){
		
		while ($data=$db->fetch_array($result))
			addDeviceDocument($data["FK_doc"],SOFTWARE_TYPE,$newID);
	}

	return $newID;
	
}

function restoreSoftware($input) {
	// Restore Software
	
	$ct = new Software;
	$ct->restoreInDB($input["ID"]);
} 

function deleteSoftware($input,$force=0) {
	// Delete Software
	$sw = new Software;
	$sw->deleteFromDB($input["ID"],$force);
	
} 

function dropdownSoftware($withtemplate='') {
	$db = new DB;
	$query = "SELECT * FROM glpi_software WHERE deleted='N' and is_template='0' order by name";
	$result = $db->query($query);
	$number = $db->numrows($result);

	$i = 0;
	echo "<select name=sID size=1>";
	echo "<option value='-1'>------</option>";
	while ($i < $number) {
		$version = $db->result($result, $i, "version");
		$name = $db->result($result, $i, "name");
		$sID = $db->result($result, $i, "ID");
		
		if (empty($withtemplate)||isGlobalSoftware($sID)||isFreeSoftware($sID))
		echo  "<option value=$sID>$name (v. $version)</option>";
		$i++;
	}
	echo "</select>";
}


function showLicensesAdd($ID) {
	
	GLOBAL $cfg_layout,$cfg_install,$lang;
	
	echo "<div align='center'>&nbsp;<table class='tab_cadre' width='90%' cellpadding='2'>";
	echo "<tr><td align='center' class='tab_bg_2'><strong>";
	echo "<a href=\"".$cfg_install["root"]."/software/software-licenses.php?form=add&amp;sID=$ID\">";
	echo $lang["software"][12];
	echo "</a></strong></td></tr>";
	echo "</table></div><br>";
}

function showLicenses ($sID) {

	GLOBAL $cfg_layout,$cfg_install, $HTMLRel, $lang;
	
	$db = new DB;

	$query = "SELECT count(ID) AS COUNT  FROM glpi_licenses WHERE (sID = '$sID')";
	$query_update = "SELECT count(glpi_licenses.ID) AS COUNT  FROM glpi_licenses, glpi_software WHERE (glpi_software.ID = glpi_licenses.sID AND glpi_software.update_software = '$sID' and glpi_software.is_update='Y')";
	
	if ($result = $db->query($query)) {
		if ($db->result($result,0,0)!=0) { 
			$nb_licences=$db->result($result, 0, "COUNT");
			$result_update = $db->query($query_update);
			$nb_updates=$db->result($result_update, 0, "COUNT");;
			$installed = getInstalledLicence($sID);
			// As t'on utilisé trop de licences en prenant en compte les mises a jours (double install original + mise à jour)
			// Rien si free software
			$pb="";
			if (($nb_licences-$nb_updates-$installed)<0&&!isFreeSoftware($sID)&&!isGlobalSoftware($sID)) $pb="class='tab_bg_1_2'";
			
			echo "<br><div align='center'><table cellpadding='2' class='tab_cadre' width='90%'>";
			echo "<tr><th colspan='5' $pb >";
			echo $nb_licences;
			echo "&nbsp;".$lang["software"][13]."&nbsp;-&nbsp;$nb_updates&nbsp;".$lang["software"][36]."&nbsp;-&nbsp;$installed&nbsp;".$lang["software"][19]."</th>";
			echo "<th colspan='1'>";
			echo " ".$lang["software"][19]." :</th></tr>";
			$i=0;
			echo "<tr><th>".$lang['software'][31]."</th><th>".$lang['software'][21]."</th><th>".$lang['software'][32]."</th><th>".$lang['software'][33]."</th><th>".$lang['software'][35]."</th><th>&nbsp;</th></tr>";
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
		
		$SEARCH_LICENCE="(glpi_licenses.sID = $sID AND glpi_licenses.serial = '".unhtmlentities($serial)."'  AND glpi_licenses.oem = '$oem' AND glpi_licenses.oem_computer = '$oem_computer'  AND glpi_licenses.buy = '$buy' ";
		if ($expire=="")
		$SEARCH_LICENCE.=" AND glpi_licenses.expire IS NULL)";
		else $SEARCH_LICENCE.=" AND glpi_licenses.expire = '$expire')";
		
		$today=date("Y-m-d"); 
		$expirer=0;
		$expirecss="";
		if ($expire!=NULL&&$today>$expire) {$expirer=1; $expirecss="_2";}
		// Get installed licences
		$query_inst = "SELECT glpi_inst_software.ID AS ID, glpi_inst_software.license AS lID, glpi_computers.deleted as deleted, ";
		$query_inst .= " glpi_infocoms.ID as infocoms, ";
		$query_inst .= " glpi_computers.ID AS cID, glpi_computers.name AS cname FROM glpi_licenses, glpi_inst_software ";
		$query_inst .= " INNER JOIN glpi_computers ON (glpi_computers.deleted='N' AND glpi_computers.is_template='0' AND glpi_inst_software.cID= glpi_computers.ID) ";
		$query_inst .= " LEFT JOIN glpi_infocoms ON (glpi_infocoms.device_type='".LICENSE_TYPE."' AND glpi_infocoms.FK_device=glpi_licenses.ID) ";
		$query_inst .= " WHERE $SEARCH_LICENCE ";
		
		$query_inst.= " AND glpi_inst_software.license = glpi_licenses.ID";	
		
		$result_inst = $db->query($query_inst);
		$num_inst=$db->numrows($result_inst);

		echo "<tr class='tab_bg_1'>";
		echo "<td align='center'><strong>".$serial."</strong></td>";
		echo "<td align='center'><strong>";
		echo $num_tot;
		echo "</strong></td>";
		
		echo "<td align='center' class='tab_bg_1$expirecss'><strong>";
		if ($expire==NULL)
			echo $lang["software"][26];
		else {
			if ($expirer) echo $lang["software"][27];
			else echo $lang["software"][25]."&nbsp;".$expire;
			}

		echo "</strong></td>";
		if ($serial!="free"&&$serial!="global"){
			// OEM
			if ($data["OEM"]=='Y') {
			$comp=new Computer();
			$comp->getFromDB($data["OEM_COMPUTER"]);
			}
			echo "<td align='center' class='tab_bg_1".($data["OEM"]=='Y'&&!isset($comp->fields['ID'])?"_2":"")."'>".($data["OEM"]=='Y'?$lang["choice"][0]:$lang["choice"][1]);
			if ($data["OEM"]=='Y') {
			echo "<br><strong>";
			if (isset($comp->fields['ID']))
			echo "<a href='".$cfg_install["root"]."/computers/computers-info-form.php?ID=".$comp->fields['ID']."'>".$comp->fields['name']."</a>";
			else echo "N/A";
			echo "<strong>";
			} 
			echo "</td>";
		
			// BUY
			echo "<td align='center'>".($data["BUY"]=='Y'?$lang["choice"][0]:$lang["choice"][1]);
			echo "</td>";
		} else 
		echo "<td>&nbsp;</td><td>&nbsp;</td>";
		
		echo "<td align='center'>";
		
		
		// Logiciels installés :
		echo "<table width='100%'>";
	
		// Restant	

		echo "<tr><td align='center'>";

		if ($serial!="free"&&$serial!="global") echo $lang["software"][20].": ".($num_tot-$num_inst);
		if ($num_tot!=$num_inst||$serial=="free"||$serial=="global") {
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
				echo "<strong><a href=\"".$cfg_install["root"]."/software/software-licenses.php?delete=delete&amp;ID=$ID\">";
				
				echo "<img src=\"".$HTMLRel."pics/delete.png\" alt='".$lang["buttons"][6]."' title='".$lang["buttons"][6]."'>";
				
				
				echo "</a></strong>";
				echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong><a href=\"".$cfg_install["root"]."/software/software-licenses.php?form=update&amp;lID=$ID&amp;sID=$sID\">";
				
				echo "<img src=\"".$HTMLRel."pics/edit.png\" alt='".$lang["buttons"][14]."' title='".$lang["buttons"][14]."'>";
				
				echo "</a></strong>";
				}
				
			}
		}
		// Dupliquer une licence
		if ($serial!="free"&&$serial!="global"){
		$query_new="SELECT glpi_licenses.ID as ID FROM glpi_licenses WHERE $SEARCH_LICENCE";		
		if ($result_new = $db->query($query_new)) {			
		$IDdup=$db->result($result_new,0,0);
		echo "</td><td align='center' >";
		
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong><a href=\"".$cfg_install["root"]."/software/software-licenses.php?duplicate=duplicate&amp;lID=$IDdup\">";
		
		echo "<img src=\"".$HTMLRel."pics/add.png\" alt='".$lang["buttons"][8]."' title='".$lang["buttons"][8]."'>";
		
		echo "</a></strong>";
		
		}
		}
		
		echo "</td></tr>";
		
		
		// Logiciels installés
		while ($data_inst=$db->fetch_array($result_inst)){
			echo "<tr class='tab_bg_1".(($data["OEM"]=='Y'&&$data["OEM_COMPUTER"]!=$data_inst["cID"])||$data_inst["deleted"]=='Y'?"_2":"")."'><td align='center'>";
			echo "<strong><a href=\"".$cfg_install["root"]."/computers/computers-info-form.php?ID=".$data_inst["cID"]."\">";
			echo $data_inst["cname"];
			echo "</a></strong></td><td align='center'>";
			echo "<strong><a href=\"".$cfg_install["root"]."/software/software-licenses.php?uninstall=uninstall&amp;ID=".$data_inst["ID"]."&amp;cID=".$data_inst["cID"]."\">";
			
			echo "<img src=\"".$HTMLRel."pics/remove.png\" alt='".$lang["buttons"][5]."' title='".$lang["buttons"][5]."'>";
			
			echo "</a></strong>";
				echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong><a href=\"".$cfg_install["root"]."/software/software-licenses.php?form=update&amp;lID=".$data_inst["lID"]."&amp;sID=$sID\">";
				echo "<img src=\"".$HTMLRel."pics/edit.png\" alt='".$lang["buttons"][14]."' title='".$lang["buttons"][14]."'>";
				echo "</a></strong>";

			// Display infocoms
			echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>";
			showDisplayInfocomLink(LICENSE_TYPE,$data_inst["lID"],1);
			echo "</strong>";
			
			echo "</td></tr>";
		}
			
		
		
		echo "</table></td>";
		
		echo "</tr>";
				
	}
	}	
echo "</table></div>\n\n";
	
}


function showLicenseForm($target,$action,$sID,$lID="",$search_computer="") {

	GLOBAL $cfg_install, $cfg_layout, $lang,$HTMLRel;

	$show_infocom=false;
	
	switch ($action){
	case "add" :
		$title= $lang["software"][15]." ($sID):";
		$button= $lang["buttons"][8];
		$ic=new Infocom();
		
		if ($ic->getFromDB(SOFTWARE_TYPE,$sID))
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
	//$values['number']='1';
	$values['expire']='';
	$values['oem']='N';
	$values["oem_computer"]='';
//	$values['is_update']='N';
//	$values["update_software"]='';
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
	
	
	
	
	echo "<div align='center'><strong>";
	echo "<a href=\"".$cfg_install["root"]."/software/software-info-form.php?ID=$sID\">";
	echo $lang["buttons"][13]."</strong>";
	echo "</a><br>";
	
	echo "<form name='form' method='post' action=\"$target\">";
	
	echo "<table class='tab_cadre'><tr><th colspan='3'>$title</th></tr>";
	

	echo "<tr class='tab_bg_1'><td>".$lang["software"][16]."</td>";
	echo "<td><input type='text' size='20' name='serial' value='".$values['serial']."'>";
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
		
	echo "<tr class='tab_bg_1'><td>".$lang["software"][24].":</td><td>";
	showCalendarForm("form","expire",$values['expire']);
	echo "</td></tr>"; 
	
	// OEM
	echo "<tr class='tab_bg_1'><td>".$lang["software"][28]."</td><td>";
	echo "<select name='oem'><option value='Y' ".($values['oem']=='Y'?"selected":"").">".$lang['choice'][0]."</option><option value='N' ".($values['oem']=='N'?"selected":"").">".$lang['choice'][1]."</option></select>";
	dropdownValueSearch("glpi_computers","oem_computer",$values["oem_computer"],$search_computer);
        echo "<input type='text' size='10'  name='search_computer' value='$search_computer'>";
	echo "<input type='submit' value=\"".$lang["buttons"][0]."\" name='Modif_Interne' class='submit'>";
	
	echo "</td></tr>";
	// BUY
	echo "<tr class='tab_bg_1'><td>".$lang["software"][35]."</td><td>";
	echo "<select name='buy'><option value='Y' ".($values['buy']=='Y'?"selected":"").">".$lang['choice'][0]."</option><option value='N' ".($values['buy']=='N'?"selected":"").">".$lang['choice'][1]."</option></select>";
	echo "</td></tr>";
	
	echo "<tr class='tab_bg_2'>";
	echo "<td align='center' colspan='3'>";
	echo "<input type='hidden' name='sID' value=".$sID.">";
	echo "<input type='hidden' name='lID' value=".$lID.">";
	echo "<input type='hidden' name='form' value=".$action.">";
	echo "<input type='submit' name='$action' value=\"".$button."\" class='submit'>";
	echo "</td>";

	echo "</table></form></div>";
}


function addLicense($input) {
	$lic = new License;
	
	// dump status
	$null = array_pop($input);
	$null = array_pop($input);
	$null = array_pop($input);
	$null = array_pop($input);
	
	if (empty($input['expire'])) unset($input['expire']);
	if ($input['oem']=='N') $input['oem_computer']=-1;

	// fill array for update
	foreach ($input as $key => $val) {
		if ($key[0]!='_'&&(empty($lic->fields[$key]) || $sw->fields[$key] != $input[$key])) {
			$lic->fields[$key] = $input[$key];
		}
	}
	$newID=$lic->addToDB();
	
	// Add infocoms if exists for the licence
	$ic=new Infocom();
	if ($ic->getFromDB(SOFTWARE_TYPE,$lic->fields["sID"])){
		unset($ic->fields["ID"]);
		$ic->fields["FK_device"]=$newID;
		$ic->fields["device_type"]=LICENSE_TYPE;
		$ic->addToDB();
	}
	
	return $newID;
}

function updateLicense($input) {
	// Update License in the database

	$lic = new License;
	$lic->getFromDB($input["lID"]);

	if (empty($input['expire'])) unset($input['expire']);
	if ($input['oem']=='N') $input['oem_computer']=-1;

	
	// Fill the update-array with changes
	$x=0;
	foreach ($input as $key => $val) {
		if (array_key_exists($key,$lic->fields) && $lic->fields[$key] != $input[$key]) {
			$lic->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}
	
	if(!empty($updates)) {
	
		$lic->updateInDB($updates);
	}
}

function deleteLicense($ID) {
	// Delete License
	
	$lic = new License;
	$lic->deleteFromDB($ID);
	
} 

function showLicenseSelect($back,$target,$cID,$sID) {

	GLOBAL $cfg_layout,$cfg_install, $lang;
	
	$db = new DB;

	$back = urlencode($back);

	$query = "SELECT DISTINCT glpi_licenses.ID as ID FROM glpi_licenses LEFT JOIN glpi_inst_software ON glpi_licenses.ID = glpi_inst_software.license WHERE (glpi_licenses.sID = $sID AND glpi_inst_software.ID IS NULL) OR (glpi_licenses.sID = $sID AND glpi_licenses.serial='free') OR (glpi_licenses.sID = $sID AND glpi_licenses.serial='global') ORDER BY glpi_licenses.serial";
	if ($result = $db->query($query)) {
		if ($db->numrows($result)!=0) { 
			echo "<br><center><table cellpadding='2' class='tab_cadre' width='50%'>";
			echo "<tr><th colspan='7'>";
			echo $db->numrows($result);
			echo " ".$lang["software"][13].":</th></tr>";
			echo "<tr><th>".$lang['software'][31]."</th><th>".$lang['software'][2]."</th><th>".$lang['software'][32]."</th><th>".$lang['software'][33]."</th><th>".$lang['software'][35]."</th><th>&nbsp;</th></tr>";

			$i=0;
			while ($data=$db->fetch_row($result)) {
				$ID = current($data);
				
				$lic = new License;
				$lic->getfromDB($ID);
				if ($lic->fields['serial']!="free"&&$lic->fields['serial']!="global") {
				
					$query2 = "SELECT license FROM glpi_inst_software WHERE (license = '$ID')";
					$result2 = $db->query($query2);
					if ($db->numrows($result2)==0) {				
						$lic = new License;
						$lic->getfromDB($ID);
						$today=date("Y-m-d"); 
						$expirer=0;
						$expirecss="";
						if ($lic->fields['expire']!=NULL&&$today>$lic->fields['expire']) {$expirer=1; $expirecss="_2";}

						echo "<tr class='tab_bg_1'>";
						echo "<td><strong>$ID</strong></td>";
						echo "<td width='50%' align='center'><strong>".$lic->fields['serial']."</strong></td>";
						
						echo "<td width='50%' align='center' class='tab_bg_1$expirecss'><strong>";
						if ($lic->fields['expire']==NULL)
							echo $lang["software"][26];
						else {
							if ($expirer) echo $lang["software"][27];
							else echo $lang["software"][25]."&nbsp;".$lic->expire;
						}

						echo "</strong></td>";
		// OEM
		if ($lic->fields["oem"]=='Y') {
		$comp=new Computer();
		$comp->getFromDB($lic->fields["oem_computer"]);
		}
		echo "<td align='center' class='tab_bg_1".($lic->fields["oem"]=='Y'&&!isset($comp->fields['ID'])?"_2":"")."'>".($lic->fields["oem"]=='Y'?$lang["choice"][0]:$lang["choice"][1]);
		if ($lic->fields["oem"]=='Y') {
		echo "<br><strong>";
		if (isset($comp->fields['ID']))
		echo "<a href='".$cfg_install["root"]."/computers/computers-info-form.php?ID=".$comp->fields['ID']."'>".$comp->fields['name']."</a>";
		else echo "N/A";
		echo "<strong>";
		} 
		echo "</td>";
		
		// BUY
		echo "<td align='center'>".($lic->fields["buy"]=='Y'?$lang["choice"][0]:$lang["choice"][1]);
		echo "</td>";				
						echo "<td align='center'><strong>";
							echo "<a href=\"".$cfg_install["root"]."/software/software-licenses.php?back=$back&amp;install=install&amp;cID=$cID&amp;lID=$ID\">";
							echo $lang["buttons"][4];
							echo "</a>";
						echo "</strong></td>";
						echo "</tr>";
					}
					$i++;
				} else {
					echo "<tr class='tab_bg_1'>";
					echo "<td><strong>$i</strong></td>";
					echo "<td width='100%' align='center'><strong>".$lic->fields['serial']."</strong></td>";
					echo "<td width='50%' align='center' class='tab_bg_1'><strong>";
					echo $lang["software"][26];
					echo "</strong></td>";
					echo "<td>&nbsp;</td>";
					echo "<td>&nbsp;</td>";
					echo "<td align='center'><strong>";
					echo "<a href=\"".$cfg_install["root"]."/software/software-licenses.php?back=$back&amp;install=install&amp;cID=$cID&amp;lID=$ID\">";
					echo $lang["buttons"][4];
					echo "</a></strong></td>";
					echo "</tr>";	
				}
			}	
			echo "</table></center><br>\n\n";
		} else {

			echo "<br><center><table border='0' width='50%' cellpadding='2' class='tab_cadre'>";
			echo "<tr><th>".$lang["software"][14]."</th></tr>";
			echo "<tr><td align='center'><strong>";
			echo "<a href=\"".$cfg_install["root"]."/software/software-licenses.php?back=$back\">";
			echo $lang["buttons"][13]."</a></strong></td></tr>";
			echo "</table></center><br>";
		}
	}
}

function installSoftware($cID,$lID) {

	$db = new DB;
	$query = "INSERT INTO glpi_inst_software VALUES (NULL,$cID,$lID)";
	if ($result = $db->query($query)) {
		return true;
	} else {
		return false;
	}
}

function uninstallSoftware($ID) {

	$db = new DB;
	$query = "DELETE FROM glpi_inst_software WHERE(ID = '$ID')";
//	echo $query;
	if ($result = $db->query($query)) {
		return true;
	} else {
		return false;
	}
}

function showSoftwareInstalled($instID,$withtemplate='') {

	GLOBAL $cfg_layout,$cfg_install, $lang;
        $db = new DB;
	$query = "SELECT glpi_inst_software.license as license, glpi_inst_software.ID as ID FROM glpi_inst_software, glpi_software,glpi_licenses ";
	$query.= "WHERE glpi_inst_software.license = glpi_licenses.ID AND glpi_licenses.sID = glpi_software.ID AND (glpi_inst_software.cID = '$instID') order by glpi_software.name";
	
	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;
		
	echo "<form method='post' action=\"".$cfg_install["root"]."/software/software-licenses.php\">";
        
	echo "<br><br><center><table class='tab_cadre' width='90%'>";
	echo "<tr><th colspan='5'>".$lang["software"][17].":</th></tr>";
			echo "<tr><th>".$lang['software'][2]."</th><th>".$lang['software'][32]."</th><th>".$lang['software'][33]."</th><th>".$lang['software'][35]."</th><th>&nbsp;</th></tr>";
	
	while ($i < $number) {
		$lID = $db->result($result, $i, "license");
		$ID = $db->result($result, $i, "ID");
		$query2 = "SELECT * FROM glpi_licenses WHERE (ID = '$lID')";
		$result2 = $db->query($query2);
		$data=$db->fetch_array($result2);
		$today=date("Y-m-d"); 
		$expirer=0;
		$expirecss="";
		if ($data['expire']!=NULL&&$today>$data['expire']) {$expirer=1; $expirecss="_2";}

		$sw = new Software;
		$sw->getFromDB($data['sID']);
		
		if ($sw->fields['deleted']=="Y") {$expirer=1; $expirecss="_2";}

		echo "<tr class='tab_bg_1$expirecss'>";
	
		echo "<td align='center'><strong><a href=\"".$cfg_install["root"]."/software/software-info-form.php?ID=".$data['sID']."\">";
		echo $sw->fields["name"]." (v. ".$sw->fields["version"].")</a>";
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
			echo "<td align='center' class='tab_bg_1".($expirer||($data["oem"]=='Y'&&$comp->fields['ID']!=$instID)?"_2":"")."'>".($data["oem"]=='Y'?$lang["choice"][0]:$lang["choice"][1]);
			if ($data["oem"]=='Y') {
			echo "<br><strong>";
			if (isset($comp->fields['ID']))
			echo "<a href='".$cfg_install["root"]."/computers/computers-info-form.php?ID=".$comp->fields['ID']."'>".$comp->fields['name']."</a>";
			else echo "N/A";
			echo "<strong>";
			} 
			echo "</td>";
		
			// BUY
			echo "<td align='center'>".($data["buy"]=='Y'?$lang["choice"][0]:$lang["choice"][1]);
			echo "</td>";								
		}
		else echo "<td>&nbsp;</td><td>&nbsp;</td>";
		echo "<td align='center' class='tab_bg_2'>";
		if(!empty($withtemplate) && $withtemplate == 2) {
			//do nothing
			echo "&nbsp;";
		} else {
			echo "<a href=\"".$cfg_install["root"]."/software/software-licenses.php?uninstall=uninstall&amp;ID=$ID&amp;cID=$instID\">";
			echo "<strong>".$lang["buttons"][5]."</strong></a>";
		}
		echo "</td></tr>";

		$i++;		
	}
	$q="SELECT * FROM glpi_software WHERE deleted='N' AND is_template='0'";
	$result = $db->query($q);
	$nb = $db->numrows($result);
	
	if((!empty($withtemplate) && $withtemplate == 2) || $nb==0) {
		echo "</table></center>";
	} else {
		echo "<tr class='tab_bg_1'><td>&nbsp;</td><td align='center'>";
		echo "<div class='software-instal'><input type='hidden' name='cID' value='$instID'>";
		echo "<input type='hidden' name='withtemplate' value='".$withtemplate."'>";
			dropdownSoftware($withtemplate);
		echo "</div></td><td align='center' class='tab_bg_2'>";
		echo "<input type='submit' name='select' value=\"".$lang["buttons"][4]."\" class='submit'>";
		echo "</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
		echo "</table></center>";
        	echo "</form>";
	}
	
		

}

function countInstallations($sID) {
	
	GLOBAL $cfg_layout, $lang;
	
	$db = new DB;
	
	// Get total
	$total = getLicenceNumber($sID);

	if ($total!=0) {

		if (isFreeSoftware($sID)) {
			// Get installed
			$installed = getInstalledLicence($sID);
			echo "<center><i>".$lang["software"][39]."</i>&nbsp;&nbsp;".$lang["software"][19].": <strong>$installed</strong></center>";
		} else if (isGlobalSoftware($sID)){
			$installed = getInstalledLicence($sID);
			echo "<center><i>".$lang["software"][38]."</i>&nbsp;&nbsp;".$lang["software"][19].": <strong>$installed</strong></center>";
			
		}
		else {
	
			// Get installed
			$i=0;
			$installed = getInstalledLicence($sID);
		
			// Get remaining
			$remaining = $total - $installed;

			// Output
			echo "<table width='100%' cellpadding='2' cellspacing='0'><tr>";
			echo "<td width='35%'>".$lang["software"][19].": <strong>$installed</strong></td>";
			if ($remaining < 0) {
				$remaining = "<span class='red'>$remaining";
				$remaining .= "</span>";
			} else if ($remaining == 0) {
				$remaining = "<span class='green'>$remaining";
				$remaining .= "</span>";
			} else {
				$remaining = "<span class='blue'>$remaining";
				$remaining .= "</span>";
			}			
			echo "<td width='20%'>".$lang["software"][20].": <strong>$remaining</strong></td>";
			echo "<td width='20%'>".$lang["software"][21].": <strong>".$total."</strong></td>";
			$tobuy=getLicenceToBuy($sID);
			if ($tobuy>0){
			echo "<td width='25%'>".$lang["software"][37].": <strong><span class='red'>".$tobuy."</span></strong></td>";
			} else {
			echo "<td width='20%'>&nbsp;</td>";
			}
			echo "</tr></table>";
		} 
	} else {
			echo "<center><i>".$lang["software"][40]."</i></center>";
	}
}	

function getInstalledLicence($sID){
	$db=new DB;
	$query = "SELECT ID FROM glpi_licenses WHERE (sID = '$sID')";
	$result = $db->query($query);
	if ($db->numrows($result)!=0){
		$installed=0;
		while ($data =  $db->fetch_array($result))
			{
			$query2 = "SELECT glpi_inst_software.license FROM glpi_inst_software ";
			$query2.= " INNER JOIN glpi_computers ON glpi_inst_software.cID=glpi_computers.ID AND glpi_computers.deleted='N' AND glpi_computers.is_template='0' ";
			$query2.= " WHERE (glpi_inst_software.license = '".$data["ID"]."')";
			$result2 = $db->query($query2);
			$installed += $db->numrows($result2);
			}
		return $installed;
	} else return 0;
	
}

function getLicenceToBuy($sID){
	$db=new DB;
	$query = "SELECT ID FROM glpi_licenses WHERE (sID = '$sID' AND buy ='N')";
	$result = $db->query($query);
	return $db->numrows($result);
}

function getLicenceNumber($sID){
	$db=new DB;
	$query = "SELECT ID,serial FROM glpi_licenses WHERE (sID = '$sID')";
	$result = $db->query($query);
	return $db->numrows($result);
}

function isGlobalSoftware($sID){
	$db=new DB;
	$query = "SELECT ID,serial FROM glpi_licenses WHERE (sID = '$sID' and serial='global')";
	$result = $db->query($query);
	
	return ($db->numrows($result)>0);
}

function isFreeSoftware($sID){
	$db=new DB;
	$query = "SELECT ID,serial FROM glpi_licenses WHERE (sID = '$sID'  and serial='free')";
	$result = $db->query($query);
	return ($db->numrows($result)>0);
}
?>
