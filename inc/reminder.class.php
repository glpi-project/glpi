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
// Original Author of file: Jean-mathieu Doléans
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}



class Reminder extends CommonDBTM {

	function Reminder () {
		$this->table="glpi_reminder";
		$this->type=REMINDER_TYPE;
		$this->entity_assign=true;
		$this->may_be_recursive=true;
		$this->may_be_private=true;
	}

	function prepareInputForAdd($input) {
		global $LANG;

		if(empty($input["name"])) $input["name"]=$LANG["reminder"][15];

		$input["begin"] = $input["end"] = "0000-00-00 00:00:00";

		if (isset($input['plan'])){
			$input['_plan']=$input['plan'];
			unset($input['plan']);
			$input['rv']="1";
			$input["begin"] = $input['_plan']["begin_date"]." ".$input['_plan']["begin_hour"].":00";
			$input["end"] = $input['_plan']["end_date"]." ".$input['_plan']["end_hour"].":00";
		}	

		if ($input['recursive']&&!$input['private']){
			if (!haveRecursiveAccessToEntity($input["FK_entities"])){
				unset($input['recursive']);
				addMessageAfterRedirect($LANG["common"][75]);
			}
		}

		// set new date.
		$input["date"] = $_SESSION["glpi_currenttime"];

		return $input;
	}

	function pre_updateInDB($input,$updates) {
		$this->fields["date_mod"]=$_SESSION["glpi_currenttime"];
		$updates[]="date_mod";
		return array($input,$updates);
	}

	function prepareInputForUpdate($input) {
		global $LANG;

		if(empty($input["name"])) $input["name"]=$LANG["reminder"][15];


		if (isset($input['plan'])){
			$input['_plan']=$input['plan'];
			unset($input['plan']);
			$input['rv']="1";
			$input["begin"] = $input['_plan']["begin_date"]." ".$input['_plan']["begin_hour"].":00";
			$input["end"] = $input['_plan']["end_date"]." ".$input['_plan']["end_hour"].":00";
			$input["state"] = $input['_plan']["state"];
		}	
		if ($input['recursive']&&!$input['private']){
			if (!haveRecursiveAccessToEntity($input["FK_entities"])){
				unset($input['recursive']);
				addMessageAfterRedirect($LANG["common"][75]);
			}
		}

		return $input;
	}


	function post_getEmpty () {
		global $LANG;
		$this->fields["name"]=$LANG["reminder"][6];
		$this->fields["FK_users"]=$_SESSION['glpiID'];
		$this->fields["private"]=1;
		$this->fields["FK_entities"]=$_SESSION["glpiactive_entity"];
	}

