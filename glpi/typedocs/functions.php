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
// FUNCTIONS type documents


function titleTypedocs(){
                GLOBAL  $lang,$HTMLRel;
                echo "<div align='center'><table border='0'><tr><td>";
                echo "<img src=\"".$HTMLRel."pics/docs.png\" alt='".$lang["document"][12]."' title='".$lang["document"][12]."'></td><td><a  class='icon_consol' href=\"typedocs-info-form.php\"><b>".$lang["document"][12]."</b></a>";
                echo "</td>";
                echo "</tr></table></div>";
}


function searchFormTypedoc($field="",$phrasetype= "",$contains="",$sort= "") {
	// Print Search Form
	
	GLOBAL $cfg_install, $cfg_layout, $layout, $lang,$HTMLRel;

	$option["glpi_type_docs.name"]				= $lang["document"][1];
	$option["glpi_type_docs.ID"]				= $lang["document"][14];
	$option["glpi_type_docs.ext"]				= $lang["document"][9];
	$option["glpi_type_docs.mime"]				= $lang["document"][4];
	$option["glpi_type_docs.upload"]				= $lang["document"][15];

	echo "<form method='get' action=\"".$cfg_install["root"]."/typedocs/typedocs-search.php\">";
	echo "<div align='center'><table  width='750' class='tab_cadre'>";
	echo "<tr><th colspan='2'><b>".$lang["search"][0].":</b></th></tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'>";
	echo "<input type='text' size='15' name=\"contains\" value=\"". $contains ."\" >";
	echo "&nbsp;";
	
	echo $lang["search"][10]."&nbsp;";
	echo "<select name=\"field\" size='1'>";
        echo "<option value='all' ";
	if($field == "all") echo "selected";
	echo ">".$lang["search"][7]."</option>";
        reset($option);
	foreach ($option as $key => $val) {
		echo "<option value=\"".$key."\""; 
		if($key == $field) echo "selected";
		echo ">". $val ."</option>\n";
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


function showTypedocList($target,$username,$field,$phrasetype,$contains,$sort,$order,$start) {

	// Lists peripheral

	GLOBAL $cfg_install, $cfg_layout, $cfg_features, $lang, $HTMLRel;

	$db = new DB;

	// Build query
	if($field=="all") {
		$where = " (";
		$fields = $db->list_fields("glpi_type_docs");
		$columns = $db->num_fields($fields);
		
		for ($i = 0; $i < $columns; $i++) {
			if($i != 0) {
				$where .= " OR ";
			}
			$coco = $db->field_name($fields, $i);

			$where .= $coco . " LIKE '%".$contains."%'";
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
	$query = "select ID from glpi_type_docs ";
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
			// Produce headline
			echo "<center><table  class='tab_cadre'><tr>";
			// Name
			echo "<th>";
			if ($sort=="glpi_type_docs.name") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_type_docs.name&order=ASC&start=$start\">";
			echo $lang["document"][1]."</a></th>";

			// Extension
			echo "<th>";
			if ($sort=="glpi_type_docs.ext") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_type_docs.ext&order=ASC&start=$start\">";
			echo $lang["document"][9]."</a></th>";
			
			// icon			
			echo "<th>";
			if ($sort=="glpi_type_docs.icon") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_type_docs.icon&order=ASC&start=$start\">";
			echo $lang["document"][10]."</a></th>";

			// MIME
			echo "<th>";
			if ($sort=="glpi_type_docs.mime") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_type_docs.mime&order=ASC&start=$start\">";
			echo $lang["document"][4]."</a></th>";

			// Upload		
			echo "<th>";
			if ($sort=="glpi_type_docs.upload") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_type_docs.upload&order=DESC&start=$start\">";
			echo $lang["document"][15]."</a></th>";

			for ($i=0; $i < $numrows_limit; $i++) {
				$ID = $db->result($result_limit, $i, "ID");
				$mon = new Typedoc;
				$mon->getfromDB($ID);
				echo "<tr class='tab_bg_2' align='center'>";
				echo "<td><b>";
				echo "<a href=\"".$cfg_install["root"]."/typedocs/typedocs-info-form.php?ID=$ID\">";
				echo $mon->fields["name"]." (".$mon->fields["ID"].")";
				echo "</a></b></td>";
				echo "<td>". $mon->fields["ext"] ."</td>";
				echo "<td>&nbsp;";
				if (!empty($mon->fields["icon"])) echo "<img style='vertical-align:middle;' alt='' src='".$HTMLRel.$cfg_install["typedoc_icon_dir"]."/".$mon->fields["icon"]."'>";
				echo "</td>";
				echo "<td>". $mon->fields["mime"] ."</td>";
				echo "<td>". $mon->fields["upload"] ."</td>";
				
				echo "</tr>";
			}

			// Close Table
			echo "</table></center>";

			// Pager
			$parameters="field=$field&phrasetype=$phrasetype&contains=$contains&sort=$sort&order=$order";
			printPager($start,$numrows,$target,$parameters);

		} else {
			echo "<center><b>".$lang["peripherals"][17]."</b></center>";
			//echo "<hr noshade>";
			//searchFormperipheral();
		}
	}
}


function showTypedocForm ($target,$ID) {

	GLOBAL $cfg_install, $cfg_layout, $lang,$HTMLRel,$phproot;

	$mon = new Typedoc;

	$mon_spotted = false;

	if(empty($ID)) {
		if($mon->getEmpty()) $mon_spotted = true;
	} else {
		if($mon->getfromDB($ID)) $mon_spotted = true;
	}
	$date = $mon->fields["date_mod"];
	$datestring = $lang["document"][5]." : ";
	
	if ($mon_spotted){
	echo "<div align='center'><form method='post' name=form action=\"$target\">";

	echo "<table class='tab_cadre' cellpadding='2'>";

		echo "<tr><th align='center' >";
		if (empty($ID))
		echo $lang["document"][17];
		else 
		echo $lang["document"][7].": ".$mon->fields["ID"];
		
		echo "</th><th  align='center'>".$datestring.$date;
		echo "</th></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["document"][1].":	</td>";
	echo "<td><input type='text' name='name' value=\"".$mon->fields["name"]."\" size='20'></td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["document"][9].":	</td>";
	echo "<td><input type='text' name='ext' value=\"".$mon->fields["ext"]."\" size='20'></td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["document"][10].":	</td><td>";
	dropdownIcons("icon",$mon->fields["icon"],$phproot.$cfg_install["typedoc_icon_dir"]);
	if (!empty($mon->fields["icon"])) echo "&nbsp;<img style='vertical-align:middle;' alt='' src='".$HTMLRel.$cfg_install["typedoc_icon_dir"]."/".$mon->fields["icon"]."'>";
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["document"][4].":	</td>";
	echo "<td><input type='text' name='mime' value=\"".$mon->fields["mime"]."\" size='20'></td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["document"][11].":	</td><td>";
	if (empty($mon->fields["upload"])) $mon->fields["upload"]='Y';
	dropdownYesNo("upload",$mon->fields["upload"]);
	echo "</td></tr>";
	
	if(empty($ID)){

		echo "<td class='tab_bg_2' valign='top' colspan='3'>";
		echo "<div align='center'><input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'></div>";
		echo "</td>";
		
	} else {
	
		echo "<td class='tab_bg_2' valign='top'>";
		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
		echo "<center><input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit' class='submit'></center>";
		echo "</td></form>\n\n";
		echo "<form action=\"$target\" method='post'>\n";
		echo "<td class='tab_bg_2' valign='top'>\n";
		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
		echo "<div align='center'>";
		echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
		echo "</div>";
		echo "</td>";
	}
		
		echo "</form></tr>";

		echo "</table></div>";
	
		return true;	
	}
	else {
                echo "<div align='center'><b>".$lang["printers"][17]."</b></div>";
                echo "<hr noshade>";
                searchFormPrinters();
                return false;
        }

}


function updateTypedoc($input) {
	// Update a Peripheral in the database

	$mon = new Typedoc;
	$mon->getFromDB($input["ID"]);

	// set new date and make sure it gets updated
	
	$updates[0]= "date_mod";
	$mon->fields["date_mod"] = date("Y-m-d H:i:s");

	// Pop off the last two attributes, no longer needed
	$null=array_pop($input);
	
	// Get all flags and fill with 0 if unchecked in form
	foreach ($mon->fields as $key => $val) {
		if (eregi("\.*flag\.*",$key)) {
			if (!isset($input[$key])) {
				$input[$key]=0;
			}
		}
	}

	// Fill the update-array with changes
	$x=1;
	foreach ($input as $key => $val) {
		if (isset($mon->fields[$key]) && $mon->fields[$key] != $input[$key]) {
			$mon->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}

	$mon->updateInDB($updates);

}

function addTypedoc($input) {
	// Add Peripheral, nasty hack until we get PHP4-array-functions
	$db=new DB;
	$mon = new Typedoc;

	// dump status
	$null = array_pop($input);

 	// set new date.
 	$mon->fields["date_mod"] = date("Y-m-d H:i:s");
	
	// fill array for udpate
	foreach ($input as $key => $val) {
		if (!isset($mon->fields[$key]) || $mon->fields[$key] != $input[$key]) {
			$mon->fields[$key] = $input[$key];
		}
	}

	$mon->addToDB();
}

function deleteTypedoc($input,$force=0) {
	// Delete Printer
	
	$mon = new Typedoc;
	$mon->deleteFromDB($input["ID"],$force);
	
}

function isValidDoc($filename){
	$splitter=split("\.",$filename);
	$ext=end($splitter);
	$db=new DB();
	$query="SELECT * from glpi_type_docs where ext LIKE '$ext' AND upload='Y'";
	if ($result = $db->query($query))
	if ($db->numrows($result)>0)
	return strtoupper($ext);
	
return "";
}

 	
?>
