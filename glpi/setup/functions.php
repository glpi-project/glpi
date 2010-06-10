<?php
/*
 
 ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------
 Based on:
IRMA, Information Resource-Management and Administration
Christian Bauer, turin@incubus.de 

 ----------------------------------------------------------------------
 LICENSE

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License (GPL)
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 To read the license please visit http://www.gnu.org/copyleft/gpl.html
 ----------------------------------------------------------------------
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
*/
 

include ("_relpos.php");
// FUNCTIONS Setup

function showFormDropdown ($target,$name,$human) {

	GLOBAL $cfg_layout, $lang;
	
	echo "<center><table border=0 width=50%>";
	echo "<tr><th colspan=2>$human:</th></tr>";
	echo "<form method=post action=\"$target\">";

	echo "<tr><td align=center bgcolor=\"".$cfg_layout["tab_bg_1"]."\">";

	dropdown("dropdown_".$name, "value");

	echo "</td><td align=center bgcolor=\"".$cfg_layout["tab_bg_2"]."\">";
	echo "<input type=hidden name=tablename value=dropdown_".$name.">";
	echo "<input type=submit name=delete value=\"".$lang["buttons"][6]."\">";
	echo "</td></form></tr>";
	echo "<form action=\"$target\" method=post>";
	echo "<tr><td align=center bgcolor=\"".$cfg_layout["tab_bg_1"]."\">";
	echo "<input type=text maxlength=100 size=20 name=value>";
	echo "</td><td align=center bgcolor=\"".$cfg_layout["tab_bg_2"]."\">";
	echo "<input type=hidden name=tablename value=dropdown_".$name.">";
	echo "<input type=submit name= add value=\"".$lang["buttons"][8]."\">";
	echo "</td></form></tr>";
	echo "</table></center>";
}

function showFormTypeDown ($target,$name,$human) {

	GLOBAL $cfg_layout, $lang;
	
	echo "<center><table border=0 width=50%>";
	echo "<tr><th colspan=2>$human:</th></tr>";
	echo "<form method=post action=\"$target\">";

	echo "<tr><td align=center bgcolor=\"".$cfg_layout["tab_bg_1"]."\">";

	dropdown("type_".$name, "value");

	echo "</td><td align=center bgcolor=\"".$cfg_layout["tab_bg_2"]."\">";
	echo "<input type=hidden name=tablename value=type_".$name.">";
	echo "<input type=submit name=delete value=\"".$lang["buttons"][6]."\">";
	echo "</td></form></tr>";
	echo "<form action=\"$target\" method=post>";
	echo "<tr><td align=center bgcolor=\"".$cfg_layout["tab_bg_1"]."\">";
	echo "<input type=text maxlength=100 size=20 name=value>";
	echo "</td><td align=center bgcolor=\"".$cfg_layout["tab_bg_2"]."\">";
	echo "<input type=hidden name=tablename value=type_".$name.">";
	echo "<input type=submit name= add value=\"".$lang["buttons"][8]."\">";
	echo "</td></form></tr>";
	echo "</table></center>";
}


function addDropdown($input) {

	$db = new DB;
	
	$query = "INSERT INTO ".$input["tablename"]." VALUES ('".$input["value"]."')";
	if ($result=$db->query($query)) {
		return true;
	} else {
		return false;
	}
}

function deleteDropdown($input) {

	$db = new DB;
	
	$query = "DELETE FROM ".$input["tablename"]." WHERE (name = '".$input["value"]."')";
	if ($result=$db->query($query)) {
		return true;
	} else {
		return false;
	}
}

function showPasswordForm($target,$ID) {

	GLOBAL $cfg_layout, $lang;
	
	$user = new User;
	$user->getFromDB($ID);
	
	echo "<center><table border=0 cellpadding=5 width=30%>";
	echo "<form method=post action=\"$target\">";
	echo "<tr><th colspan=2>".$lang["setup"][11]." '".$user->fields["name"]."':</th></tr>";
	echo "<tr><td width=100% align=center bgcolor=\"".$cfg_layout["tab_bg_1"]."\">";
	echo "<input type=password name=password size=10>";
	echo "</td><td align=center bgcolor=\"".$cfg_layout["tab_bg_2"]."\">";
	echo "<input type=hidden name=name value=\"".$user->fields["name"]."\">";
	echo "<input type=submit name=changepw value=\"".$lang["buttons"][14]."\">";
	echo "</td></tr>";
	echo "</form>";
	echo "</table></center>";

}