	function showForm ($target,$ID) {
		// Show Reminder or blank form

		global $CFG_GLPI,$LANG;

		$onfocus="";

		$spotted=false;
		if ($ID>0) {
			if($this->can($ID,'r')) {
				$spotted = true;	
			}
		} else {
			if ($this->can(-1,'w')){
				$spotted = true;
				$onfocus="onfocus=\"this.value=''\"";
			}
		} 

		if ($spotted){
			$canedit=$this->can($ID,'w');


		if($canedit) {
			echo "<form method='post' name='remind' action=\"$target\">";
		}

		echo "<div class='center'><table class='tab_cadre' width='450'>";
		echo "<tr><th>&nbsp;</th><th>";
		if (!$ID) {
			echo $LANG["reminder"][6];
		} else {
			echo $LANG["common"][2]." $ID";
		}		

			echo "</th></tr>";

			echo "<tr class='tab_bg_2'><td>".$LANG["common"][57].":		</td>";
			echo "<td>";

			if($canedit) { 
				echo "<input type='text' size='80' name='name' value=\"".$this->fields["name"]."\"  ".$onfocus.">";
			}else{ 
				echo  $this->fields["name"];
			}
			echo "</td></tr>";

			if(!$canedit) { 
				echo "<tr class='tab_bg_2'><td>".$LANG["planning"][9].":		</td>";
				echo "<td>";
				echo getUserName($this->fields["FK_users"]);
				echo "</td></tr>";
			}

			echo "<tr class='tab_bg_2'><td>".$LANG["common"][17].":		</td>";
			echo "<td>";

			if($canedit&&haveRight("reminder_public","w")) { 

				if (!$ID){
					if (isset($_GET["private"])){
						$this->fields["private"]=$_GET["private"];
					}
					if (isset($_GET["recursive"])){
						$this->fields["recursive"]=$_GET["recursive"];
					}
				}
	
				privatePublicSwitch($this->fields["private"],$this->fields["FK_entities"],$this->fields["recursive"]);
			}else{
				echo getYesNo($this->fields["private"]);				
			}

			echo "</td></tr>";


			echo "<tr class='tab_bg_2'><td >".$LANG["reminder"][11].":		</td>";





			echo "<td class='center'>";

			if($canedit) { 
				echo "<script type='text/javascript' >\n";
				echo "function showPlan(){\n";
					echo "window.document.getElementById('plan').style.display='none';";
					$params=array('form'=>'remind');
					if ($ID&&$this->fields["rv"]){
						$params['state']=$this->fields["state"];
						$params['begin_date']=$this->fields["begin"];
						$params['end_date']=$this->fields["end"];
					}
					ajaxUpdateItemJsCode('viewplan',$CFG_GLPI["root_doc"]."/ajax/planning.php",$params,false);
				echo "}";
				
				echo "</script>\n";
			}
			
			if(!$ID||$this->fields["rv"]==0){
				if($canedit) { 
					echo "<div id='plan'  onClick='showPlan()'>\n";
					echo "<span class='showplan'>".$LANG["reminder"][12]."</span>";
				}
			}else{
				if($canedit) {
					echo "<div id='plan'  onClick='showPlan()'>\n";
					echo "<span class='showplan'>";
				}
				echo getPlanningState($this->fields["state"]).": ".convDateTime($this->fields["begin"])."->".convDateTime($this->fields["end"]);
				if($canedit){
					echo "</span>";
				}
			}	
			
			if($canedit) { 
				echo "</div>\n";
				echo "<div id='viewplan'>\n";
				echo "</div>\n";	
			}
			echo "</td>";


			echo "</tr>";

			echo "<tr class='tab_bg_2'><td>".$LANG["reminder"][9].":		</td><td>";
			if($canedit) { 
				echo "<textarea cols='80' rows='15' name='text'>".$this->fields["text"]."</textarea>";
			}else{
				echo nl2br($this->fields["text"]);
			}
			echo "</td></tr>";

			if (!$ID) { // add
				echo "<tr>";
				echo "<td class='tab_bg_2' valign='top' colspan='2'>";
				echo "<input type='hidden' name='FK_users' value=\"".$this->fields['FK_users']."\">\n";
				echo "<div class='center'><input type='submit' name='add' value=\"".$LANG["buttons"][8]."\" class='submit'></div>";
				echo "</td>";
				echo "</tr>";
			} elseif($canedit) { // update / delete uniquement pour l'auteur du message
				echo "<tr>";

				echo "<td class='tab_bg_2' valign='top' colspan='2'>";
				echo "<input type='hidden' name='ID' value=\"$ID\">\n";
				echo "<div class='center'><input type='submit' name='update' value=\"".$LANG["buttons"][7]."\" class='submit'>";

				echo "<input type='hidden' name='ID' value=\"$ID\">\n";

				echo "<input type='submit' name='delete' value=\"".$LANG["buttons"][6]."\" class='submit'></div>";

				echo "</td>";
				echo "</tr>";




			}

			echo "</table></div>";
			if($canedit){
				echo "</form>";
			}
		} else {
			echo "<div class='center'><strong>".$LANG["common"][54]."</strong></div>";

		}

		return true;

	}


}

?>
