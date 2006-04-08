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
// CLASSES PlanningTracking

class PlanningTracking extends CommonDBTM {

	function PlanningTracking () {
		$this->table="glpi_tracking_planning";
	}

	function update($input,$target,$item){
		global $lang,$cfg_glpi;
		// Update a Planning Tracking

		$this->getFromDB($input["ID"]);

	// Get all flags and fill with 0 if unchecked in form
	foreach ($this->fields as $key => $val) {
		if (eregi("\.*flag\.*",$key)) {
			if (!isset($input[$key])) {
				$input[$key]=0;
			}
		}
	}	

	// Fill the update-array with changes
	$x=0;
	foreach ($input as $key => $val) {
		if (array_key_exists($key,$this->fields) && $this->fields[$key] != $input[$key]) {
			$this->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}
	$this->fields["begin"]=$_POST["begin"];
	$this->fields["end"]=$_POST["end"];

	if (!$this->test_valid_date()){
		$this->displayError("date",$item,$target);
		return false;
	}

	if ($this->is_alreadyplanned()){
		$this->displayError("is_res",$item,$target);
		return false;
	}

	// Auto update Status
	$job=new Job();
	$job->getFromDB($input["id_tracking"],0);
	if ($job->fields["status"]=="new"||$job->fields["status"]=="assign"){
		$job->fields["status"]="plan";
		$updates[]="status";
		$job->updateInDB($updates);		
	}
	
	// Auto update realtime
	$fup=new Followup();
	$fup->getFromDB($input["id_followup"]);
	$tmp_beg=split(" ",$input["begin"]);
	$tmp_end=split(" ",$input["end"]);
	$tmp_dbeg=split("-",$tmp_beg[0]);
	$tmp_dend=split("-",$tmp_end[0]);
	$tmp_hbeg=split(":",$tmp_beg[1]);
	$tmp_hend=split(":",$tmp_end[1]);
				
	$dateDiff = mktime($tmp_hend[0],$tmp_hend[1],$tmp_hend[2],$tmp_dend[1],$tmp_dend[2],$tmp_dend[0]) 
			  - mktime($tmp_hbeg[0],$tmp_hbeg[1],$tmp_hbeg[2],$tmp_dbeg[1],$tmp_dbeg[2],$tmp_dbeg[0]);		
	$updates2[]="realtime";
	$fup->fields["realtime"]=$dateDiff/60/60;
	$fup->updateInDB($updates2);

	if (isset($updates))
		$this->updateInDB($updates);
	
	if (count($updates)>0&&$cfg_glpi["mailing"])
		{
			$user=new User;
			$user->getfromDBbyName($_SESSION["glpiname"]);
			$mail = new Mailing("followup",$job,$user);
			$mail->send();
		}

	return true;
}

function add($input,$target,$nomail=0){
	global $lang,$cfg_glpi;

  // set new date.
   $this->fields["id_followup"] = $input["id_followup"];
   $this->fields["id_assign"] = $input["id_assign"];
   $this->fields["begin"] = $input["begin_date"]." ".$input["begin_hour"].":".$input["begin_min"].":00";
   $this->fields["end"] = $input["end_date"]." ".$input["end_hour"].":".$input["end_min"].":00";

//	if (!empty($target)){
		if (!$this->test_valid_date()){
			$this->displayError("date",$input["id_followup"],$target);
			return false;
		}
	
		if ($this->is_alreadyplanned()){
			$this->displayError("is_res",$input["id_followup"],$target);
			return false;
		}
/*	} else if ($this->is_alreadyplanned()||!$this->test_valid_date()) {
		$_SESSION["MESSAGE_AFTER_REDIRECT"]=$lang["job"][36];
		return false;
	}
*/
	// Auto update Status
	$job=new Job();
	$job->getFromDB($input["id_tracking"],0);
	if ($job->fields["status"]=="new"||$job->fields["status"]=="assign"){
		$job->fields["status"]="plan";
		$updates[]="status";
		$job->updateInDB($updates);		
	}

	// Auto update realtime
	$fup=new Followup();
	$fup->getFromDB($input["id_followup"]);
	if ($fup->fields["realtime"]==0){
		$tmp_beg=split(" ",$this->fields["begin"]);
		$tmp_end=split(" ",$this->fields["end"]);
		$tmp_dbeg=split("-",$tmp_beg[0]);
		$tmp_dend=split("-",$tmp_end[0]);
		$tmp_hbeg=split(":",$tmp_beg[1]);
		$tmp_hend=split(":",$tmp_end[1]);
				
		$dateDiff = mktime($tmp_hend[0],$tmp_hend[1],$tmp_hend[2],$tmp_dend[1],$tmp_dend[2],$tmp_dend[0]) 
				  - mktime($tmp_hbeg[0],$tmp_hbeg[1],$tmp_hbeg[2],$tmp_dbeg[1],$tmp_dbeg[2],$tmp_dbeg[0]);		
		$updates2[]="realtime";
		$fup->fields["realtime"]=$dateDiff/60/60;
		$fup->updateInDB($updates2);
	}

	if ($input["id_tracking"]>0)
		$return=$this->addToDB();
	else $return = true;
	
	if ($nomail==0&&$cfg_glpi["mailing"])
		{
			$user=new User;
			$user->getfromDBbyName($_SESSION["glpiname"]);
			$mail = new Mailing("followup",$job,$user);
			$mail->send();
		}

	
	return $return;
}

	function pre_deleteItem($ID) {

		if ($this->getfromDB($ID)){

			if (isset($this->fields["id_assign"])&&($this->fields["id_assign"]==$_SESSION["glpiID"]||isAdmin($_SESSION["glpitype"]))){
				// Auto update realtime
				$fup=new Followup();
				$fup->getFromDB($this->fields["id_followup"]);
				$updates2[]="realtime";
				$fup->fields["realtime"]=0;
				$fup->updateInDB($updates2);
			}
		} 
	}

	
	// SPECIFIC FUNCTIONS
	
	function is_alreadyplanned(){
		global $db;
		if (!isset($this->fields["id_assign"])||empty($this->fields["id_assign"]))
		return true;
		
		// When modify a planning do not itself take into account 
		$ID_where="";
		if(isset($this->fields["ID"]))
		$ID_where=" (ID <> '".$this->fields["ID"]."') AND ";
		
		$query = "SELECT * FROM glpi_tracking_planning".
		" WHERE $ID_where (id_assign = '".$this->fields["id_assign"]."') AND ".
		" ( ('".$this->fields["begin"]."' < begin AND '".$this->fields["end"]."' > begin) ".
		" OR ('".$this->fields["begin"]."' < end AND '".$this->fields["end"]."' >= end) ".
		" OR ('".$this->fields["begin"]."' >= begin AND '".$this->fields["end"]."' < end))";
//		echo $query."<br>";
		if ($result=$db->query($query)){
			return ($db->numrows($result)>0);
		}
		return true;
		}
	function test_valid_date(){
		return (strtotime($this->fields["begin"])<strtotime($this->fields["end"]));
		}

	function displayError($type,$ID,$target){
		global $HTMLRel,$lang;
		
		//echo "<br><div align='center'>";
		switch ($type){
			case "date":
			 $_SESSION["MESSAGE_AFTER_REDIRECT"].=$lang["planning"][1]."<br>";
			break;
			case "is_res":
			 $_SESSION["MESSAGE_AFTER_REDIRECT"].=$lang["planning"][0]."<br>";
			break;
			default :
				$_SESSION["MESSAGE_AFTER_REDIRECT"].="Erreur Inconnue<br>";
			break;
		}
		//echo "<br><a href='".$target."?job=$ID'>".$lang["buttons"][13]."</a>";
		//echo "</div>";
		}

}


?>