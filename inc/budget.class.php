<?php

/*
 * @version $Id: bookmark.class.php 8095 2009-03-19 18:27:00Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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

if (!defined('GLPI_ROOT')) {
	die("Sorry. You can't access directly to this file");
}


class Budget extends CommonDBTM{

	/**
	 * Constructor
	**/
	function __construct () {
		$this->table="glpi_budgets";
		$this->type=BUDGET_TYPE;
		$this->entity_assign = true;
		$this->may_be_recursive = true;
		$this->dohistory=true;
	}

	function defineTabs($ID,$withtemplate){
		global $LANG;
		$ong=array();
		$ong[1]=$LANG['title'][26];


		if ($ID>0){
			$ong[2]=$LANG['common'][1];

			if (haveRight("document","r"))	
				$ong[5]=$LANG['Menu'][27];
			if (haveRight("link","r"))	
				$ong[7]=$LANG['title'][34];
			if (haveRight("notes","r"))
				$ong[10]=$LANG['title'][37];
		}
		return $ong;
	}

	/**
	 * Print the contact form
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

		if (!haveRight("budget","r")) return false;

		$use_cache=true;

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
			if (empty($ID)||$ID<0){
				echo "<input type='hidden' name='FK_entities' value='".$_SESSION["glpiactive_entity"]."'>";
			}
		}

      echo "<div class='center' id='tabsbody'><table class='tab_cadre_fixe' cellpadding='2' >";
		$this->showFormHeader($ID);
		
				echo "<tr class='tab_bg_1'><td class='tab_bg_1' valign='top'>";
            echo "<table cellpadding='1' cellspacing='0' border='0'>\n";
            echo "<tr>";

				echo "<td>".$LANG['common'][16].":	</td>";
				echo "<td>";
				autocompletionTextField("name","glpi_budgets","name",$this->fields["name"],40,$this->fields["FK_entities"]);	
				echo "</td></tr>";

				echo "<tr class='tab_bg_1'><td>".$LANG['financial'][21]."</td><td>";
				echo "<input type='text' name='value' value=\"".formatNumber($this->fields["value"],true)."\" size='10'>";
				echo "</td></tr>";
				
				echo "<tr class='tab_bg_1'>";
				echo "<td>".$LANG['search'][8].":	</td>";
				echo "<td>";
				showDateFormItem("startdate",$this->fields["startdate"]);
				echo "</td></tr>";
	
				echo "<tr class='tab_bg_1'><td>".$LANG['search'][9].":	</td>";
				echo "<td>";
				showDateFormItem("enddate",$this->fields["enddate"]);
				echo "</td></tr>";
            echo "</table>";

  				echo "<td class='tab_bg_1' valign='top'>";
            echo "<table cellpadding='1' cellspacing='0' border='0'>\n";
            echo "<tr>";

				echo "<tr class='tab_bg_1'><td>";
				echo $LANG['common'][25].":	</td>";
				echo "<td class='center'><textarea cols='45' rows='4' name='comments' >".$this->fields["comments"]."</textarea>";
				echo "</td><td colspan='2'></td>";
            echo "</td></tr></table>";

            echo "</td>";
            echo "</tr>";

		if ($canedit) {
			
			echo "<tr>";
			
			if ($ID>0){
				
				echo "<td class='tab_bg_2' valign='top'>";
				echo "<input type='hidden' name='ID' value=\"$ID\">\n";
				echo "<div class='center'><input type='submit' name='update' value=\"".$LANG['buttons'][7]."\" class='submit' ></div>";
				echo "</td>\n\n";
				echo "<td class='tab_bg_2' valign='top'>\n";
				if (!$this->fields["deleted"])
					echo "<div class='center'><input type='submit' name='delete' value=\"".$LANG['buttons'][6]."\" class='submit'></div>";
				else {
					echo "<div class='center'><input type='submit' name='restore' value=\"".$LANG['buttons'][21]."\" class='submit'>";

					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$LANG['buttons'][22]."\" class='submit'></div>";
				}
				echo "</td>";

			} else {

				echo "<td class='tab_bg_2' valign='top' colspan='2'>";
				echo "<div class='center'><input type='submit' name='add' value=\"".$LANG['buttons'][8]."\" class='submit'></div>";
				echo "</td>";

			}
			echo "</tr>";
			echo "</table></div></form>";
			
		}else { // canedit
			echo "</table></div>";
		}

		echo "<div id='tabcontent'></div>";
		echo "<script type='text/javascript'>loadDefaultTab();</script>";
			
		return true;
	}

}

?>
