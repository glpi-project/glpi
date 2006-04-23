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

// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
// Tracking Classes

class Job extends CommonDBTM{

	var $fields	= array();
	var $updates	= array();
	var $computername	= "";
	var $computerfound	= 0;
	
	function Job(){
		$this->table="glpi_tracking";
		$this->type=TRACKING_TYPE;
	}

	function getFromDBwithData ($ID,$purecontent) {

		global $db,$lang;
		
		if ($this->getFromDB($ID)){

			if (!$purecontent) {
				$this->fields["contents"] = nl2br(preg_replace("/\r\n\r\n/","\r\n",$this->fields["contents"]));
			}
			$m= new CommonItem;
			if ($m->getfromDB($this->fields["device_type"],$this->fields["computer"])){
				$this->computername=$m->getName();
			} else $this->computername='';
			
			if ($this->computername==""){
				if ($this->fields["device_type"]==0) $this->computername = $lang["help"][30];
				else $this->computername = "N/A";
				$this->computerfound=0;				
			} else 	$this->computerfound=1;	

			return true;
		} else {
			return false;
		}
	}

	function numberOfFollowups($with_private=1){
		global $db;
		$RESTRICT="";
		if ($with_private!=1) $RESTRICT = " AND private='0'";
		// Set number of followups
		$query = "SELECT count(*) FROM glpi_followups WHERE (tracking = ".$this->fields["ID"].") $RESTRICT";
		$result = $db->query($query);
		return $db->result($result,0,0);

	}

	function updateInDB($updates)  {

		global $db;

		for ($i=0; $i < count($updates); $i++) {
			$query  = "UPDATE glpi_tracking SET ";
			$query .= $updates[$i];
			$query .= "='";
			$query .= $this->fields[$updates[$i]];
			$query .= "' WHERE ID='";
			$query .= $this->fields["ID"];	
			$query .= "'";
			$result=$db->query($query);
		}
	}

	function addToDB() {
		
		global $db;

		// Build query
		$query = "INSERT INTO glpi_tracking (";
		$i=0;
		
		foreach ($this->fields as $key => $val) {
			$fields[$i] = $key;
			$values[$i] = $val;
			$i++;
		}		
		for ($i=0; $i < count($fields); $i++) {
			$query .= $fields[$i];
			if ($i!=count($fields)-1) {
				$query .= ",";
			}
		}
		$query .= ") VALUES (";
		for ($i=0; $i < count($values); $i++) {
			$query .= "'".$values[$i]."'";
			if ($i!=count($values)-1) {
				$query .= ",";
			}
		}
		$query .= ")";
		$result=$db->query($query);
		return $db->insert_id();
	}	

	function updateRealtime() {
		// update Status of Job
		
		global $db;
		$query = "SELECT SUM(realtime) FROM glpi_followups WHERE tracking = '".$this->fields["ID"]."'";
		if ($result = $db->query($query)) {
				$query2="UPDATE glpi_tracking SET realtime='".$db->result($result,0,0)."' WHERE ID='".$this->fields["ID"]."'";
				$db->query($query2);
				return true;
		} else {
			return false;
		}
	}
	

	function textFollowups($format="text") {
		// get the last followup for this job and give its contents as
		GLOBAL $db,$lang;
		
		if (isset($this->fields["ID"])){
		$query = "SELECT * FROM glpi_followups WHERE tracking = '".$this->fields["ID"]."' AND private = '0' ORDER by date DESC";
		$result=$db->query($query);
		$nbfollow=$db->numrows($result);
		if($format=="html"){
			$message = $lang["mailing"][1]."<br>".$lang["mailing"][4]." : $nbfollow<br>".$lang["mailing"][1]."<br>";
			
			if ($nbfollow>0){
				$fup=new Followup();
				while ($data=$db->fetch_array($result)){
						$fup->getfromDB($data['ID']);
						$message .= "[ ".convDateTime($fup->fields["date"])." ]<br>";
						$message .= $lang["mailing"][2]." ".$fup->getAuthorName()."<br>";
						$message .= $lang["mailing"][3]."<br>".$fup->fields["contents"]."<br>";
						if ($fup->fields["realtime"]>0)
							$message .= $lang["mailing"][104]." ".getRealtime($fup->fields["realtime"])."<br>";
	
						$message.=$lang["mailing"][25]." ";
						$query2="SELECT * from glpi_tracking_planning WHERE id_followup='".$data['ID']."'";
						$result2=$db->query($query2);
						if ($db->numrows($result2)==0)
					$message.=$lang["job"][32]."<br>";
						else {
							$data2=$db->fetch_array($result2);
							$message.=convDateTime($data2["begin"])." -> ".convDateTime($data2["end"])."<br>";
						}
						
						$message.=$lang["mailing"][0]."<br>";	
				}	
			}
		}else{ // text format
			$message = $lang["mailing"][1]."\n".$lang["mailing"][4]." : $nbfollow\n".$lang["mailing"][1]."\n";
			
			if ($nbfollow>0){
				$fup=new Followup();
				while ($data=$db->fetch_array($result)){
						$fup->getfromDB($data['ID']);
						$message .= "[ ".convDateTime($fup->fields["date"])." ]\n";
						$message .= $lang["mailing"][2]." ".$fup->getAuthorName()."\n";
						$message .= $lang["mailing"][3]."\n".$fup->fields["contents"]."\n";
						if ($fup->fields["realtime"]>0)
							$message .= $lang["mailing"][104]." ".getRealtime($fup->fields["realtime"])."\n";
	
						$message.=$lang["mailing"][25]." ";
						$query2="SELECT * from glpi_tracking_planning WHERE id_followup='".$data['ID']."'";
						$result2=$db->query($query2);
						if ($db->numrows($result2)==0)
					$message.=$lang["job"][32]."\n";
						else {
							$data2=$db->fetch_array($result2);
							$message.=convDateTime($data2["begin"])." -> ".convDateTime($data2["end"])."\n";
						}
						
						$message.=$lang["mailing"][0]."\n";	
				}	
			}


		}
		return $message;
		} else return "";
	}
	
