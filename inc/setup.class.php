<?php
/*
 * @version $Id: HEADER 3795 2006-08-22 03:57:36Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.

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

class SetupSearchDisplay extends CommonDBTM{

	function SetupSearchDisplay () {
		$this->table="glpi_display";
		$this->type=-1;
	}

	function prepareInputForAdd($input) {
		global $db;
		$query="SELECT MAX(rank) FROM glpi_display WHERE type='".$input["type"]."' AND FK_users='".$input["FK_users"]."'";
		$result=$db->query($query);
		$input["rank"]=$db->result($result,0,0)+1;

		return $input;
	}

	function activatePerso($input){
		global $db,$SEARCH_OPTION;
		$query="SELECT * FROM glpi_display WHERE type='".$input["type"]."' AND FK_users='0'";
		$result=$db->query($query);
		if ($db->numrows($result)){
			while ($data=$db->fetch_assoc($result)){
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

		};

	}

	function up($input){
		global $db;
		// Get current item
		$query="SELECT rank FROM glpi_display WHERE ID='".$input['ID']."';";
		$result=$db->query($query);
		$rank1=$db->result($result,0,0);
		// Get previous item
		$query="SELECT ID,rank FROM glpi_display WHERE type='".$input['type']."' AND FK_users='".$input["FK_users"]."' AND rank<'$rank1' ORDER BY rank DESC;";
		$result=$db->query($query);
		$rank2=$db->result($result,0,"rank");
		$ID2=$db->result($result,0,"ID");
		// Update items
		$query="UPDATE glpi_display SET rank='$rank2' WHERE ID ='".$_POST['ID']."'";
		$db->query($query);
		$query="UPDATE glpi_display SET rank='$rank1' WHERE ID ='$ID2'";
		$db->query($query);
	}

	function down($input){
		global $db;

		// Get current item
		$query="SELECT rank FROM glpi_display WHERE ID='".$_POST['ID']."';";
		$result=$db->query($query);
		$rank1=$db->result($result,0,0);
		// Get next item
		$query="SELECT ID,rank FROM glpi_display WHERE type='".$input['type']."' AND FK_users='".$input["FK_users"]."' AND rank>'$rank1' ORDER BY rank ASC;";
		$result=$db->query($query);
		$rank2=$db->result($result,0,"rank");
		$ID2=$db->result($result,0,"ID");
		// Update items
		$query="UPDATE glpi_display SET rank='$rank2' WHERE ID ='".$_POST['ID']."'";
		$db->query($query);
		$query="UPDATE glpi_display SET rank='$rank1' WHERE ID ='$ID2'";
		$db->query($query);
	}

	function title($type){
		global $lang,$cfg_glpi;

		$dp=array();
		if (haveRight("computer","r")){
			$dp[COMPUTER_TYPE]=$lang["Menu"][0];
			if (!$type)
				$type=COMPUTER_TYPE;
		}
		if (haveRight("networking","r")){
			$dp[NETWORKING_TYPE]=$lang["Menu"][1];
			if (!$type)
				$type=NETWORKING_TYPE;
		}
		if (haveRight("printer","r")){
			$dp[PRINTER_TYPE]=$lang["Menu"][2];
			if (!$type)
				$type=PRINTER_TYPE;
		}
		if (haveRight("monitor","r")){
			$dp[MONITOR_TYPE]=$lang["Menu"][3];
			if (!$type)
				$type=MONITOR_TYPE;
		}
		if (haveRight("peripheral","r")){
			$dp[PERIPHERAL_TYPE]=$lang["Menu"][16];
			if (!$type)
				$type=PERIPHERAL_TYPE;
		}
		if (haveRight("software","r")){
			$dp[SOFTWARE_TYPE]=$lang["Menu"][4];
			if (!$type)
				$type=SOFTWARE_TYPE;
		}
		if (haveRight("contact_enterprise","r")){
			$dp[CONTACT_TYPE]=$lang["Menu"][22];
			$dp[ENTERPRISE_TYPE]=$lang["Menu"][23];
			if (!$type)
				$type=CONTACT_TYPE;
		}
		if (haveRight("contract_infocom","r")){
			$dp[CONTRACT_TYPE]=$lang["Menu"][25];
			if (!$type)
				$type=CONTRACT_TYPE;
		}
		if (haveRight("typedoc","r")){
			$dp[TYPEDOC_TYPE]=$lang["document"][7];
			if (!$type)
				$type=TYPEDOC_TYPE;
		}
		if (haveRight("document","r")){
			$dp[DOCUMENT_TYPE]=$lang["Menu"][27];
			if (!$type)
				$type=DOCUMENT_TYPE;
		}
		if (haveRight("user","r")){
			$dp[USER_TYPE]=$lang["Menu"][14];
			if (!$type)
				$type=USER_TYPE;
		}
		if (haveRight("consumable","r")){
			$dp[CONSUMABLE_TYPE]=$lang["Menu"][32];
			if (!$type)
				$type=CONSUMABLE_TYPE;
		}
		if (haveRight("cartridge","r")){
			$dp[CARTRIDGE_TYPE]=$lang["Menu"][21];
			if (!$type)
				$type=CARTRIDGE_TYPE;
		}
		if (haveRight("link","r")){
			$dp[LINK_TYPE]=$lang["setup"][87];
			if (!$type)
				$type=LINK_TYPE;
		}
		if (haveRight("phone","r")){
			$dp[PHONE_TYPE]=$lang["Menu"][34];
			if (!$type)
				$type=PHONE_TYPE;
		}
		if (haveRight("group","r")){
			$dp[GROUP_TYPE]=$lang["Menu"][36];
			if (!$type)
				$type=GROUP_TYPE;
		}
		if (count($dp)){
			asort($dp);
			echo "<div align='center'><form method='post' action=\"".$cfg_glpi["root_doc"]."/front/setup.display.php\">";
			echo "<table class='tab_cadre' cellpadding='5'><tr><th colspan='2'>";
			echo $lang["setup"][251].": </th></tr><tr class='tab_bg_1'><td><select name='type'>";
	
	
			foreach ($dp as $key => $val){
				$sel="";
				if ($type==$key) $sel="selected";
				echo "<option value='$key' $sel>".$val."</option>";
			}
	
			echo "</select></td>";
			echo "<td><input type='submit' value=\"".$lang["buttons"][2]."\" class='submit' ></td></tr>";
			echo "</table></form></div>";
	
			echo "<div id='barre_onglets'><ul id='onglet'>";
			echo "<li "; if ($_SESSION['glpi_searchconfig']==1){ echo "class='actif'";} echo  "><a href='".$cfg_glpi["root_doc"]."/front/setup.display.php?onglet=1&amp;type=$type'>".$lang["central"][13]."</a></li>";
			echo "<li "; if ($_SESSION['glpi_searchconfig']==2){ echo "class='actif'";} echo  "><a href='".$cfg_glpi["root_doc"]."/front/setup.display.php?onglet=2&amp;type=$type'>".$lang["central"][12]."</a></li>";
			echo "</ul></div>";
			return $type;
		} else return false;
	}

	function showForm($type){
		global $SEARCH_OPTION,$cfg_glpi,$lang,$db,$HTMLRel;


		$is_global=($_SESSION['glpi_searchconfig']==1);
		if ($is_global) $IDuser=0;
		else $IDuser=$_SESSION["glpiID"];
		$global_write=haveRight("search_config","w");

		echo "<div align='center'>";
		// Defined items
		$query="SELECT * from glpi_display WHERE type='$type' AND FK_users='$IDuser' order by rank";

		$result=$db->query($query);
		$numrows=0;
		$numrows=$db->numrows($result);
		if ($numrows==0&&!$is_global){

			echo "<table class='tab_cadre_fixe' cellpadding='2' ><tr><th colspan='4'>";
			echo "<form method='post' action=\"".$cfg_glpi["root_doc"]."/front/setup.display.php\">";
			echo "<input type='hidden' name='type' value='$type'>";
			echo "<input type='hidden' name='FK_users' value='$IDuser'>";

			echo $lang["setup"][241];
			echo "&nbsp;&nbsp;&nbsp;<input type='submit' name='activate' value=\"".$lang["buttons"][2]."\" class='submit' >";
			echo "</form></th></tr></table>";

		} else {

			echo "<table class='tab_cadre_fixe' cellpadding='2' ><tr><th colspan='4'>";
			echo $lang["setup"][252].": </th></tr>";
			if (!$is_global||$global_write){
				echo "<tr class='tab_bg_1'><td colspan='4' align='center'>";
				echo "<form method='post' action=\"".$cfg_glpi["root_doc"]."/front/setup.display.php\">";
				echo "<input type='hidden' name='type' value='$type'>";
				echo "<input type='hidden' name='FK_users' value='$IDuser'>";

				echo "<select name='num'>";
				$first_group=true;
				foreach ($SEARCH_OPTION[$type] as $key => $val)
					if (!is_array($val)){
						if (!$first_group) echo "</optgroup>";
						else $first_group=false;
						echo "<optgroup label=\"".$val."\">";
					} else 	if ($key!=1){
						echo "<option value='$key'>".$val["name"]."</option>";
					}
				if (!$first_group) echo "</optgroup>";

				echo "</select>";
				echo "&nbsp;&nbsp;&nbsp;<input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit' >";
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
				while ($data=$db->fetch_array($result)){
					if ($data["num"]!=1){
						echo "<tr class='tab_bg_2'><td align='center' width='50%' >";
						echo $SEARCH_OPTION[$type][$data["num"]]["name"];
						echo "</td>";
						if (!$is_global||$global_write){
							if ($i!=0){
								echo "<td align='center' valign='middle'>";
								echo "<form method='post' action=\"".$cfg_glpi["root_doc"]."/front/setup.display.php\">";
								echo "<input type='hidden' name='ID' value='".$data["ID"]."'>";
								echo "<input type='hidden' name='FK_users' value='$IDuser'>";

								echo "<input type='hidden' name='type' value='$type'>";
								echo "<input type='image' name='up'  value=\"".$lang["buttons"][24]."\"  src=\"".$HTMLRel."pics/puce-up2.png\" alt=\"".$lang["buttons"][24]."\"  title=\"".$lang["buttons"][24]."\" >";	
								echo "</form>";
								echo "</td>";
							} else echo "<td>&nbsp;</td>";
							if ($i!=$numrows-1){
								echo "<td align='center' valign='middle'>";
								echo "<form method='post' action=\"".$cfg_glpi["root_doc"]."/front/setup.display.php\">";
								echo "<input type='hidden' name='ID' value='".$data["ID"]."'>";
								echo "<input type='hidden' name='FK_users' value='$IDuser'>";

								echo "<input type='hidden' name='type' value='$type'>";
								echo "<input type='image' name='down' value=\"".$lang["buttons"][25]."\" src=\"".$HTMLRel."pics/puce-down2.png\" alt=\"".$lang["buttons"][25]."\"  title=\"".$lang["buttons"][25]."\" >";	
								echo "</form>";
								echo "</td>";
							} else echo "<td>&nbsp;</td>";

							echo "<td align='center' valign='middle'>";
							echo "<form method='post' action=\"".$cfg_glpi["root_doc"]."/front/setup.display.php\">";
							echo "<input type='hidden' name='ID' value='".$data["ID"]."'>";
							echo "<input type='hidden' name='FK_users' value='$IDuser'>";

							echo "<input type='hidden' name='type' value='$type'>";
							echo "<input type='image' name='delete' value=\"".$lang["buttons"][6]."\"src=\"".$HTMLRel."pics/puce-delete2.png\" alt=\"".$lang["buttons"][6]."\"  title=\"".$lang["buttons"][6]."\" >";	
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
