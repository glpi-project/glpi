<?php
/*
* @version $Id$
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi-project.org
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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

 
// CLASSES peripherals


class Typedoc  extends CommonDBTM {


	function Typedoc () {
		$this->table="glpi_type_docs";
		$this->type=TYPEDOC_TYPE;
	}

	function prepareInputForUpdate($input) {
		// set new date.
		$input["date_mod"] = date("Y-m-d H:i:s");
		return $input;
	}
	function prepareInputForAdd($input) {
		// set new date.
		$input["date_mod"] = date("Y-m-d H:i:s");
	
		return $input;
	}

	function title(){
		global  $lang,$HTMLRel;
		echo "<div align='center'><table border='0'><tr><td>";
		echo "<img src=\"".$HTMLRel."pics/docs.png\" alt='".$lang["document"][12]."' title='".$lang["document"][12]."'></td>";
		if (haveRight("typedoc","w")){
			echo "<td><a  class='icon_consol' href=\"typedoc.form.php\"><b>".$lang["document"][12]."</b></a>";
			echo "</td>";
		}
		echo "</tr></table></div>";
	}
	
	
	
	function showForm ($target,$ID) {
	
		global $cfg_glpi, $lang,$HTMLRel,$phproot;
	
		if (!haveRight("typedoc","r")) return false;
	
		$spotted = false;
	
		if(empty($ID)) {
			if($this->getEmpty()) $spotted = true;
		} else {
			if($this->getfromDB($ID)) $spotted = true;
		}
		
		if ($spotted){
		$date = $this->fields["date_mod"];
		$datestring = $lang["common"][26]." : ";
	
		echo "<div align='center'><form method='post' name=form action=\"$target\">";
	
		echo "<table class='tab_cadre' cellpadding='2'>";
	
			echo "<tr><th align='center' >";
			if (empty($ID))
			echo $lang["document"][17];
			else 
			echo $lang["document"][7].": ".$this->fields["ID"];
			
			echo "</th><th  align='center'>".$datestring.$date;
			echo "</th></tr>";
	
		echo "<tr class='tab_bg_1'><td>".$lang["common"][16].":	</td><td>";
		autocompletionTextField("name","glpi_type_docs","name",$this->fields["name"],20);
		echo "</td></tr>";
	
		echo "<tr class='tab_bg_1'><td>".$lang["document"][9].":	</td><td>";
		autocompletionTextField("ext","glpi_type_docs","ext",$this->fields["ext"],20);
	
		echo "</td></tr>";
	
		echo "<tr class='tab_bg_1'><td>".$lang["document"][10].":	</td><td>";
		dropdownIcons("icon",$this->fields["icon"],$phproot."/".$cfg_glpi["typedoc_icon_dir"]);
		if (!empty($this->fields["icon"])) echo "&nbsp;<img style='vertical-align:middle;' alt='' src='".$HTMLRel.$cfg_glpi["typedoc_icon_dir"]."/".$this->fields["icon"]."'>";
		echo "</td></tr>";
	
		echo "<tr class='tab_bg_1'><td>".$lang["document"][4].":	</td><td>";
		autocompletionTextField("mime","glpi_type_docs","mime",$this->fields["mime"],20);
		echo "</td></tr>";
	
		echo "<tr class='tab_bg_1'><td>".$lang["document"][11].":	</td><td>";
		if (empty($this->fields["upload"])) $this->fields["upload"]='Y';
		dropdownYesNo("upload",$this->fields["upload"]);
		echo "</td></tr>";

		if (haveRight("typedoc","w")) {
			echo "<tr>";
			if(empty($ID)){
		
				echo "<td class='tab_bg_2' valign='top' colspan='3'>";
				echo "<div align='center'><input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'></div>";
				echo "</td>";
				
			} else {
			
				echo "<td class='tab_bg_2' valign='top' align='center'>";
				echo "<input type='hidden' name='ID' value=\"$ID\">\n";
				echo "<input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'>";
				echo "</td>";
				echo "<td class='tab_bg_2' valign='top'>\n";
				echo "<div align='center'>";
				echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
				echo "</div>";
				echo "</td>";
			}
			echo "</tr>";
		}
	
			echo "</table></form></div>";
		
			return true;	
		}
		else {
			echo "<div align='center'><b>".$lang["document"][23]."</b></div>";
			return false;
		}
	
	}
	
}

?>