	function textDescription($format="text"){
		GLOBAL $db,$lang;
		
		
		$m= new CommonItem;
		$name=$lang["help"][30];
		$contact="";
		if ($m->getfromDB($this->fields["device_type"],$this->fields["computer"])){
			$name=$m->getType()." ".$m->getName();
			if (isset($m->obj->fields["contact"]))
				$contact=$m->obj->fields["contact"];
		}
		
		if($format=="html"){
			$message= "<html><head> <style type=\"text/css\">";
			$message.=".description{ color: inherit; background: #ebebeb; border-style: solid; border-color: #8d8d8d; border-width: 0px 1px 1px 0px; }";
			$message.=" </style></head><body>";
			
			 $message.="<div class='description'><strong>".$lang["mailing"][5]."</strong></div><br>";
			$author=$this->getAuthorName();
			if (empty($author)) $author=$lang["mailing"][108];
			$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$lang["mailing"][2]."</span> ".$author."<br>";
			$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>". $lang["mailing"][6]."</span> ".convDateTime($this->fields["date"])."<br>";
			$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>". $lang["mailing"][7]."</span> ".$name."<br>";
			$message.= "<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$lang["mailing"][24]."</span> ".getStatusName($this->fields["status"])."<br>";
			$assign=getAssignName($this->fields["assign"],USER_TYPE);
			if ($assign=="[Nobody]")
				$assign=$lang["mailing"][105];
			$message.= "<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$lang["mailing"][8]."</span> ".$assign."<br>";
			$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$lang["mailing"][16]."</span> ".getPriorityName($this->fields["priority"])."<br>";
			if ($this->fields["device_type"]!=SOFTWARE_TYPE)
				$message.= "<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$lang["mailing"][28]."</span> ".$contact."<br>";
			if ($this->fields["emailupdates"]=="yes"){
				$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$lang["mailing"][103]."</span> ".$lang["choice"][1]."<br>";
			} else {
				$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$lang["mailing"][103]."</span> ".$lang["choice"][0]."<br>";
			}
			
			$message.= "<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$lang["mailing"][26]."</span> ";
			if (isset($this->fields["category"])&&$this->fields["category"]){
				$message.= getDropdownName("glpi_dropdown_tracking_category",$this->fields["category"]);
			} else $message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>".$lang["mailing"][100]."</span>";
			$message.= "<br>";
			
			$message.="<span style='color:#8B8C8F; font-weight:bold;  text-decoration:underline; '>". $lang["mailing"][3]."</span><br>".$this->fields["contents"]."<br><br>";	
			
		}else{ //text format
			$message = $lang["mailing"][1]."\n*".$lang["mailing"][5]."*\n".$lang["mailing"][1]."\n";
			$author=$this->getAuthorName();
			if (empty($author)) $author=$lang["mailing"][108];
			$message.= $lang["mailing"][2]." ".$author."\n";
			$message.= $lang["mailing"][6]." ".convDateTime($this->fields["date"])."\n";
			$message.= $lang["mailing"][7]." ".$name."\n";
			$message.= $lang["mailing"][24]." ".getStatusName($this->fields["status"])."\n";
			$assign=getAssignName($this->fields["assign"],USER_TYPE);
			if ($assign=="[Nobody]")
				$assign=$lang["mailing"][105];
			$message.= $lang["mailing"][8]." ".$assign."\n";
			$message.= $lang["mailing"][16]." ".getPriorityName($this->fields["priority"])."\n";
			if ($this->fields["device_type"]!=SOFTWARE_TYPE)
				$message.= $lang["mailing"][28]." ".$contact."\n";
			if ($this->fields["emailupdates"]=="yes"){
				$message.=$lang["mailing"][103]." ".$lang["choice"][1]."\n";
			} else {
				$message.=$lang["mailing"][103]." ".$lang["choice"][0]."\n";
			}
			
			$message.= $lang["mailing"][26]." ";
			if (isset($this->fields["category"])&&$this->fields["category"]){
				$message.= getDropdownName("glpi_dropdown_tracking_category",$this->fields["category"]);
			} else $message.=$lang["mailing"][100];
			$message.= "\n";
			
			$message.= $lang["mailing"][3]."\n".$this->fields["contents"]."\n";	
			$message.="\n\n";

		}

		return $message;
	}
	