function showUser($back,$ID) {

	GLOBAL $cfg_layout, $lang;
	
	$user = new User;
	$user->getFromDB($ID);

	echo "<center><table border=0 cellpadding=5>";
	echo "<tr><th colspan=2>".$lang["setup"][12].": ".$user->fields["name"]."</th></tr>";

	echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\"><td>".$lang["setup"][13].": </td>";
	echo "<td><b>".$user->fields["realname"]."</b></td></tr>";	

	echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\"><td>".$lang["setup"][14].":</td>";
	echo "<td><b><a href=\"mailto:".$user->fields["email"]."\">".$user->fields["email"]."</b></td></tr>";

	echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\"><td>".$lang["setup"][15].": </td>";
	echo "<td><b>".$user->fields["phone"]."</b></td></tr>";	

	echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\"><td>".$lang["setup"][16].": </td>";
	echo "<td><b>".$user->fields["location"]."</b></td></tr>";	

	echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\"><td>".$lang["setup"][17].": </td>";
	echo "<td><b>".$user->fields["type"]."</b></td></tr>";	

	echo "<tr><td colspan=2 height=10></td></tr>";
	echo "<tr bgcolor=\"".$cfg_layout["tab_bg_2"]."\">";
	echo "<td colspan=2 align=center><b><a href=\"$back\">".$lang["buttons"][13]."</a></b></td></tr>";
	echo "</table></center>";

}

function listUsersForm($target) {
	
	GLOBAL $cfg_layout,$cfg_install, $lang;
	
	$db = new DB;
	
	$query = "SELECT name FROM users where name <> 'Helpdesk' ORDER BY type DESC";
	
	if ($result = $db->query($query)) {

		echo "<center><table border=0>";
		echo "<tr><th>".$lang["setup"][18]."</th><th>".$lang["setup"][19]."</th>";
		echo "<th>".$lang["setup"][13]."</th><th>".$lang["setup"][20]."</th>";
		echo "<th>".$lang["setup"][14]."</th><th>".$lang["setup"][15]."</th>";
		echo "<th>".$lang["setup"][16]."</th><th colspan=2></th></tr>";
		
		$i = 0;
		while ($i < $db->numrows($result)) {
			$name = $db->result($result,$i,"name");
			$user = new User;
			$user->getFromDB($name);
			
			echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\">";	
			echo "<form method=post action=\"$target\">";
			echo "<td align=center><b>".$user->fields["name"]."</b>";
			echo "<input type=hidden name=name value=\"".$user->fields["name"]."\">";
			echo "</td>";
			echo "<td><input type=password name=password size=6></td>";
			
			echo "<td><input name=realname size=10 value=\"".$user->fields["realname"]."\"></td>";

			echo "<td>";
			echo "<select name=type>";
			echo "<option value=admin";
				if ($user->fields["type"]=="admin") { echo " selected"; }
			echo ">Admin";
			echo "<option value=normal";
				if ($user->fields["type"]=="normal") { echo " selected"; }
			echo ">Normal";
			echo "<option value=\"post-only\"";
				if ($user->fields["type"]=="post-only") { echo " selected"; }
			echo ">Post Only";
			echo "</select>";
			echo "</td>";	
			echo "<td><input name=email size=6 value=\"".$user->fields["email"]."\"></td>";
			echo "<td><input name=phone size=6 value=\"".$user->fields["phone"]."\"></td>";
			echo "<td>";
				dropdownValue("dropdown_locations", "location", $user->fields["location"]);
			echo "</td>";
			echo "<td bgcolor=\"".$cfg_layout["tab_bg_2"]."\"><input type=submit name=update value=\"".$lang["buttons"][7]."\"></td>";
			echo "<td bgcolor=\"".$cfg_layout["tab_bg_2"]."\"><input type=submit name=delete value=\"".$lang["buttons"][6]."\"></td>";
			echo "</tr></form>";
			$i++;
		}	

		echo "</table>";
		
		echo "<table border=0>";
		echo "<tr><th>Login</th><th>".$lang["setup"][13]."</th><th>".$lang["setup"][20]."</th>";
		echo "<th>".$lang["setup"][14]."</th><th>".$lang["setup"][15]."</th>";
		echo "<th>".$lang["setup"][16]."</th></tr>";
		echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\">";	
		echo "<form method=post action=\"$target\">";
		echo "<td><input name=name size=7 value=\"\"></td>";
		echo "<td><input name=realname size=15 value=\"\"></td>";
		echo "<td>";
		echo "<select name=type>";
		echo "<option value=admin>Admin";
		echo "<option value=normal>Normal";
		echo "<option value=\"post-only\">Post Only";
		echo "</select>";
		echo "</td>";	
		echo "<td><input name=email size=15 value=\"\"></td>";
		echo "<td><input name=phone size=10 value=\"\"></td>";
		echo "<td>";
			dropdownValue("dropdown_locations", "location", "");
		echo "</td";
					
		echo "</tr>";
		echo "<tr bgcolor=\"".$cfg_layout["tab_bg_2"]."\">";
		echo "<td colspan=5 align=center><i>".$lang["setup"][21]."</i></td>";
		echo "<td align=center>";
		echo "<input type=submit name=add value=\"".$lang["buttons"][8]."\">";
		echo "</td>";
		echo "</tr></form>";

		echo "</table></center>";	
	}
}


