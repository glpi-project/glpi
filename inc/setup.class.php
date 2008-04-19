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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}

class SetupSearchDisplay extends CommonDBTM{

	/**
	 * Constructor
	**/
	function SetupSearchDisplay () {
		$this->table="glpi_display";
		$this->type=-1;
	}

	function prepareInputForAdd($input) {
		global $DB;
		$query="SELECT MAX(rank) FROM glpi_display WHERE type='".$input["type"]."' AND FK_users='".$input["FK_users"]."'";
		$result=$DB->query($query);
		$input["rank"]=$DB->result($result,0,0)+1;

		return $input;
	}

	function activatePerso($input){
		global $DB,$SEARCH_OPTION;

		if (!haveRight("search_config","w")) return false;

		$query="SELECT * FROM glpi_display WHERE type='".$input["type"]."' AND FK_users='0'";
		$result=$DB->query($query);
		if ($DB->numrows($result)){
			while ($data=$DB->fetch_assoc($result)){
				unset($data["ID"]);
				$data["FK_users"]=$input["FK_users"];
				$this->fields=$data;
				$this->addToDB();
			}
		} else {
			// No items in the global config
			if (count($SEARCH_OPTION[$input["type"]])>1){
				$done=false;
				foreach($SEARCH_OPTION[$input["type"]] as $key => $val)
					if (is_array($val)&&$key!=1&&!$done){
						$data["FK_users"]=$input["FK_users"];
						$data["type"]=$input["type"];
						$data["type"]=$input["type"];
						$data["rank"]=1;
						$data["num"]=$key;
						$this->fields=$data;
						$this->addToDB();
						$done=true;
					}


			}

		}

	}

	function up($input){
		global $DB;
		// Get current item
		$query="SELECT rank FROM glpi_display WHERE ID='".$input['ID']."';";
		$result=$DB->query($query);
		$rank1=$DB->result($result,0,0);
		// Get previous item
		$query="SELECT ID,rank FROM glpi_display WHERE type='".$input['type']."' AND FK_users='".$input["FK_users"]."' AND rank<'$rank1' ORDER BY rank DESC;";
		$result=$DB->query($query);
		$rank2=$DB->result($result,0,"rank");
		$ID2=$DB->result($result,0,"ID");
		// Update items
		$query="UPDATE glpi_display SET rank='$rank2' WHERE ID ='".$input['ID']."'";
		$DB->query($query);
		$query="UPDATE glpi_display SET rank='$rank1' WHERE ID ='$ID2'";
		$DB->query($query);
	}