	function cleanDBonPurge($ID) {
		global $db;

		$query="SELECT ID FROM glpi_followups WHERE tracking = '$ID'";
		$result=$db->query($query);
		if ($db->numrows($result)>0)
		while ($data=$db->fetch_array($result)){
			$querydel="DELETE FROM glpi_tracking_planning WHERE id_followup = '".$data['ID']."'";
			$db->query($querydel);				
		}
		$query1="delete from glpi_followups where tracking = '$ID'";
		$db->query($query1);

	}


	function getAuthorName($link=0){
	
	return getUserName($this->fields["author"],$link);
	}
	
}


class Followup  extends CommonDBTM {
	
	function Followup () {
		$this->table="glpi_followups";
	}


	function post_addToDB(){

		if (isset($this->fields["realtime"])&&$this->fields["realtime"]>0) {
			$job=new Job();
			$job->getfromDB($this->fields["tracking"]);
			$job->updateRealTime();
		}
	}
	
	function post_updateInDB($updates)  {

		for ($i=0; $i < count($updates); $i++) {
			if ($updates[$i]=="realtime") {
				$job=new Job();
				$job->getfromDB($this->fields["tracking"]);
				$job->updateRealTime();
			}
		}
	}

	function cleanDBonPurge($ID) {
		global $db;
		$querydel="DELETE FROM glpi_tracking_planning WHERE id_followup = '$ID'";
		$db->query($querydel);				
	}

	function prepareInputForUpdate($input) {
		$input["realtime"]=$input["hour"]+$input["minute"]/60;
		$input["author"]=$_SESSION["glpiID"];

		return $input;
	}

	function post_updateItem($input,$updates,$history=1) {
		global $cfg_glpi;
		$job=new Job;
		$job->getFromDBwithData($input["tracking"],1);

		if (in_array("contents",$updates)&&$cfg_glpi["mailing"]){
			$user=new User;
			$user->getfromDBbyName($_SESSION["glpiname"]);
			$mail = new Mailing("followup",$job,$user);
			$mail->send();
		}
	}

	function add($input,$type="followup"){
		global $cfg_glpi;

		$isadmin=haveRight("comment_all_ticket","1");

		$job=new Job;
		$job->getFromDB($input["tracking"]);

		// Security to add unauthorized followups
		if (!$isadmin&&$job->fields["author"]!=$_SESSION["glpiID"]) return;

		$close=0;
		unset($input["add"]);
	
		$input["author"]=$_SESSION["glpiID"];

		if ($isadmin&&$type!="update"&&$type!="finish"){
			if (isset($input['plan'])){
			$plan=$input['plan'];
			unset($input['plan']);
			}	
			if (isset($input["add_close"])) $close=1;
			unset($input["add_close"]);
	
			if ($input["hour"]>0||$input["minute"]>0)
			$input["realtime"]=$input["hour"]+$input["minute"]/60;
		}

		unset($input["minute"]);
		unset($input["hour"]);

		$input["date"] = date("Y-m-d H:i:s");
		foreach ($input as $key => $val) {
			if ($key[0]!='_'&&(empty($this->fields[$key]) || $this->fields[$key] != $input[$key])) {
				$this->fields[$key] = $input[$key];
			}
		}
		$newID=$this->addToDB();	

		if ($isadmin&&$type!="update"&&$type!="finish"){
			if (isset($plan)){
				$plan['id_followup']=$newID;
				$plan['id_tracking']=$input['tracking'];
				$pt=new PlanningTracking();
				if (!$pt->add($plan,"",1)){
					return false;
				}
			}


			if ($close&&$type!="update"&&$type!="finish"){
				$updates[]="status";
				$updates[]="closedate";
				$job->fields["status"]="old_done";
				$job->fields["closedate"] = date("Y-m-d H:i:s");
				$job->updateInDB($updates);
			}

			$job->updateRealtime();		
		}

		if ($cfg_glpi["mailing"]){
			if ($close) $type="finish";
			$user=new User;
			$user->getfromDBbyName($_SESSION["glpiname"]);
			$mail = new Mailing($type,$job,$user);
			$mail->send();
		}
		return $newID;
	}


	function delete($input) {
		// Delete Contact
	
		$this->deleteFromDB($input["ID"]);

		$job=new Job();
		$job->getFromDB($input['tracking']);
		$job->updateRealtime();		

	} 

	// SPECIFIC FUNCTIONS
	
	function getAuthorName($link=0){
	return getUserName($this->fields["author"],$link);
	}	
	

}



?>
