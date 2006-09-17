<?php
/*
 * @version $Id$
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
// Original Author of file: Jean-mathieu Doléans
// Purpose of file:
// ----------------------------------------------------------------------




class Reminder extends CommonDBTM {

	function Reminder () {
		$this->table="glpi_reminder";
		$this->type=REMINDER_TYPE;
	}

	function prepareInputForAdd($input) {
		global $lang;

		if(empty($input["title"])) $input["title"]=$lang["reminder"][15];

		$input["begin"] = $input["end"] = "0000-00-00 00:00:00";

		if (isset($input['plan'])){
			$input['_plan']=$input['plan'];
			unset($input['plan']);
			$input['rv']="1";
			$input["begin"] = $input['_plan']["begin_date"]." ".$input['_plan']["begin_hour"].":00";
			$input["end"] = $input['_plan']["end_date"]." ".$input['_plan']["end_hour"].":00";
		}	


		// set new date.
		$input["date"] = date("Y-m-d H:i:s");

		return $input;
	}

	function prepareInputForUpdate($input) {
		global $lang;

		if(empty($input["title"])) $input["title"]=$lang["reminder"][15];


		if (isset($input['plan'])){
			$input['_plan']=$input['plan'];
			unset($input['plan']);
			$input['rv']="1";
			$input["begin"] = $input['_plan']["begin_date"]." ".$input['_plan']["begin_hour"].":00";
			$input["end"] = $input['_plan']["end_date"]." ".$input['_plan']["end_hour"].":00";
		}	


		// set new date.
		$input["date_mod"] = date("Y-m-d H:i:s");

		return $input;
	}


	function title(){

		global  $lang,$HTMLRel;

		echo "<div align='center'><table border='0'><tr><td>";
		echo "<img src=\"".$HTMLRel."pics/reminder.png\" alt='".$lang["reminder"][0]."' title='".$lang["reminder"][0]."'></td><td><a  class='icon_consol' href=\"".$HTMLRel."front/reminder.form.php\"><b>".$lang["buttons"][8]."</b></a>";
		echo "</td></tr></table></div>";
	}




	function showForm ($target,$ID) {
		// Show Reminder or blank form

		global $cfg_glpi,$lang;

		$issuperadmin=haveRight("reminder_public","w");
		$author=$_SESSION['glpiID'];

		$read ="";
		$remind_edit=false;
		$remind_show =false;

		if (!$ID) {

			if($this->getEmpty()){
				$remind_edit = true;
				$this->fields["title"]=$lang["reminder"][6];
				$onfocus="onfocus=\"this.value=''\"";
			}


		} else {
			if($this->getfromDB($ID)){
				$onfocus="";
				if($this->fields["author"]==$author) {
					$remind_edit = true;
				} elseif($this->fields["type"]=="public") { 
					$remind_show = true;
				}

			}else $remind_show = false;

		}
		if ($remind_show||$remind_edit){

			if($remind_edit) echo "<form method='post' name='remind' action=\"$target\">";

			echo "<div align='center'><table class='tab_cadre' width='450'>";
			echo "<tr><th colspan='2' ><b>";
			if (!$ID) {
				echo $lang["reminder"][6].":";
			} else {
				echo $lang["reminder"][7]." ID $ID:";
			}		
			echo "</b></th></tr>";

			echo "<tr class='tab_bg_2'><td>".$lang["reminder"][8].":		</td>";
			echo "<td>";

			if($remind_edit) { 
				echo "<input type='text' size='80' name='title' $read value=\"".$this->fields["title"]."\"  ".$onfocus.">";
			}else{ 
				echo  $this->fields["title"];
			}
			echo "</td></tr>";

			if($remind_show) { 
				echo "<tr class='tab_bg_2'><td>".$lang["planning"][9].":		</td>";
				echo "<td>";
				echo getUserName($this->fields["author"]);
				echo "</td></tr>";
			}

			echo "<tr class='tab_bg_2'><td>".$lang["reminder"][10].":		</td>";
			echo "<td>";

			if($remind_edit) { 
				echo "<select name='type' $read>";

				echo "<option value='private' ". (((isset($_GET["type"])&&$_GET["type"]=="private")||$this->fields["type"]=="private")?"selected='selected'":"") .">".$lang["reminder"][4]."</option>";	

				if($issuperadmin){
					echo "<option value='public' ". ((isset($_GET["type"])&&$_GET["type"]=="public")||($this->fields["type"]=="public")?"selected='selected'":"").">".$lang["reminder"][5]."</option>";	
				}		
				echo "</select>";
			}else{
				echo $this->fields["type"];
			}

			echo "</td></tr>";


			echo "<tr class='tab_bg_2'><td >".$lang["reminder"][11].":		</td>";





			echo "<td align='center'>";

			if($remind_edit) { 
				echo "<script type='text/javascript' >\n";
				echo "function showPlan(){\n";
				echo "Element.hide('plan');";
				echo "var a=new Ajax.Updater('viewplan','".$cfg_glpi["root_doc"]."/ajax/planning.php' , {asynchronous:true, evalScripts:true, method: 'get',parameters: 'form=remind".(($ID&&$this->fields["rv"])?"&begin_date=".$this->fields["begin"]."&end_date=".$this->fields["end"]."":"")."'});";
				echo "}";
				echo "</script>\n";
	
				if(!$ID||$this->fields["rv"]==0){
					echo "<div id='plan'  onClick='showPlan()'>\n";
					echo "<span style='font-weight: bold;text-decoration: none; color : #009966; cursor:pointer;'>".$lang["reminder"][12]."</span>";
				}else{
					echo "<div id='plan'  onClick='showPlan()'>\n";
					echo "<span style='font-weight: bold;text-decoration: none; color : #009966;cursor:pointer;'>".convDateTime($this->fields["begin"])."->".convDateTime($this->fields["end"])."</span>";
				}	

				echo "</div>\n";
				echo "<div id='viewplan'>\n";
				echo "</div>\n";	
			}
			echo "</td>";


			echo "</tr>";

			echo "<tr class='tab_bg_2'><td>".$lang["reminder"][9].":		</td><td>";
			if($remind_edit) { 
				echo "<textarea cols='80' rows='15' name='text' $read>".$this->fields["text"]."</textarea>";
			}else{
				echo nl2br($this->fields["text"]);
			}
			echo "</td></tr>";

			if (!$ID) { // add

				echo "<tr>";
				echo "<td class='tab_bg_2' valign='top' colspan='2'>";
				echo "<input type='hidden' name='author' value=\"$author\">\n";
				echo "<div align='center'><input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'></div>";
				echo "</td>";
				echo "</tr>";



			} elseif($remind_edit) { // update / delete uniquement pour l'auteur du message


				echo "<tr>";

				echo "<td class='tab_bg_2' valign='top' colspan='2'>";
				echo "<input type='hidden' name='ID' value=\"$ID\">\n";
				echo "<div align='center'><input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'>";

				echo "<input type='hidden' name='ID' value=\"$ID\">\n";
				echo "<input type='hidden' name='author' value=\"$author\">\n";

				echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'></div>";

				echo "</td>";
				echo "</tr>";




			}

			echo "</table></div>";
			if($remind_edit){echo "</form>";}
		} else {
			echo "<div align='center'><b>".$lang["reminder"][13]."</b></div>";

		}

		return true;

	}


}

?>
