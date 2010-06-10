<?php
/*
 
 ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
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
	var $contents		= "";
	var $priority		= 0;
	var $isgroup		= "";
	var $uemail		= "";
	var $emailupdates	= "";
	var $num_of_followups	= 0;
	
	function getfromDB ($ID,$purecontent) {

		$this->ID = $ID;

		// Make new database object and fill variables
		$db = new DB;
		$query = "SELECT * FROM tracking WHERE (ID = $ID)";

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
			$this->contents = StripSlashes($this->contents);
			$this->priority = $db->result($result, 0, "priority");
			$this->is_group = $db->result($result,0, "is_group");
			$this->uemail = $db->result($result, 0, "uemail");
			$this->emailupdates = $db->result($result, 0, "emailupdates");
		
			// Set computername
			if ($this->is_group == "yes") {
				$scndquery = "SELECT name FROM groups WHERE (ID = $this->computer)";
			} else {
				$scndquery = "SELECT name FROM computers WHERE (ID = $this->computer)";
			}
			$scndresult = $db->query($scndquery);
			if ($db->numrows($scndresult)) {
				$this->computername = $db->result($scndresult, 0, "name");
			} else {
				$this->computername = "n/a";
			}		
			// Set number of followups
			$thrdquery = "SELECT * FROM followups WHERE (tracking = $this->ID)";
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
		if (!$this->status) {
			$this->closedate = date("Y-m-d H:i:s");
		}
		$this->contents = addslashes($this->contents);
		
		// dump into database
		$db = new DB;
		$query = "INSERT INTO tracking VALUES (NULL, '$this->date', '', '$this->status','$this->author', NULL, $this->computer, '$this->contents', '$this->priority', '$this->isgroup','$this->uemail', '$this->emailupdates')";

		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
	}
	

	function updateStatus($status) {
		// update Status of Job
		
		$db = new DB;
		$query = "UPDATE tracking SET status = '$status' WHERE ID = $this->ID";
		if ($result = $db->query($query)) {
			$query = "UPDATE tracking SET closedate = NOW() WHERE ID = $this->ID";
			if ($result = $db->query($query)) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}


	function assignTo($user) {
		// assign Job to user
		
		$db = new DB;
		$query = "UPDATE tracking SET assign = '$user' WHERE ID = '$this->ID'";
		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
	}


	function textFollowups(&$emailmessage) {
		// get the last followup for this job and give its contents as
	
		$message .= "\nFollowups:\n\n";
		
		for ($i=0; $i < $this->num_of_followups; $i++) {
			$fup = new Followup;
			$fup->getFromDB($this->ID,$i);
			$emailmessage .= "[ ".$fup->date." ]\n";
			$emailmessage .= "Author: ".$fup->author."\n";
			$emailmessage .= $fup->contents."\n\n";
		}
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
		$query = "SELECT * FROM followups WHERE (tracking = $ID) ORDER BY date ASC";
	
		if ($result = $db->query($query)) {
			$this->date = $db->result($result,$iteration,"date");
			$this->author = $db->result($result, $iteration, "author");
			$this->contents = nl2br($db->result($result, $iteration, "contents"));
			$this->contents = StripSlashes($this->contents);

			return true;

		} else {
			return false;
		}
	}

	function putInDB () {	
		// prepare variables

		$this->date = date("Y-m-d H:i:s");
		$this->contents = addslashes($this->contents);
	
		// dump into database
		$db = new DB;
		$query = "INSERT INTO followups VALUES (NULL, $this->tracking, '$this->date','$this->author', '$this->contents')";
		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
	}
	
	function logFupUpdate () {
		// log event
		
		$db = new DB;
		$query = "SELECT * FROM tracking WHERE (ID = $this->tracking)";
		
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
