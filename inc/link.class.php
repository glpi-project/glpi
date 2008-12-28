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


// CLASSES link
class Link extends CommonDBTM {

	/**
	 * Constructor
	**/
	function __construct () {
		$this->table="glpi_links";
		$this->type=LINK_TYPE;
		$this->may_be_recursive=true;
		$this->entity_assign=true;
	}


	function defineTabs($ID,$withtemplate){
		global $LANG;

		$ong=array();
		return $ong;
	}

	function cleanDBonPurge($ID) {

		global $DB;

		$query2="DELETE FROM glpi_links_device WHERE FK_links='$ID'";
		$DB->query($query2);
	}

	/**
	 * Print the link form
	 *
	 *
	 * Print g��al link form
	 *
	 *@param $target filename : where to go when done.
	 *@param $ID Integer : Id of the link to print
	 *
	 *
	 *@return Nothing (display)
	 *
	 **/
	function showForm ($target,$ID) {

		global $CFG_GLPI, $LANG;

		if (!haveRight("link","r")) return false;

/*
		if ($ID > 0){
			$this->check($ID,'r');
		} else {
			// Create item 
			$this->check(-1,'w');
			$use_cache=false;
			$this->getEmpty();
		} 
*/
		if ($ID > 0){
			$this->check($ID,'r');
		} else {
			// Create item 
			$this->check(-1,'w');
			$use_cache=false;
			$this->getEmpty();
		} 

		$canedit=$this->can($ID,'w');
		$canrecu=$this->can($ID,'recursive');

		$this->showTabs($ID, '',$_SESSION['glpi_tab']);
		
		echo "<form method='post' name=form action=\"$target\"><div class='center'>";

		echo "<table class='tab_cadre_fixe' cellpadding='2' >";
		
		$this->showFormHeader($ID, '');

		echo "<tr class='tab_bg_1'><td>".$LANG["links"][6].":	</td>";
		echo "<td>[LOGIN], [ID], [NAME], [LOCATION], [LOCATIONID], [IP], [MAC], [NETWORK], [DOMAIN], [SERIAL], [OTHERSERIAL], [USER]</td>";
		echo "</tr>";

		echo "<tr class='tab_bg_1'><td>".$LANG["common"][16].":	</td>";
		echo "<td>";
		autocompletionTextField("name","glpi_links","name",$this->fields["name"],80);		
		echo "</td></tr>";

		echo "<tr class='tab_bg_1'><td>".$LANG["links"][1].":	</td>";
		echo "<td>";
		autocompletionTextField("link","glpi_links","link",$this->fields["link"],80);		
		echo "</td></tr>";

		echo "<tr class='tab_bg_1'><td>".$LANG["links"][9].":	</td>";
		echo "<td>";
		echo "<textarea name='data' rows='10' cols='80'>".$this->fields["data"]."</textarea>";
		echo "</td></tr>";

		echo "<input type='hidden' name='FK_entities' value='".$this->fields["FK_entities"]."'>";


		if ($canedit)
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
				echo "<input type='hidden' name='ID' value=\"$ID\">\n";
				echo "<div class='center'><input type='submit' name='delete' value=\"".$LANG["buttons"][6]."\" class='submit' ></div>";
				echo "</td>";
				echo "</tr>";



			}
		echo "</table></div></form>";
		return true;
	}

}

?>
