<?php
/*
 * @version $Id$
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

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}


// CLASSES PlanningTracking

class PlanningTracking extends CommonDBTM {

	/**
	 * Constructor
	**/
	function __construct () {
		$this->table="glpi_tracking_planning";
	}


	function prepareInputForUpdate($input) {
		$this->getFromDB($input["ID"]);
		// Save fields
		$oldfields=$this->fields;

		// Needed for test already planned
		$this->fields["id_assign"] = $input["id_assign"];
		$this->fields["begin"] = $input["begin"];
		$this->fields["end"] = $input["end"];

		if (!$this->test_valid_date()){
			$this->displayError("date");
			return false;
		}

		if ($this->is_alreadyplanned()){
			$this->displayError("is_res");
			return false;
		}
		// Restore fields
		$this->fields=$oldfields;

		return $input;
	}

	function post_updateItem($input,$updates,$history=1) {
		global $CFG_GLPI;

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
		$tmp_beg=explode(" ",$input["begin"]);
		$tmp_end=explode(" ",$input["end"]);
		$tmp_dbeg=explode("-",$tmp_beg[0]);
		$tmp_dend=explode("-",$tmp_end[0]);
		$tmp_hbeg=explode(":",$tmp_beg[1]);
		$tmp_hend=explode(":",$tmp_end[1]);

		$dateDiff = mktime($tmp_hend[0],$tmp_hend[1],$tmp_hend[2],$tmp_dend[1],$tmp_dend[2],$tmp_dend[0]) 
			- mktime($tmp_hbeg[0],$tmp_hbeg[1],$tmp_hbeg[2],$tmp_dbeg[1],$tmp_dbeg[2],$tmp_dbeg[0]);		
		$updates2[]="realtime";
		$fup->fields["realtime"]=$dateDiff/60/60;
		$fup->updateInDB($updates2);
		$job->updateRealTime($input["id_tracking"]);

		if ((!isset($input["_nomail"])||$input["_nomail"]==0)
		&&count($updates)>0 && $CFG_GLPI["mailing"]){
			$user=new User;
			$user->getFromDB($_SESSION["glpiID"]);
			$mail = new Mailing("followup",$job,$user,$fup->fields["private"]);
			$mail->send();
		}

	}


	function prepareInputForAdd($input) {
		// Needed for test already planned
		$this->fields["id_assign"] = $input["id_assign"];
		$this->fields["begin"] = $input["begin"];
		$this->fields["end"] = $input["end"];

		if (!$this->test_valid_date()){
			$this->displayError("date");
			return false;
		}

		if ($this->is_alreadyplanned()){
			$this->displayError("is_res");
			return false;
		}

		return $input;
	}


	function post_addItem($newID,$input) {
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
			$tmp_beg=explode(" ",$this->fields["begin"]);
			$tmp_end=explode(" ",$this->fields["end"]);
			$tmp_dbeg=explode("-",$tmp_beg[0]);
			$tmp_dend=explode("-",$tmp_end[0]);
			$tmp_hbeg=explode(":",$tmp_beg[1]);
			$tmp_hend=explode(":",$tmp_end[1]);

			$dateDiff = mktime($tmp_hend[0],$tmp_hend[1],$tmp_hend[2],$tmp_dend[1],$tmp_dend[2],$tmp_dend[0]) 
				- mktime($tmp_hbeg[0],$tmp_hbeg[1],$tmp_hbeg[2],$tmp_dbeg[1],$tmp_dbeg[2],$tmp_dbeg[0]);		
			$updates2[]="realtime";
			$fup->fields["realtime"]=$dateDiff/60/60;
			$fup->updateInDB($updates2);
			$job->updateRealTime($input["id_tracking"]);
		}

		if ((!isset($input["_nomail"])||$input["_nomail"]==0)&&$CFG_GLPI["mailing"]){
			$user=new User;
			$user->getFromDB($_SESSION["glpiID"]);
			$mail = new Mailing("followup",$job,$user,$fup->fields["private"]);
			$mail->send();
		}

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

	/**
	 * Is the user assigned to the current planning already planned ?
	 *
	 *@return boolean
	 **/
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
	/**
	 * Current dates are valid ? begin before end
	 *
	 *@return boolean
	 **/
	function test_valid_date(){
		return (!empty($this->fields["begin"])&&!empty($this->fields["end"])
			&&strtotime($this->fields["begin"])<strtotime($this->fields["end"]));
	}

	/**
	 * Add error message to message after redirect
	 * @param $type error type : date / is_res / other
	 *@return nothing
	 **/
	function displayError($type){
		global $LANG;

		switch ($type){
			case "date":
				addMessageAfterRedirect($LANG["planning"][1]);
			break;
			case "is_res":
				addMessageAfterRedirect($LANG["planning"][0]);
			break;
			default :
				addMessageAfterRedirect($LANG["common"][61]);
			break;
		}
	}

}


?>
