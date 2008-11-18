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


/// Entity Data class
class EntityData extends CommonDBTM{

	/**
	 * Constructor
	**/
	function __construct () {
		$this->table="glpi_entities_data";
		$this->type=-1;
	}
	function getIndexName(){
		return "FK_entities";
	}

}


/// Entity class
class Entity extends CommonDBTM{

	/**
	 * Constructor
	**/
	function __construct () {
		$this->table="glpi_entities";
		$this->type=ENTITY_TYPE;
		$this->entity_assign=true;
		$this->may_be_recursive=true;
	}
	
	function defineTabs($ID,$withtemplate){
		global $LANG;

		$ong[1]=$LANG["title"][26];
		$ong[2]=$LANG["Menu"][14];
		$ong[3]=$LANG["rulesengine"][17];

		return $ong;
	}

	/**
	 * Print a good title for entity pages
	 *
	 *@return nothing (display)
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
	 * Print the entity form
	 *
	 *
	 * Print entity form
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

		if ($ID > 0){
			$this->check($ID,'r');
		} else {
			// Create item 
			$this->check(-1,'w');
			$use_cache=false;
			$this->getEmpty();

			// Special root entity case	
			if ($ID==0) {
				$this->fields["name"]=$LANG["entity"][2];
				$this->fields["completename"]="";
			}
		} 

		
		// Get data 
		$entdata=new EntityData();
		if (!$entdata->getFromDB($ID)){
			$entdata->add(array("FK_entities"=>$ID)); 
			if (!$entdata->getFromDB($ID)){
				$con_spotted=false;
			}
		}
		
		$canedit=$this->can($ID,'w');
		
		$this->showTabs($ID, $withtemplate,$_SESSION['glpi_tab']);
		
		if ($canedit) {
			echo "<form method='post' name=form action=\"$target\">";
		}
		echo "<div class='center' id='tabsbody' >";
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

		echo "<tr class='tab_bg_1'><td>".$LANG["help"][35].":		</td>";
		echo "<td>";
		autocompletionTextField("phonenumber","glpi_entities_data","phonenumber",$entdata->fields["phonenumber"],40);	
		echo "</td>";
		echo "<td>".$LANG["financial"][30].":		</td><td>";
		autocompletionTextField("fax","glpi_entities_data","fax",$entdata->fields["fax"],40);	
		echo "</td></tr>";

		echo "<tr class='tab_bg_1'><td>".$LANG["financial"][45].":		</td>";
		echo "<td>";
		autocompletionTextField("website","glpi_entities_data","website",$entdata->fields["website"],40);	
		echo "</td>";

		echo "<td>".$LANG["setup"][14].":		</td><td>";
		autocompletionTextField("email","glpi_entities_data","email",$entdata->fields["email"],40);		
		echo "</td></tr>";


		echo "<tr class='tab_bg_1'><td  rowspan='4'>".$LANG["financial"][44].":		</td>";
		echo "<td align='center' rowspan='4'><textarea cols='35' rows='4' name='address' >".$entdata->fields["address"]."</textarea>";
		echo "<td>".$LANG["financial"][100]."</td>";
		echo "<td>";
		autocompletionTextField("postcode","glpi_entities_data","postcode",$entdata->fields["postcode"],40);		
		echo "</td>";
		echo "</tr>";

		echo "<tr class='tab_bg_1'>";
		echo "<td>".$LANG["financial"][101].":		</td><td>";
		autocompletionTextField("town","glpi_entities_data","town",$entdata->fields["town"],40);		
		echo "</td></tr>";

		echo "<tr class='tab_bg_1'>";
		echo "<td>".$LANG["financial"][102].":		</td><td>";
		autocompletionTextField("state","glpi_entities_data","state",$entdata->fields["state"],40);		
		echo "</td></tr>";

		echo "<tr class='tab_bg_1'>";
		echo "<td>".$LANG["financial"][103].":		</td><td>";
		autocompletionTextField("country","glpi_entities_data","country",$entdata->fields["country"],40);		
		echo "</td></tr>";

		echo "<tr class='tab_bg_1'>";
		echo "<td>".$LANG["setup"][203].":		</td><td colspan='3'>";
		autocompletionTextField("admin_email","glpi_entities_data","admin_email",$entdata->fields["admin_email"],50);		
		echo "</td></tr>";

		echo "<tr class='tab_bg_1'>";
		echo "<td>".$LANG["setup"][207].":		</td><td colspan='3'>";
		autocompletionTextField("admin_reply","glpi_entities_data","admin_reply",$entdata->fields["admin_reply"],50);		
		echo "</td></tr>";


		if ($canedit) {
			echo "<tr>";
			echo "<td class='tab_bg_2' colspan='4' valign='top' align='center'>";
			echo "<input type='hidden' name='FK_entities' value=\"$ID\">\n";
			echo "<input type='hidden' name='ID' value=\"".$entdata->fields["ID"]."\">\n";
			echo "<input type='submit' name='update' value=\"".$LANG["buttons"][7]."\" class='submit' >";
			echo "</td>\n\n";
			echo "</tr>";

			echo "</table></div></form>";
		} else {
			echo "</table></div>";			
		}

		
		echo "<div id='tabcontent'></div>";
		echo "<script type='text/javascript'>loadDefaultTab();</script>";

		return true;
	}

	/**
	 * Get the ID of entity assigned to the object
	 * 
	 * simply return ID
	 * 
	 * @return ID of the entity 
	**/
	function getEntityID () {
		if (isset($this->fields["ID"])) {
			return $this->fields["ID"];		
		} 
		return  -1;
	}	
	/**
	 * Is the object recursive
	 * 
	 * Entity are always recursive
	 * 
	 * @return integer (0/1) 
	**/
	function isRecursive () {
		return true;
	}	
}

?>
