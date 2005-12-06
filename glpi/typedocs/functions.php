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



function showTypedocForm ($target,$ID) {

	GLOBAL $cfg_install, $cfg_layout, $lang,$HTMLRel,$phproot;

	$mon = new Typedoc;

	$mon_spotted = false;

	if(empty($ID)) {
		if($mon->getEmpty()) $mon_spotted = true;
	} else {
		if($mon->getfromDB($ID)) $mon_spotted = true;
	}
	
	if ($mon_spotted){
	$date = $mon->fields["date_mod"];
	$datestring = $lang["document"][5]." : ";

	echo "<div align='center'><form method='post' name=form action=\"$target\">";

	echo "<table class='tab_cadre' cellpadding='2'>";

		echo "<tr><th align='center' >";
		if (empty($ID))
		echo $lang["document"][17];
		else 
		echo $lang["document"][7].": ".$mon->fields["ID"];
		
		echo "</th><th  align='center'>".$datestring.$date;
		echo "</th></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["document"][1].":	</td><td>";
	autocompletionTextField("name","glpi_type_docs","name",$mon->fields["name"],20);
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["document"][9].":	</td><td>";
	autocompletionTextField("ext","glpi_type_docs","ext",$mon->fields["ext"],20);

	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["document"][10].":	</td><td>";
	dropdownIcons("icon",$mon->fields["icon"],$phproot.$cfg_install["typedoc_icon_dir"]);
	if (!empty($mon->fields["icon"])) echo "&nbsp;<img style='vertical-align:middle;' alt='' src='".$HTMLRel.$cfg_install["typedoc_icon_dir"]."/".$mon->fields["icon"]."'>";
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["document"][4].":	</td><td>";
	autocompletionTextField("mime","glpi_type_docs","mime",$mon->fields["mime"],20);
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["document"][11].":	</td><td>";
	if (empty($mon->fields["upload"])) $mon->fields["upload"]='Y';
	dropdownYesNo("upload",$mon->fields["upload"]);
	echo "</td></tr>";
	echo "<tr>";
	if(empty($ID)){

		echo "<td class='tab_bg_2' valign='top' colspan='3'>";
		echo "<div align='center'><input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'></div>";
		echo "</td>";
		
	} else {
	
		echo "<td class='tab_bg_2' valign='top'>";
		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
		echo "<center><input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'></center>";
		echo "</td>";
		echo "<td class='tab_bg_2' valign='top'>\n";
		echo "<div align='center'>";
		echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
		echo "</div>";
		echo "</td>";
	}
		
		echo "</tr>";

		echo "</table></form></div>";
	
		return true;	
	}
	else {
                echo "<div align='center'><b>".$lang["document"][23]."</b></div>";
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
		if (array_key_exists($key,$mon->fields) && $mon->fields[$key] != $input[$key]) {
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
	unset($input["add"]);
	
 	// set new date.
 	$mon->fields["date_mod"] = date("Y-m-d H:i:s");
	
	// fill array for udpate
	foreach ($input as $key => $val) {
		if ($key[0]!='_'&&(!isset($mon->fields[$key]) || $mon->fields[$key] != $input[$key])) {
			$mon->fields[$key] = $input[$key];
		}
	}

	return $mon->addToDB();
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
