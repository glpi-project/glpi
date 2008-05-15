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


// CLASSES PlanningTracking

class PlanningTracking extends CommonDBTM {

	function PlanningTracking () {
		$this->table="glpi_tracking_planning";
	}

	function update($input,$history=1){
		global $LANG,$CFG_GLPI;
		// Update a Planning Tracking

		$this->getFromDB($input["ID"]);

		list($begin_year,$begin_month,$begin_day)=split("-",$input["begin_date"]);
		list($end_year,$end_month,$end_day)=split("-",$input["end_date"]);

		list($begin_hour,$begin_min)=split(":",$input["begin_hour"]);
		list($end_hour,$end_min)=split(":",$input["end_hour"]);
		$input["begin"]=date("Y-m-d H:i:00",mktime($begin_hour,$begin_min,0,$begin_month,$begin_day,$begin_year));
		$input["end"]=date("Y-m-d H:i:00",mktime($end_hour,$end_min,0,$end_month,$end_day,$end_year));

		// Fill the update-array with changes
		$x=0;
		foreach ($input as $key => $val) {
			if (array_key_exists($key,$this->fields) && $this->fields[$key] != $input[$key]) {
				$this->fields[$key] = $input[$key];
				$updates[$x] = $key;
				$x++;
			}
		}

		if (!$this->test_valid_date()){
			$this->displayError("date");
			return false;
		}

		if ($this->is_alreadyplanned()){
			$this->displayError("is_res");
			return false;
		}

		// Auto update Status
		$job=new Job();
		$job->getFromDB($input["id_tracking"]);
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
		$job->updateRealTime();

		if (isset($updates)){
			$this->updateInDB($updates);

			if ((!isset($input["_nomail"])||$input["_nomail"]==0)&&count($updates)>0&&$CFG_GLPI["mailing"]){
				$user=new User;
				$user->getFromDB($_SESSION["glpiID"]);
				$mail = new Mailing("followup",$job,$user,$fup->fields["private"]);
				$mail->send();
			}

		}
		return true;
	}

	function add($input){
		global $LANG,$CFG_GLPI;
		// set new date.
		$this->fields["id_followup"] = $input["id_followup"];
		$this->fields["id_assign"] = $input["id_assign"];
		$this->fields["state"] = $input["state"];
		$this->fields["begin"] = $input["begin_date"]." ".$input["begin_hour"].":00";
		$this->fields["end"] = $input["end_date"]." ".$input["end_hour"].":00";

		//	if (!empty($target)){
		if (!$this->test_valid_date()){
			$this->displayError("date");
			return false;
		}

		if ($this->is_alreadyplanned()){
			$this->displayError("is_res");
			return false;
		}
		
		// Auto update Status
		$job=new Job();
		$job->getFromDB($input["id_tracking"]);
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
			$job->updateRealTime();
		}

		if ($input["id_tracking"]>0)
			$return=$this->addToDB();
		else $return = true;

		if ((!isset($input["_nomail"])||$input["_nomail"]==0)&&$CFG_GLPI["mailing"])
		{
			$user=new User;
			$user->getFromDB($_SESSION["glpiID"]);
			$mail = new Mailing("followup",$job,$user,$fup->fields["private"]);
			$mail->send();
		}


		return $return;
	}

	function pre_deleteItem($ID) {

		if ($this->getFromDB($ID)){
			if (isset($this->fields["id_assign"])&&($this->fields["id_assign"]==$_SESSION["glpiID"]||haveRight("comment_all_ticket","1"))){
				// Auto update realtime
				$fup=new Followup();
				$fup->getFromDB($this->fields["id_followup"]);
				$updates2[]="realtime";
				$fup->fields["realtime"]=0;
				$fup->updateInDB($updates2);
			}
		} 
		return true;
	}


	// SPECIFIC FUNCTIONS

	function is_alreadyplanned(){
		global $DB;
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
		if ($result=$DB->query($query)){
			return ($DB->numrows($result)>0);
		}
		return true;
	}
	function test_valid_date(){
		return (strtotime($this->fields["begin"])<strtotime($this->fields["end"]));
	}

	function displayError($type){
		global $LANG;

		switch ($type){
			case "date":
				$_SESSION["MESSAGE_AFTER_REDIRECT"].=$LANG["planning"][1]."<br>";
			break;
			case "is_res":
				$_SESSION["MESSAGE_AFTER_REDIRECT"].=$LANG["planning"][0]."<br>";
			break;
			default :
				$_SESSION["MESSAGE_AFTER_REDIRECT"].=$LANG["common"][61]."<br>";
			break;
		}
	}

}


?>
