<?php
/*
 * @version $Id$
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi.indepnet.org
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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");


function titleReminder(){

         GLOBAL  $lang,$HTMLRel;
         
         echo "<div align='center'><table border='0'><tr><td>";
         echo "<img src=\"".$HTMLRel."pics/xxxxxxx.png\" alt='".$lang["reminder"][0]."' title='".$lang["reminder"][0]."'></td><td><a  class='icon_consol' href=\"".$HTMLRel."reminder/reminder-info-form.php\"><b>".$lang["buttons"][8]."</b></a>";
         echo "</td></tr></table></div>";
}




function showReminderForm ($target,$ID) {
	// Show Reminder or blank form
	
	GLOBAL $cfg_glpi,$lang;
	
	$issuperadmin=isSuperAdmin($_SESSION['glpitype']);
	$author=$_SESSION['glpiID'];
		
	$remind = new Reminder;
	$read ="";
	$remind_edit=false;
	$remind_show =false;

	if (!$ID) {
		
		if($remind->getEmpty()) $remind_edit = true;
	} else {
		if($remind->getfromDB($ID)){

			if($remind->fields["author"]==$author) {
				$remind_edit = true;
			} elseif($remind->fields["type"]=="public") { 
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
			echo "<input type='text' size='80' name='title' $read value=\"".$remind->fields["title"]."\">";
		}else{ 
			echo  $remind->fields["title"];
		}
		echo "</td></tr>";

		if($remind_show) { 
			echo "<tr class='tab_bg_2'><td>".$lang["planning"][9].":		</td>";
			echo "<td>";
			getUserName($remind->fields["author"]);
			echo "</td></tr>";
		}
		
		echo "<tr class='tab_bg_2'><td>".$lang["reminder"][10].":		</td>";
		echo "<td>";
		
		if($remind_edit) { 
			echo "<select name='type' $read>";
		
			echo "<option value='private' ". (($remind->fields["type"]=="private")?"selected='selected'":"") .">".$lang["reminder"][4]."</option>";	
		
			if($issuperadmin){
				echo "<option value='public' ". (($remind->fields["type"]=="public")?"selected='selected'":"").">".$lang["reminder"][5]."</option>";	
			}		
			echo "</select>";
		}else{
			echo $remind->fields["type"];
		}
			
		echo "</td></tr>";
		
		
		echo "<tr class='tab_bg_2'><td >".$lang["reminder"][11].":		</td>";

		
			
		
		
		echo "<td align='center'>";

		echo "<script type='text/javascript' >\n";
		echo "function showPlan(){\n";
		echo "Element.hide('plan');";
		echo "var a=new Ajax.Updater('viewplan','".$cfg_glpi["root_doc"]."/ajax/planning.php' , {method: 'get',parameters: 'form=remind".(($ID&&$remind->fields["rv"])?"&begin_date=".$remind->fields["begin"]."&end_date=".$remind->fields["end"]."":"")."'});";
		echo "}";
		echo "</script>\n";
		
		
		
	

		if(!$ID||$remind->fields["rv"]==0){
			echo "<div id='plan'  onClick='showPlan()'>\n";
			echo "<span style='font-weight: bold;text-decoration: none; color : #009966; cursor:pointer;'>".$lang["reminder"][12]."</span>";
		}else{
			echo "<div id='plan'  onClick='showPlan()'>\n";
			echo "<span style='font-weight: bold;text-decoration: none; color : #009966;'>".convDateTime($remind->fields["begin"])."->".convDateTime($remind->fields["end"])."</span>";
		}	
		
		echo "</div>\n";
		echo "<div id='viewplan'>\n";
		echo "</div>\n";	
		echo "</td>";
	
		
		echo "</tr>";
		
		echo "<tr class='tab_bg_2'><td>".$lang["reminder"][9].":		</td><td>";
		if($remind_edit) { 
			echo "<textarea cols='80' rows='15' name='text' $read>".$remind->fields["text"]."</textarea>";
		}else{
			echo nl2br($remind->fields["text"]);
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

function updateReminder($input) {
	// Update reminder in database

	$remind = new Reminder;
	$remind->getFromDB($input["ID"]);

	if (isset($input['plan'])){
		$plan=$input['plan'];
		unset($input['plan']);
		$input["begin"] = $plan["begin_date"]." ".$plan["begin_hour"].":".$plan["begin_min"].":00";
  		$input["end"] = $plan["end_date"]." ".$plan["end_hour"].":".$plan["end_min"].":00";
		$input["rv"]=1;
		}	


	// set new date and make sure it gets updated
	$updates[0]= "date_mod";
	$reminder->fields["date_mod"] = date("Y-m-d H:i:s");
	

	// Fill the update-array with changes
	$x=0;
	foreach ($input as $key => $val) {
		if (array_key_exists($key,$remind->fields) && $remind->fields[$key] != $input[$key]) {
			$remind->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}

	if(!empty($updates)) {
	
		$remind->updateInDB($updates);
	}
}

function addReminder($input) {
	
	$remind = new Reminder;
	
	$input["begin"] = $input["end"] = "0000-00-00 00:00:00";

	if (isset($input['plan'])){
		$plan=$input['plan'];
		unset($input['plan']);
		$input['rv']="1";
		$input["begin"] = $plan["begin_date"]." ".$plan["begin_hour"].":".$plan["begin_min"].":00";
  		$input["end"] = $plan["end_date"]." ".$plan["end_hour"].":".$plan["end_min"].":00";
		}	

		
	// set new date.
   	$input["date"] = date("Y-m-d H:i:s");

	// dump status
	unset($input['add']);
	
	
	// fill array for update
	foreach ($input as $key => $val) {
		if ($key[0]!='_'&&(empty($remind->fields[$key]) || $remind->fields[$key] != $input[$key])) {
			$remind->fields[$key] = $input[$key];
		}
	}


	return $remind->addToDB();
}


function deleteReminder($input) {
	// Delete reminder
	
	$remind = new Reminder;
	$remind->deleteFromDB($input["ID"]);
} 


function showCentralReminder($type="private"){
	// show reminder that are not planned 

	GLOBAL $db,$cfg_glpi, $lang, $HTMLRel;
	
	$author=$_SESSION['glpiID'];	
	$today=date("Y-m-d H:i:s");
	
	if($type=="public"){ // show public reminder
	$query="SELECT * FROM glpi_reminder WHERE type='public' AND (begin>='$today' or rv='0')";
	$titre="<a href=\"".$HTMLRel."reminder/index.php\">".$lang["reminder"][1]."</a>";
	}else{ // show private reminder
	$query="SELECT * FROM glpi_reminder WHERE author='$author' AND type='private' AND (begin>='$today' or rv='0') ";
	$titre="<a href=\"".$HTMLRel."reminder/index.php\">".$lang["reminder"][0]."</a>";
	}

	
	$result = $db->query($query);

	

		echo "<div align='center'><br><table class='tab_cadrehov'>";
		
		echo "<tr><th><div style='position: relative'><span><strong>"."$titre"."</strong></span>";
		echo "<span style='  position:absolute; right:0; margin-right:5px; font-size:10px;'><a href=\"".$HTMLRel."reminder/reminder-info-form.php\"><img src=\"".$HTMLRel."pics/plus.png\" alt='+' title='".$lang["buttons"][8]."'></a></span></div>";
		echo "</th></tr>";
	if($db->numrows($result)>0){
		while ($data =$db->fetch_array($result)){ 

			echo "<tr class='tab_bg_2'><td><div style='position: relative'><span><a style='margin-left:8px' href=\"".$cfg_glpi["root_doc"]."/reminder/reminder-info-form.php?ID=".$data["ID"]."\">".$data["title"]."</a></span>";

			if($data["rv"]=="1"){

				$tab=split(" ",$data["begin"]);
				$date_url=$tab[0];
			
				echo "<span style='  position:absolute; right:0; margin-right:5px; font-size:10px;'><a href=\"".$cfg_install["root"]."/planning/index.php?date=".$date_url."&amp;type=day\"><img src=\"".$HTMLRel."pics/rdv.png\" alt='".$lang["planning"][3]."' title='".convDateTime($data["begin"])."=>".convDateTime($data["end"])."'></a></span>";



			}

			echo "</td></tr>";

		
		}
	}
	

	echo "</table></div>";
}


function showListReminder($type="private"){
	// show reminder that are not planned 

	GLOBAL $db,$cfg_glpi, $lang, $HTMLRel;
	
	$author=$_SESSION['glpiID'];	
	
	if($type=="public"){ // show public reminder
	$query="SELECT * FROM glpi_reminder WHERE type='public'";
	$titre="Notes publiques";
	}else{ // show private reminder
	$query="SELECT * FROM glpi_reminder WHERE author='$author' AND type='private' ";
	$titre="Notes persos";
	}

	
	$result = $db->query($query);

	$tabremind=array();

	$remind=new Reminder();
	
	$i=0;
	if ($db->numrows($result)>0)
	while ($data=$db->fetch_array($result)){
		$remind->getFromDB($data["ID"]);
		
		if($data["rv"]==1){ //Un rdv on va trier sur la date begin
			$sort=$data["begin"];
		}else{ // non programmé on va trier sur la date de modif...
			$sort=$data["date"];
		}

		
		$tabremind[$sort."$$".$i]["id_reminder"]=$remind->fields["ID"];
		$tabremind[$sort."$$".$i]["begin"]=($data["rv"]==1?"".$data["begin"]."":"".$data["date"]."");
		$tabremind[$sort."$$".$i]["end"]=($data["rv"]==1?"".$data["end"]."":"");
		$tabremind[$sort."$$".$i]["title"]=resume_text($remind->fields["title"],$cfg_glpi["cut"]);
		$tabremind[$sort."$$".$i]["text"]=resume_text($remind->fields["text"],$cfg_glpi["cut"]);
		$i++;
	}
	
	
	
	ksort($tabremind);


	echo "<div align='center' ><br><table class='tab_cadrehov' style='width:600px'>";
	echo "<tr><th>"."$titre"."</th><th colspan='2'>".$lang["common"][27]."</th></tr>";
	
		if (count($tabremind)>0){
			foreach ($tabremind as $key => $val){

			echo "<tr class='tab_bg_2'><td width='70%'><a href=\"".$cfg_glpi["root_doc"]."/reminder/reminder-info-form.php?ID=".$val["id_reminder"]."\">".$val["title"]."</a></td>";
			
				if($val["end"]!=""){	
				echo "<td style='text-align:center;'>";

				$tab=split(" ",$val["begin"]);
				$date_url=$tab[0];
				echo "<a href=\"".$cfg_glpi["root_doc"]."/planning/index.php?date=".$date_url."&amp;type=day\"><img src=\"".$HTMLRel."pics/rdv.png\" alt='".$lang["planning"][3]."' title='".$lang["planning"][3]."'></a>";
				echo "</td>";
				echo "<td style='text-align:center;' ><strong>".convDateTime($val["begin"]);
				echo "<br>".convDateTime($val["end"])."</strong>";

				}else{
				echo "<td>&nbsp;";
				echo "</td>";
				echo "<td  style='text-align:center;'><span style='color:#aaaaaa;'>".convDateTime($val["begin"])."</span>";
				
				}
			echo "</td></tr>";
			}

		}

	echo "</table></div>";
}







?>
