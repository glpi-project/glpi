<?php
/*
 
  ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
 Bazile Lebeau, baaz@indepnet.net - Jean-Mathieu Doléans, jmd@indepnet.net
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------
 Based on:
IRMA, Information Resource-Management and Administration
Christian Bauer, turin@incubus.de 

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
 ----------------------------------------------------------------------
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
*/

include ("_relpos.php");
// Tracking Classes

class Job {

	var $ID			= 0;
	var $date		= "";
	var $closedate		= "";
	var $status		= "";
	var $author		= "";
	var $assign		= "";
	var $computer		= 0;
	var $computername	= "";
	var $computerfound	= 0;
	var $contents		= "";
	var $priority		= 0;
	var $isgroup		= "";
	var $uemail		= "";
	var $emailupdates	= "";
	var $num_of_followups	= 0;
	var $realtime	= 0;
	
	
	function getfromDB ($ID,$purecontent) {

		$this->ID = $ID;

		// Make new database object and fill variables
		$db = new DB;
		$query = "SELECT * FROM glpi_tracking WHERE (ID = $ID)";

		if ($result = $db->query($query)) {
			$resultnum = $db->numrows($result);
			$this->date = $db->result($result,0,"date");
			$this->closedate = $db->result($result, 0, "closedate");
			$this->status = $db->result($result, 0, "status");
			$this->author = $db->result($result, 0, "author");
			$this->assign = $db->result($result, 0, "assign");
			$this->computer = $db->result($result, 0, "computer");
			if (!$purecontent) {
				$this->contents = nl2br($db->result($result, 0, "contents"));
			}
			$this->contents = $this->contents;
			$this->priority = $db->result($result, 0, "priority");
			$this->is_group = $db->result($result,0, "is_group");
			$this->uemail = $db->result($result, 0, "uemail");
			$this->emailupdates = $db->result($result, 0, "emailupdates");
			$this->realtime = $db->result($result, 0, "realtime");
		
			// Set computername
			if ($this->is_group == "yes") {
				$scndquery = "SELECT name FROM glpi_groups WHERE (ID = $this->computer)";
			} else {
				$scndquery = "SELECT name FROM glpi_computers WHERE (ID = $this->computer)";
			}
			$scndresult = $db->query($scndquery);
			if ($db->numrows($scndresult)) {
				$this->computername = $db->result($scndresult, 0, "name");
				$this->computerfound=1;
			} else {
				$this->computername = "N/A";
				$this->computerfound=0;				
			}		
			// Set number of followups
			$thrdquery = "SELECT * FROM glpi_followups WHERE (tracking = $this->ID)";
			$thrdresult = $db->query($thrdquery);
			$this->num_of_followups = $db->numrows($thrdresult);
			
			return true;

		} else {
			return false;
		}
	}

	function putinDB () {	
		// prepare variables

		$this->date = date("Y-m-d H:i:s");

		if ($this->status=="old") {
			$this->closedate = date("Y-m-d H:i:s");
		}
		
		// dump into database
		$db = new DB;
		$query = "INSERT INTO glpi_tracking VALUES (NULL, '$this->date', '$this->closedate', '$this->status','$this->author', '$this->assign', $this->computer, '$this->contents', '$this->priority', '$this->isgroup','$this->uemail', '$this->emailupdates','$this->realtime')";

		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
	}
	

	function updateStatus($status) {
		// update Status of Job
		
		$db = new DB;
		$query = "UPDATE glpi_tracking SET status = '$status' WHERE ID = $this->ID";
		if ($result = $db->query($query)) {
			$this->closedate=date("Y-m-d G:i:s");
			$query = "UPDATE glpi_tracking SET closedate = NOW() WHERE ID = $this->ID";
			if ($result = $db->query($query)) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	function updateRealtime($realtime) {
		// update Status of Job
		
		$db = new DB;
		$query = "UPDATE glpi_tracking SET realtime = '$realtime' WHERE ID = $this->ID";
		if ($result = $db->query($query)) {
				return true;
		} else {
			return false;
		}
	}
	

	function assignTo($user) {
		// assign Job to user
		
		$db = new DB;
		$this->assign=$user;
		$query = "UPDATE glpi_tracking SET assign = '$user' WHERE ID = '$this->ID'";
		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
	}

	function textFollowups() {
		// get the last followup for this job and give its contents as
		GLOBAL $lang;
	
		$message = $lang["mailing"][1]."\n".$lang["mailing"][4]." (".$this->num_of_followups.")"."\n".$lang["mailing"][1]."\n";
		
		for ($i=0; $i < $this->num_of_followups; $i++) {
			$fup = new Followup;
			$fup->getFromDB($this->ID,$i);
			$message .= "[ ".$fup->date." ]\n";
			$message .= $lang["mailing"][2].$fup->author."\n";
			$message .= $lang["mailing"][3]."\n".$fup->contents."\n".$lang["mailing"][0]."\n";
		}
		return $message;
	}
	
	function textDescription(){
		GLOBAL $lang;
		
		if ($this->computername==""){
			$db=new DB;
			$scndquery = "SELECT name FROM glpi_computers WHERE (ID = $this->computer)";
			$scndresult = $db->query($scndquery);
			if ($db->numrows($scndresult)) {
				$this->computername = $db->result($scndresult, 0, "name");
			} else {
				$this->computername = "n/a";
			}		
		}
		
		$message = $lang["mailing"][1]."\n".$lang["mailing"][5]."\n".$lang["mailing"][1]."\n";
		$message.= $lang["mailing"][2].$this->author."\n";
		$message.= $lang["mailing"][6].$this->date."\n";
		$message.= $lang["mailing"][7].$this->computername."\n";
		$message.= $lang["mailing"][8].$this->assign."\n";
		$message.= $lang["mailing"][3]."\n".$this->contents."\n";	
		$message.="\n\n";
		return $message;
	}
	
	function deleteInDB ($ID) {
		if ($ID!=""){
			$db=new DB;
			$query1="delete from glpi_followups where tracking = '$ID'";
			$query2="delete from glpi_tracking where ID = '$ID'";
			if (!$db->query($query1))
			 return false;
			if(!$db->query($query2));
			 return false;
			 return true;
			}
			 return false;		
	}
}


class Followup {
	
	var $ID		= 0;
	var $tracking	= 0;
	var $date	= "";
	var $author	= "";
	var $contents	= "";

	function getfromDB ($ID,$iteration) {

		$this->ID = $ID;

		// Make new database object and fill variables
		$db = new DB;
		$query = "SELECT * FROM glpi_followups WHERE (tracking = $ID) ORDER BY date ASC";
	
		if ($result = $db->query($query)) {
			$this->date = $db->result($result,$iteration,"date");
			$this->author = $db->result($result, $iteration, "author");
			$this->contents = nl2br($db->result($result, $iteration, "contents"));

			return true;

		} else {
			return false;
		}
	}

	function putInDB () {	
		// prepare variables

		$this->date = date("Y-m-d H:i:s");
	
		// dump into database
		$db = new DB;
		$query = "INSERT INTO glpi_followups VALUES (NULL, $this->tracking, '$this->date','$this->author', '$this->contents')";
		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
	}
	
	function logFupUpdate () {
		// log event
		
		$db = new DB;
		$query = "SELECT * FROM glpi_tracking WHERE (ID = $this->tracking)";
		
		if ($result = $db->query($query)) {
			$cID = $db->result($result, 0, "computer");
			logEvent($cID, "computers", 4, "tracking", "$this->author added followup to job $this->tracking.");
			return true;
		} else {
			return false;
		}
		
	}


}



?>