	function down($input){
		global $DB;

		// Get current item
		$query="SELECT rank FROM glpi_display WHERE ID='".$input['ID']."';";
		$result=$DB->query($query);
		$rank1=$DB->result($result,0,0);
		// Get next item
		$query="SELECT ID,rank FROM glpi_display WHERE type='".$input['type']."' AND FK_users='".$input["FK_users"]."' AND rank>'$rank1' ORDER BY rank ASC;";
		$result=$DB->query($query);
		$rank2=$DB->result($result,0,"rank");
		$ID2=$DB->result($result,0,"ID");
		// Update items
		$query="UPDATE glpi_display SET rank='$rank2' WHERE ID ='".$input['ID']."'";
		$DB->query($query);
		$query="UPDATE glpi_display SET rank='$rank1' WHERE ID ='$ID2'";
		$DB->query($query);
	}

/*	function title($target,$type){
		global $LANG,$CFG_GLPI;

		$dp=array();
		$state=false;
		if (haveRight("computer","r")){
			$state=true;
			$dp[COMPUTER_TYPE]=$LANG["Menu"][0];
		}
		if (haveRight("networking","r")){
			$state=true;
			$dp[NETWORKING_TYPE]=$LANG["Menu"][1];
		}
		if (haveRight("printer","r")){
			$state=true;
			$dp[PRINTER_TYPE]=$LANG["Menu"][2];
		}
		if (haveRight("monitor","r")){
			$state=true;
			$dp[MONITOR_TYPE]=$LANG["Menu"][3];
		}
		if (haveRight("peripheral","r")){
			$state=true;
			$dp[PERIPHERAL_TYPE]=$LANG["Menu"][16];
		}
		if (haveRight("software","r")){
			$state=true;
			$dp[SOFTWARE_TYPE]=$LANG["Menu"][4];
		}
		if ($state){
			$dp[STATE_TYPE]=$LANG["Menu"][28];
		}
		if (haveRight("reservation_central","r")){
			$dp[RESERVATION_TYPE]=$LANG["Menu"][17];
		}
		if (haveRight("contact_enterprise","r")){
			$dp[CONTACT_TYPE]=$LANG["Menu"][22];
			$dp[ENTERPRISE_TYPE]=$LANG["Menu"][23];
		}
		if (haveRight("contract_infocom","r")){
			$dp[CONTRACT_TYPE]=$LANG["Menu"][25];
		}
		if (haveRight("typedoc","r")){
			$dp[TYPEDOC_TYPE]=$LANG["document"][7];
		}
		if (haveRight("document","r")){
			$dp[DOCUMENT_TYPE]=$LANG["Menu"][27];
		}
		if (haveRight("user","r")){
			$dp[USER_TYPE]=$LANG["Menu"][14];
		}
		if (haveRight("entity","r")){
			$dp[ENTITY_TYPE]=$LANG["Menu"][37];
		}
		if (haveRight("consumable","r")){
			$dp[CONSUMABLE_TYPE]=$LANG["Menu"][32];
		}
		if (haveRight("cartridge","r")){
			$dp[CARTRIDGE_TYPE]=$LANG["Menu"][21];
		}
		if (haveRight("link","r")){
			$dp[LINK_TYPE]=$LANG["setup"][87];
		}
		if (haveRight("phone","r")){
			$dp[PHONE_TYPE]=$LANG["Menu"][34];
		}
		if (haveRight("group","r")){
			$dp[GROUP_TYPE]=$LANG["Menu"][36];
		}
		if (haveRight("profile","r")){
			$dp[PROFILE_TYPE]=$LANG["Menu"][35];
		}
		if (haveRight("config","r")){
			$dp[MAILGATE_TYPE]=$LANG["Menu"][39];
		}
		if (!$type){
			$type=key($dp);
		}

		if (count($dp)){
			asort($dp);
			echo "<div class='center'><form method='post' action=\"$target\">";
			echo "<table class='tab_cadre' cellpadding='5'><tr><th colspan='2'>";
			echo $LANG["setup"][251].": </th></tr><tr class='tab_bg_1'><td><select name='type'>";
	
	
			foreach ($dp as $key => $val){
				$sel="";
				if ($type==$key) $sel="selected";
				echo "<option value='$key' $sel>".$val."</option>";
			}
	
			echo "</select></td>";
			echo "<td><input type='submit' value=\"".$LANG["buttons"][2]."\" class='submit' ></td></tr>";
			echo "</table></form></div>";
	
			return $type;
		} else return false;
	}
*/
	function showForm($target,$type){
		global $SEARCH_OPTION,$CFG_GLPI,$LANG,$DB;

		if (!isset($SEARCH_OPTION[$type])) {
			return false;
		}
		$is_global=($_SESSION['glpi_searchconfig']==1);
		if ($is_global) $IDuser=0;
		else $IDuser=$_SESSION["glpiID"];

		$global_write=haveRight("search_config_global","w");

		echo "<div id='barre_onglets'><ul id='onglet'>";
		echo "<li "; if ($_SESSION['glpi_searchconfig']==1){ echo "class='actif'";} echo  "><a href='$target?onglet=1&amp;type=$type'>".$LANG["central"][13]."</a></li>";
		if (haveRight("search_config","w")){
			echo "<li "; if ($_SESSION['glpi_searchconfig']==2){ echo "class='actif'";} echo  "><a href='$target?onglet=2&amp;type=$type'>".$LANG["central"][12]."</a></li>";
		}
		echo "</ul></div>";

		echo "<div class='center'>";
		// Defined items
		$query="SELECT * from glpi_display WHERE type='$type' AND FK_users='$IDuser' order by rank";

		$result=$DB->query($query);
		$numrows=0;
		$numrows=$DB->numrows($result);
		if ($numrows==0&&!$is_global){
			checkRight("search_config","w");
			echo "<table class='tab_cadre_fixe' cellpadding='2' ><tr><th colspan='4'>";
			echo "<form method='post' action=\"$target\">";
			echo "<input type='hidden' name='type' value='$type'>";
			echo "<input type='hidden' name='FK_users' value='$IDuser'>";

			echo $LANG["setup"][241];
			echo "&nbsp;&nbsp;&nbsp;<input type='submit' name='activate' value=\"".$LANG["buttons"][2]."\" class='submit' >";
			echo "</form></th></tr></table>";

		} else {

			echo "<table class='tab_cadre_fixe' cellpadding='2' ><tr><th colspan='4'>";
			echo $LANG["setup"][252].": </th></tr>";
			if (!$is_global||$global_write){
				echo "<tr class='tab_bg_1'><td colspan='4' align='center'>";
				echo "<form method='post' action=\"$target\">";
				echo "<input type='hidden' name='type' value='$type'>";
				echo "<input type='hidden' name='FK_users' value='$IDuser'>";
				echo "<select name='num'>";
				$first_group=true;
				$searchopt=cleanSearchOption($type);
				foreach ($searchopt as $key => $val)
					if (!is_array($val)){
						if (!$first_group) echo "</optgroup>";
						else $first_group=false;
						echo "<optgroup label=\"".$val."\">";
					} else 	if ($key!=1){
						echo "<option value='$key'>".$val["name"]."</option>";
					}
				if (!$first_group) echo "</optgroup>";

				echo "</select>";
				echo "&nbsp;&nbsp;&nbsp;<input type='submit' name='add' value=\"".$LANG["buttons"][8]."\" class='submit' >";
				echo "</form>";
				echo "</td></tr>";
			}


			// print first element 
			echo "<tr class='tab_bg_2'><td  align='center' width='50%'>";
			echo $SEARCH_OPTION[$type][1]["name"];


			if (!$is_global||$global_write)
				echo "</td><td colspan='3'>&nbsp;</td>";
			echo "</tr>";
			$i=0;
			if ($numrows){
				while ($data=$DB->fetch_array($result)){
					if ($data["num"]!=1){
						echo "<tr class='tab_bg_2'><td align='center' width='50%' >";
						echo $SEARCH_OPTION[$type][$data["num"]]["name"];
						echo "</td>";
						if (!$is_global||$global_write){
							if ($i!=0){
								echo "<td align='center' valign='middle'>";
								echo "<form method='post' action=\"$target\">";
								echo "<input type='hidden' name='ID' value='".$data["ID"]."'>";
								echo "<input type='hidden' name='FK_users' value='$IDuser'>";

								echo "<input type='hidden' name='type' value='$type'>";
								echo "<input type='image' name='up'  value=\"".$LANG["buttons"][24]."\"  src=\"".$CFG_GLPI["root_doc"]."/pics/puce-up2.png\" alt=\"".$LANG["buttons"][24]."\"  title=\"".$LANG["buttons"][24]."\" >";	
								echo "</form>";
								echo "</td>";
							} else echo "<td>&nbsp;</td>";
							if ($i!=$numrows-1){
								echo "<td align='center' valign='middle'>";
								echo "<form method='post' action=\"$target\">";
								echo "<input type='hidden' name='ID' value='".$data["ID"]."'>";
								echo "<input type='hidden' name='FK_users' value='$IDuser'>";

								echo "<input type='hidden' name='type' value='$type'>";
								echo "<input type='image' name='down' value=\"".$LANG["buttons"][25]."\" src=\"".$CFG_GLPI["root_doc"]."/pics/puce-down2.png\" alt=\"".$LANG["buttons"][25]."\"  title=\"".$LANG["buttons"][25]."\" >";	
								echo "</form>";
								echo "</td>";
							} else echo "<td>&nbsp;</td>";

							echo "<td align='center' valign='middle'>";
							echo "<form method='post' action=\"$target\">";
							echo "<input type='hidden' name='ID' value='".$data["ID"]."'>";
							echo "<input type='hidden' name='FK_users' value='$IDuser'>";

							echo "<input type='hidden' name='type' value='$type'>";
							echo "<input type='image' name='delete' value=\"".$LANG["buttons"][6]."\"src=\"".$CFG_GLPI["root_doc"]."/pics/puce-delete2.png\" alt=\"".$LANG["buttons"][6]."\"  title=\"".$LANG["buttons"][6]."\" >";	
							echo "</form>";
							echo "</td>";
						}
						echo "</tr>";
						$i++;
					}
				}
			}
		echo "</table>";
		}			
		echo "</div>";
	}
}

?>
