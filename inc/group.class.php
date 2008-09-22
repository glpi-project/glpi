<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

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

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}


// CLASSES contact
class Group extends CommonDBTM{

	/**
	 * Constructor
	**/
	function __construct () {
		$this->table="glpi_groups";
		$this->type=GROUP_TYPE;
		$this->entity_assign=true;
		$this->may_be_recursive=true;
	}

	function cleanDBonPurge($ID) {
		global $DB,$CFG_GLPI,$LINK_ID_TABLE;

		$query = "DELETE from glpi_users_groups WHERE FK_groups = '$ID'";
		$DB->query($query);

	}

	function post_getEmpty () {
		global $CFG_GLPI;
		//$this->fields["ldap_field"]=$CFG_GLPI["ldap_field_group"];
	}


	function defineTabs($withtemplate){
		global $LANG;
		if (haveRight("user","r"))	
			$ong[1]=$LANG["Menu"][14];

		$ong[2]=$LANG["common"][1];
		return $ong;
	}

	/**
	 * Print the group form
	 *
	 *
	 * Print group form
	 *
	 *@param $target filename : where to go when done.
	 *@param $ID Integer : Id of the contact to print
	 *@param $withtemplate='' boolean : template or basic item
	 *
	 *
	 *@return Nothing (display)
	 *
	 **/
	function showForm ($target,$ID,$withtemplate='') {

		global $CFG_GLPI, $LANG;

		if (!haveRight("group","r")) return false;


		if ($ID > 0){
			$this->check($ID,'r');
		} else {
			// Create item 
			$this->check(-1,'w');
			$use_cache=false;
			$this->getEmpty();
		} 


		$canedit=$this->can($ID,'w');

		$this->showTabs($ID, $withtemplate,$_SESSION['glpi_tab']);

		if ($canedit) {
			
			echo "<form method='post' name=form action=\"$target\">";
			if (empty($ID)){
				echo "<input type='hidden' name='FK_entities' value='".$_SESSION["glpiactive_entity"]."'>";
			}
		}
		echo "<div class='center' id='tabsbody' >";
		echo "<table class='tab_cadre_fixe' cellpadding='2' >";
		echo "<tr><th>";
		if (empty($ID)) {
			echo $LANG["setup"][605];

		} else {
			echo $LANG["common"][2]." ".$this->fields["ID"];
		}		
		if (isMultiEntitiesMode()){
			echo "&nbsp;(".getDropdownName("glpi_entities",$this->fields["FK_entities"]).")";
		}
		echo "</th><th>";
		if (isMultiEntitiesMode()){
			echo $LANG["entity"][9].":&nbsp;";
		
			if ($this->can($ID,'recursive')) {
				dropdownYesNo("recursive",$this->fields["recursive"]);					
			} else {
				echo getYesNo($this->fields["recursive"]);
			}
		} else {
			echo "&nbsp;";
		}
		echo "</th></tr>";

		echo "<tr><td class='tab_bg_1' valign='top'>";

		echo "<table cellpadding='1' cellspacing='0' border='0'>\n";

		echo "<tr><td>".$LANG["common"][16].":	</td>";
		echo "<td>";
		autocompletionTextField("name","glpi_groups","name",$this->fields["name"],40,$this->fields["FK_entities"]);	
		echo "</td></tr>";

		echo "<tr><td>".$LANG["common"][64].":	</td>";
		echo "<td>";
		// Manager must be in the same entity
		// TODO for a recursive group the manager need to have a recursive right ?
		dropdownUsers('FK_users',$this->fields["FK_users"],'all',0,1,$this->fields["FK_entities"]);
		echo "</td></tr>";

		if(useAuthLdap()){
			echo "<tr><td colspan='2' align='center'>".$LANG["setup"][256].":	</td>";
			echo "</tr>";

			echo "<tr><td>".$LANG["setup"][260].":	</td>";
			echo "<td>";
			autocompletionTextField("ldap_field","glpi_groups","ldap_field",$this->fields["ldap_field"],40,$this->fields["FK_entities"]);
			echo "</td></tr>";

			echo "<tr><td>".$LANG["setup"][601].":	</td>";
			echo "<td>";
			autocompletionTextField("ldap_value","glpi_groups","ldap_value",$this->fields["ldap_value"],40,$this->fields["FK_entities"]);
			echo "</td></tr>";

			echo "<tr><td colspan='2' align='center'>".$LANG["setup"][257].":	</td>";
			echo "</tr>";


			echo "<tr><td>".$LANG["setup"][261].":	</td>";
			echo "<td>";
			autocompletionTextField("ldap_group_dn","glpi_groups","ldap_group_dn",$this->fields["ldap_group_dn"],40,$this->fields["FK_entities"]);
			echo "</td></tr>";
		}

		echo "</table>";

		echo "</td>\n";	

		echo "<td class='tab_bg_1' valign='top'>";

		echo "<table cellpadding='1' cellspacing='0' border='0'><tr><td>";
		echo $LANG["common"][25].":	</td></tr>";
		echo "<tr><td class='center'><textarea cols='45' rows='4' name='comments' >".$this->fields["comments"]."</textarea>";
		echo "</td></tr></table>";

		echo "</td>";
		echo "</tr>";

		if ($canedit) {
			if ($ID=="") {

				echo "<tr>";
				echo "<td class='tab_bg_2' valign='top' colspan='2'>";
				echo "<div class='center'><input type='submit' name='add' value=\"".$LANG["buttons"][8]."\" class='submit'></div>";
				echo "</td>";
				echo "</tr>";

			} else {

				echo "<tr>";
				echo "<td class='tab_bg_2' valign='top'>";
				echo "<input type='hidden' name='ID' value=\"$ID\">\n";
				echo "<div class='center'><input type='submit' name='update' value=\"".$LANG["buttons"][7]."\" class='submit' ></div>";
				echo "</td>\n\n";
				echo "<td class='tab_bg_2' valign='top'>\n";
				echo "<div class='center'><input type='submit' name='delete' value=\"".$LANG["buttons"][6]."\" class='submit'></div>";

				echo "</td>";
				echo "</tr>";

			}
			echo "</table></div></form>";
		} else {
			echo "</table></div>";
		}
		echo "<div id='tabcontent'></div>";
		echo "<script type='text/javascript'>loadDefaultTab();</script>";
		
		return true;
	}


	/**
	 * Print a good title for group pages
	 *
	 *@return nothing (display)
	 **/
	function title() {
		global $LANG, $CFG_GLPI;

		$buttons = array ();
		if (haveRight("group", "w") && haveRight("user_auth_method", "w") && useAuthLdap()) {
			$buttons["ldap.group.php"] = $LANG["setup"][3];
			$title="";
		} else {
			$title = $LANG["Menu"][36];		
		}

		displayTitle($CFG_GLPI["root_doc"] . "/pics/groupes.png", $LANG["Menu"][36], $title, $buttons);
	}

}

?>
