<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2007 by the INDEPNET Development Team.

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


class EntityData extends CommonDBTM{

	function EntityData () {
		$this->table="glpi_entities_data";
		$this->type=-1;
	}
	function getIndexName(){
		return "FK_entities";
	}

}


// CLASSES entity
class Entity extends CommonDBTM{

	function Entity () {
		$this->table="glpi_entities";
		$this->type=ENTITY_TYPE;
	}
	function defineOnglets($withtemplate){
		global $LANG;

		$ong[1]=$LANG["title"][26];
		$ong[2]=$LANG["Menu"][14];
		$ong[3]=$LANG["rulesengine"][17];
		return $ong;
	}

	/**
	 * Print a good title for coontact pages
	 *
	 *
	 *
	 *
	 *@return nothing (diplays)
	 *
	 **/
	function title(){
		global  $LANG,$CFG_GLPI;

		$buttons=array();
		$title=$LANG["Menu"][37];
		if (haveRight("entity","w")){
			$buttons["entity.tree.php"]=$LANG["entity"][1];
			$title="";
		}
		$buttons["entity.form.php?ID=0"]=$LANG["entity"][2];
		
		displayTitle($CFG_GLPI["root_doc"]."/pics/groupes.png",$LANG["Menu"][37],$title,$buttons);
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

		if (!haveRight("entity","r")) return false;

		$con_spotted=false;

		if (empty($ID)&&$ID!=0) {
			if($this->getEmpty()) $con_spotted = true;
		} else {
			if ($ID==0) {
				$con_spotted=true;
				$this->fields["name"]=$LANG["entity"][2];
				$this->fields["completename"]="";
			}
			else if($this->getfromDB($ID)) $con_spotted = true;
		}

		if ($con_spotted){
			// Get data
			$entdata=new EntityData();

			if (!$entdata->getFromDB($ID)){
				$entdata->add(array("FK_entities"=>$ID));
				$entdata->getFromDB($ID);	
			}

			$this->showOnglets($ID, $withtemplate,$_SESSION['glpi_onglet']);

			echo "<form method='post' name=form action=\"$target\"><div class='center'>";

			echo "<table class='tab_cadre_fixe' cellpadding='2' >";
			echo "<tr><th colspan='4'>";
			echo $LANG["entity"][0]." ID $ID:";

			echo "</th></tr>";

			echo "<tr class='tab_bg_1'>";

			echo "<td valign='top'>".$LANG["common"][16].":	</td>";
			echo "<td valign='top'>";
			echo $this->fields["name"];
			if ($ID!=0) echo " (".$this->fields["completename"].")";
			echo "</td>";
			if (isset($this->fields["comments"])){
				echo "<td valign='top'>";
				echo $LANG["common"][25].":	</td>";
				echo "<td align='center' valign='top'>".nl2br($this->fields["comments"]);
				echo "</td>";
			} else {
				echo "<td colspan='2'>&nbsp;</td>";
			}
			echo "</tr>";

			echo "<tr class='tab_bg_1'><td>".$LANG["financial"][29].":		</td>";
			echo "<td>";
			autocompletionTextField("phonenumber","glpi_entities_data","phonenumber",$entdata->fields["phonenumber"],25);	
			echo "</td>";
			echo "<td>".$LANG["financial"][30].":		</td><td>";
			autocompletionTextField("fax","glpi_entities_data","fax",$entdata->fields["fax"],25);	
			echo "</td></tr>";
	
			echo "<tr class='tab_bg_1'><td>".$LANG["financial"][45].":		</td>";
			echo "<td>";
			autocompletionTextField("website","glpi_entities_data","website",$entdata->fields["website"],25);	
			echo "</td>";
	
			echo "<td>".$LANG["setup"][14].":		</td><td>";
			autocompletionTextField("email","glpi_entities_data","email",$entdata->fields["email"],25);		
			echo "</td></tr>";
	
	
			echo "<tr class='tab_bg_1'><td  rowspan='4'>".$LANG["financial"][44].":		</td>";
			echo "<td align='center' rowspan='4'><textarea cols='35' rows='4' name='address' >".$entdata->fields["address"]."</textarea>";
			echo "<td>".$LANG["financial"][100]."</td>";
			echo "<td>";
			autocompletionTextField("postcode","glpi_entities_data","postcode",$entdata->fields["postcode"],25);		
			echo "</td>";
			echo "</tr>";
	
			echo "<tr class='tab_bg_1'>";
			echo "<td>".$LANG["financial"][101].":		</td><td>";
			autocompletionTextField("town","glpi_entities_data","town",$entdata->fields["town"],25);		
			echo "</td></tr>";
	
			echo "<tr class='tab_bg_1'>";
			echo "<td>".$LANG["financial"][102].":		</td><td>";
			autocompletionTextField("state","glpi_entities_data","state",$entdata->fields["state"],25);		
			echo "</td></tr>";
	
			echo "<tr class='tab_bg_1'>";
			echo "<td>".$LANG["financial"][103].":		</td><td>";
			autocompletionTextField("country","glpi_entities_data","country",$entdata->fields["country"],25);		
			echo "</td></tr>";



			if (haveRight("entity","w")) {
				echo "<tr>";
				echo "<td class='tab_bg_2' colspan='4' valign='top' align='center'>";
				echo "<input type='hidden' name='ID' value=\"$ID\">\n";
				echo "<input type='submit' name='update' value=\"".$LANG["buttons"][7]."\" class='submit' >";
				echo "</td>\n\n";
				echo "</tr>";

			}

			echo "</table></div></form>";

		} else {
			echo "<div class='center'><strong>".$LANG["common"][54]."</strong></div>";
			return false;

		}
		return true;
	}


}

?>