function addUser($input) {
	// Add User, nasty hack until we get PHP4-array-functions

	$user = new User;

	// dump status
	$null = array_pop($input);
	
	// fill array for update
	for ($i=0; $i < count($input); $i++) {
		list($key,$val) = each($input);
		if ($user->fields[$key] != $input[$key]) {
			$user->fields[$key] = $input[$key];
		}
	}

	if ($user->addToDB()) {
		// Give him some default prefs...
		$query = "INSERT INTO prefs VALUES ('".$input["name"]."','','english')";
		$db = new DB;
		$result=$db->query($query);
		return true;
	} else {
		return false;
	}
}


function updateUser($input) {
	// Update User in the database

	$user = new User;
	$user->getFromDB($input["name"]); 

 	// dump status
	$null = array_pop($input);

	// password updated?
	if (!$input["password"]) {
		$user->fields["password"]="";
	}

	// fill array for update
	$x=0;
	for ($i=0; $i < count($input); $i++) {
		list($key,$val) = each($input);
		if ($input[$key]!=$user->fields[$key]) {
			$user->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}

	$user->updateInDB($updates);
}

function deleteUser($input) {
	// Delete User
	
	$user = new User;
	$user->deleteFromDB($input["name"]);
} 


function showFormAssign($target)
{

	GLOBAL $cfg_layout,$cfg_install, $lang, $IRMName;
	
	$db = new DB;
	
	$query = "SELECT name FROM users where name <> 'Helpdesk' ORDER BY type DESC";
	
	if ($result = $db->query($query)) {

		echo "<center><table border=0>";
		echo "<tr><th>".$lang["setup"][57]."</th><th colspan='2'>".$lang["setup"][58]."</th>";
		echo "</tr>";
		
		  $i = 0;
		  while ($i < $db->numrows($result)) {
			$name = $db->result($result,$i,"name");
			$user = new User;
			$user->getFromDB($name);
			
			echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\">";	
			echo "<form method='post' action=\"$target\">";
			echo "<td align='center'><b>".$user->fields["name"]."</b>";
			echo "<input type='hidden' name='name' value=\"".$user->fields["name"]."\">";
			echo "</td>";
			echo "<td align='center'><strong>".$lang["setup"][60]."</strong><input type='radio' value='0' name='can_assign_job' ";
			if ($user->fields["can_assign_job"] == 0) echo "checked ";
      echo ">";
      echo "<td align='center'><strong>".$lang["setup"][61]."</strong><input type='radio' value='1' name='can_assign_job' ";
			if ($user->fields["can_assign_job"] == 1) echo "checked";
      echo ">";
			echo "</td>";
			echo "<td bgcolor=\"".$cfg_layout["tab_bg_2"]."\"><input type='submit' name='update' value=\"".$lang["buttons"][7]."\"></td>";
						
                        echo "</form>";
	
      $i++;
			}
echo "</table></center>";}
}

function listTemplates($target) {

	GLOBAL $cfg_layout, $lang;

	$db = new DB;
	$query = "SELECT * FROM templates";
	if ($result = $db->query($query)) {
		
		echo "<center><table border=0 width=50%>";
		echo "<tr><th colspan=2>".$lang["setup"][1].":</th></tr>";
		$i=0;
		while ($i < $db->numrows($result)) {
			$ID = $db->result($result,$i,"ID");
			$templname = $db->result($result,$i,"templname");
			
			echo "<tr>";
			echo "<td align=center bgcolor=\"".$cfg_layout["tab_bg_1"]."\">";
			echo "<a href=\"$target?ID=$ID&showform=showform\">$templname</a></td>";
			echo "<td align=center bgcolor=\"".$cfg_layout["tab_bg_2"]."\">";
			echo "<b><a href=\"$target?ID=$ID&delete=delete\">".$lang["buttons"][6]."</a></b></td>";
			echo "</tr>";		

			$i++;
		}

		echo "<tr>";
		echo "<td colspan=2 align=center bgcolor=\"".$cfg_layout["tab_bg_2"]."\">";
		echo "<b><a href=\"$target?showform=showform\">".$lang["setup"][22]."</a></b>";
		echo "</td>";
		echo "</tr>";
		echo "</form>";
				
		echo "</table></center>";
	}
	

}

function showTemplateForm($target,$ID) {

	GLOBAL $cfg_install, $cfg_layout, $lang;

	$templ = new Template;
	
	if ($ID) {
		$templ->getfromDB($ID);
	}

	echo "<center><table border=0>";
	echo "<form name='form' method=post action=$target>";
	echo "<tr><th colspan=2>";
	if ($ID) {
		echo $lang["setup"][23].": '".$templ->fields["templname"]."'";
	
	} else {
		echo $lang["setup"][23].": <input name=templname size=10>";	
	}
	echo "</th></tr>";
	
	echo "<tr><td bgcolor=#CCCCCC valign=top>";
	echo "<table cellpadding=0 cellspacing=0 border=0>\n";

	echo "<tr><td>".$lang["setup"][24].":		</td>";
	echo "<td><input type=text name=name value=\"".$templ->fields["name"]."\" size=10></td>";
	echo "</tr>";

	echo "<tr><td>".$lang["setup"][25].": 	</td>";
	echo "<td>";
		dropdownValue("dropdown_locations", "location", $templ->fields["location"]);
	echo "</td></tr>";

	echo "<tr><td>".$lang["setup"][26].":		</td>";
	echo "<td><input type=text name=contact_num value=\"".$templ->fields["contact_num"]."\" size=5>";
	echo "</td></tr>";
	
	echo "<tr><td>".$lang["setup"][27].":	</td>";
	echo "<td><input type=text name=contact size=12 value=\"".$templ->fields["contact"]."\">";
	echo "</td></tr>";

	echo "<tr><td>".$lang["setup"][28].":	</td>";
	echo "<td><input type=text name=serial size=12 value=\"".$templ->fields["serial"]."\">";
	echo "</td></tr>";

	echo "<tr><td>".$lang["setup"][29].":	</td>";
	echo "<td><input type=text size=12 name=otherserial value=\"".$templ->fields["otherserial"]."\">";
	echo "</td></tr>";

	echo "<tr><td valign=top>".$lang["setup"][30].":</td>";
	echo "<td><textarea cols=20 rows=8 name=comments wrap=soft>".$templ->fields["comments"]."</textarea>";
	echo "</td></tr>";

	echo "</table>";

	echo "</td>\n";	
	echo "<td bgcolor=#CCCCCC valign=top>\n";
	echo "<table cellpadding=0 cellspacing=0 border=0";


	echo "<tr><td>".$lang["setup"][31].": 	</td>";
	echo "<td>";
		dropdownValue("type_computers", "type", $comp->fields["type"]);
	echo "</td></tr>";

	echo "<tr><td>".$lang["setup"][32].": 	</td>";
	echo "<td>";	
		dropdownValue("dropdown_os", "os", $templ->fields["os"]);
	echo "</td></tr>";
		
	echo "<tr><td>".$lang["setup"][33].":</td>";
	echo "<td><input type=text size=8 name=osver value=\"".$templ->fields["osver"]."\">";
	echo "</td></tr>";
		
	echo "<tr><td>".$lang["setup"][34].":	</td>";
	echo "<td>";
		dropdownValue("dropdown_processor", "processor", $templ->fields["processor"]);
	echo "</td></tr>";
	
	echo "<tr><td>".$lang["setup"][35].":	</td>";
	echo "<td><input type=text name=processor_speed size=4 value=\"".$templ->fields["processor_speed"]."\">";
	echo "</td></tr>";
	
	echo "<tr><td>".$lang["setup"][49].":	</td>";
	echo "<td>";
		dropdownValue("dropdown_moboard", "moboard", $templ->fields["moboard"]);
	echo "</td></tr>";

	echo "<tr><td>".$lang["setup"][51].":	</td>";
	echo "<td>";
		dropdownValue("dropdown_sndcard", "sndcard", $templ->fields["sndcard"]);
	echo "</td></tr>";
		
	echo "<tr><td>".$lang["setup"][50].":	</td>";
	echo "<td>";
		dropdownValue("dropdown_gfxcard", "gfxcard", $templ->fields["gfxcard"]);
	echo "</td></tr>";
		
	echo "<tr><td>".$lang["setup"][36].":	</td>";
	echo "<td>";
		dropdownValue("dropdown_ram", "ramtype", $templ->fields["ramtype"]);
	echo "</td></tr>";
	
	echo "<tr><td>".$lang["setup"][37].":	</td>";
	echo "<td><input type=text name=ram value=\"".$templ->fields["ram"]."\" size=3>";
	echo "</td></tr>";

	echo "<tr><td>".$lang["setup"][52].":	</td>";
	echo "<td>";
		dropdownValue("dropdown_hdtype", "hdtype", $templ->fields["hdtype"]);
	echo "</td></tr>";

	echo "<tr><td>".$lang["setup"][38].":	</td>";
	echo "<td><input type=text name=hdspace size=3 value=\"".$templ->fields["hdspace"]."\">";
	echo "</td></tr>";

	echo "<tr><td>".$lang["setup"][39].":	</td>";
	echo "<td>";
		dropdownValue("dropdown_network", "network", $templ->fields["network"]);
	echo "</td></tr>";

//
	
	echo "<tr><td>".$lang["setup"][53].":	</td>";
	echo "<td><input type=text name='achat_date' readonly size=10 value=\"0000-00-00\">";
	echo "&nbsp; <input name='button' type='button' onClick=\"window.open('mycalendar.php?form=form&elem=achat_date','Calendrier','width=200,height=220')\" value='".$lang["buttons"][15]."...'>";
	echo "&nbsp; <input name='button_reset' type='button' onClick=\"document.forms['form'].achat_date.value='0000-00-00'\" value='reset'>";
  echo "</td></tr>";
	
	echo "<tr><td>".$lang["setup"][54].":	</td>";
	echo "<td><input type=text name='date_fin_garantie' readonly size=10 value=\"0000-00-00\">";
	echo "&nbsp; <input name='button' type='button' readonly onClick=\"window.open('mycalendar.php?form=form&elem=date_fin_garantie','Calendrier','width=200,height=220')\" value='".$lang["buttons"][15]."...'>";
	echo "&nbsp; <input name='button_reset' type='button' onClick=\"document.forms['form'].date_fin_garantie.value='0000-00-00'\" value='reset'>";
  echo "</td></tr>";
	
echo "<tr><td>".$lang["setup"][55].":	</td>";
		echo "<td>";
		if ($temp1->fields["maintenance"] == 1) {
			echo " OUI <input type=radio name='maintenance' value=1 checked>";
			echo "&nbsp; &nbsp; NON <input type=radio name='maintenance' value=0>";
		} else {
			echo " OUI <input type=radio name='maintenance' value=1>";
			echo "&nbsp; &nbsp; NON <input type=radio name='maintenance' value=0 checked >";
		}
		echo "</td></tr>";


	echo "</table>";

	echo "</td>\n";	
	echo "</tr><tr>";

	if ($ID) {
		echo "<td bgcolor=\"".$cfg_layout["tab_bg_2"]."\" align=center valign=top colspan=2>\n";
		echo "<input type=hidden name=ID value=$ID>";
		echo "<input type=submit name=update value=\"".$lang["buttons"][7]."\">";
		echo "</td></form>\n";	
	} else {
		echo "<td bgcolor=\"".$cfg_layout["tab_bg_2"]."\" align=center valign=top colspan=2>\n";
		echo "<input type=submit name=add value=\"".$lang["buttons"][8]."\">";
		echo "</td></form>\n";	
	}
	
	echo "</tr>\n";
	echo "</table>\n";

	echo "</center>\n";

	echo "</table></center>";
}


function updateTemplate($input) {
	// Update a template in the database

	$templ = new Template;
	$templ->getFromDB($input["ID"],0);

	// dump status
	$null = array_pop($input);
	
	// fill array for update
	$x=0;
	for ($i=0; $i < count($input)-1; $i++) {
		list($key,$val) = each($input);
		if ($templ->fields[$key] != $input[$key]) {
			$templ->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}

	$templ->updateInDB($updates);

}

function addTemplate($input) {
	// Add template, nasty hack until we get PHP4-array-functions

	$templ = new Template;

	// dump status
	$null = array_pop($input);
	
	// fill array for update 
	for ($i=0; $i < count($input); $i++) {
		list($key,$val) = each($input);
		if ($templ->fields[$key] != $input[$key]) {
			$templ->fields[$key] = $input[$key];
		}
	}

	$templ->addToDB();

}

function deleteTemplate($input) {
	// Delete Template
	
	$templ = new Template;
	$templ->deleteFromDB($input["ID"]);
	
} 	

function showSortForm($target,$ID) {

	GLOBAL $cfg_layout, $lang;
	
	$db = new DB;
	$query = "SELECT tracking_order FROM prefs WHERE (user = '$ID')";
	$result=$db->query($query);

	echo "<center><table border=0 cellpadding=5 width=30%>";
	echo "<form method=post action=\"$target\">";
	echo "<tr><th colspan=2>".$lang["setup"][40]."</th></tr>";
	echo "<tr><td width=100% align=center bgcolor=\"".$cfg_layout["tab_bg_1"]."\">";
	echo "<select name=tracking_order>";
	echo "<option value=\"yes\"";
	if ($db->result($result,0,"tracking_order")=="yes") { echo " selected"; }	
	echo ">".$lang["choice"][1];
	echo "<option value=\"no\"";
	if ($db->result($result,0,"tracking_order")=="no") { echo " selected"; }	
	echo ">".$lang["choice"][0];
	echo "</select>";
	echo "</td>";
	echo "<td align=center bgcolor=\"".$cfg_layout["tab_bg_2"]."\">";
	echo "<input type=hidden name=user value=\"$ID\">";
	echo "<input type=submit name=updatesort value=\"".$lang["buttons"][14]."\">";
	echo "</td></tr>";
	echo "</form>";
	echo "</table></center>";
}

function updateSort($input) {

	$db = new DB;
	$query = "UPDATE prefs SET tracking_order = '".$input["tracking_order"]."' WHERE (user = '".$input["user"]."')";
	if ($result=$db->query($query)) {
		return true;
	} else {
		return false;
	}
}

function showLangSelect($target,$ID) {

	GLOBAL $cfg_layout, $cfg_install, $lang;
	
	$db = new DB;
	$query = "SELECT language FROM prefs WHERE (user = '$ID')";
	$result=$db->query($query);

	echo "<center><table border=0 cellpadding=5 width=30%>";
	echo "<form method=post action=\"$target\">";
	echo "<tr><th colspan=2>".$lang["setup"][41].":</th></tr>";
	echo "<tr><td width=100% align=center bgcolor=\"".$cfg_layout["tab_bg_1"]."\">";
	echo "<select name=language>";
	$i=0;
	while ($i < count($cfg_install["languages"])) {
		echo "<option value=\"".$cfg_install["languages"][$i]."\"";
		if ($db->result($result,0,"language")==$cfg_install["languages"][$i]) { 
			echo " selected"; 
		}
		echo ">".$cfg_install["languages"][$i];
		$i++;
	}
	echo "</select>";
	echo "</td>";
	echo "<td align=center bgcolor=\"".$cfg_layout["tab_bg_2"]."\">";
	echo "<input type=hidden name=user value=\"$ID\">";
	echo "<input type=submit name=changelang value=\"".$lang["buttons"][14]."\">";
	echo "</td></tr>";
	echo "</form>";
	echo "</table></center>";
}

function updateLanguage($input) {

	$db = new DB;
	$query = "UPDATE prefs SET language = '".$input["language"]."' WHERE (user = '".$input["user"]."')";
	if ($result=$db->query($query)) {
		return true;
	} else {
		return false;
	}
}

?>
