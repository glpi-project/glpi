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
// And Julien Dombre for externals identifications


class DBmysql {

	var $dbhost	= ""; 
	var $dbuser = ""; 
	var $dbpassword	= "";
	var $dbdefault	= "";
	var $dbh;

	function DB()
	{  // Constructor
		$this->dbh = mysql_connect($this->dbhost, $this->dbuser, $this->dbpassword);
		mysql_select_db($this->dbdefault);
	}
	function query($query) {
		return mysql_query($query);
	}
	function result($result, $i, $field) {
		return mysql_result($result, $i, $field);
	}
	function numrows($result) {
		return mysql_num_rows($result);
	}
	function fetch_array($result) {
		return mysql_fetch_array($result);
	}
	function fetch_row($result) {
		return mysql_fetch_row($result);
	}
	function num_fields($result) {
	return mysql_num_fields($result);
	}
	function list_tables() {
	return mysql_list_tables($this->dbdefault);
	}
	function errno()
	{
		return mysql_errno();
	}

	function error()
	{
		return mysql_error();
	}
	function close()
	{
		return mysql_close($this->dbh);
	}
	
}

class Connection {

	var $ID				= 0;
	var $end1			= 0;
	var $end2			= 0;
	var $type			= 0;
	var $device_name	= "";
	var $device_ID		= 0;

	function getComputerContact ($ID) {
		$db = new DB;
		$query = "SELECT * FROM connect_wire WHERE (end1 = '$ID' AND type = '$this->type')";
		if ($result=$db->query($query)) {
			$data = $db->fetch_array($result);
			$this->end2 = $data["end2"];
			return $this->end2;
		} else {
				return false;
		}
	}

	function getComputerData($ID) {
		$db = new DB;
		$query = "SELECT * FROM computers WHERE (ID = '$ID')";
		if ($result=$db->query($query)) {
			$data = $db->fetch_array($result);
			$this->device_name = $data["name"];
			$this->device_ID = $ID;
			return true;
		} else {
			return false;
		}
	}

	function deleteFromDB($ID) {

		$db = new DB;

		$query = "DELETE from connect_wire WHERE (end1 = '$ID' AND type = '$this->type')";
		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
	}

	function addToDB() {
		$db = new DB;

		// Build query
		$query = "INSERT INTO connect_wire (end1,end2,type) VALUES ('$this->end1','$this->end2','$this->type')";
		if ($result=$db->query($query)) {
			return true;
		} else {
			return false;
		}
	}

}

class Identification
{
	var $err;
	var $user;

	//constructor for class Identification
	function Identification()
	{
		//echo "il est passé par ici";
		$this->err = "";
		$this->user = new User;

	}


	//return 1 if the (IMAP/pop) connection to host $host, using login $login and pass $pass
	// is successfull
	//else return 0
	function connection_imap($host,$login,$pass)
	{
		error_reporting(16);
		if($mbox = imap_open($host,$login,$pass))
		//if($mbox)$mbox =
		{
			imap_close($mbox);
			return 1;
		}
		else
		{
			$this->err = imap_last_error();
			imap_close($mbox);
			return 0;
		}
	}


	// void;
	//try to connect to DB
	//update the instance variable user with the user who has the name $name
	//and the password is $password in the DB.
	//If not found or can't connect to DB updates the instance variable err
	//with an eventual error message
	function connection_db_mysql($name,$password)
	{
		$db = new DB;
		$query = "SELECT * from users where (name = '".$name."' && password = PASSWORD('".$password."'))";
		$result = $db->query($query);
		//echo $query;
		if($result)
		{
			if($db->numrows($result))
			{
				$this->user->getFromDB($name);
				return 2;
			}
			else
			{
				$this->err = "Bad username or password";
				return 1;
			}
		}
		else
		{
			$err = "Erreur numero : ".$db->errno().": ";
			$err += $db->error();
			return 0;
		}

	}

	// Set Cookie for this user
	function setCookies()
	{
		$name = $this->user->fields['name'];
		$password = md5($this->user->fields['password']);
		$type = $this->user->fields['type'];
	 	SetCookie("IRMName", $name, 0, "/");
		SetCookie("IRMPass", $password, 0, "/");
	}

	//Add an user to DB or update his password if user allready exist.
	//The default type of the added user will be 'post-only'
	function add_an_user($name, $password, $host)
	{

		// Update user password if already known
		if ($this->connection_db_mysql($name,$password) == 2)
		{
			$update[0]="password";
			$this->user->fields["password"]=$password;
			$this->user->updateInDB($update);

		}// Add user if not known
		else
		{
			// dump status

			$this->user->fields["name"]=$name;
			if(empty($host))
			{
			$this->user->fields["email"]=$name;
			}
			else
			{
			$this->user->fields["email"]=$name."@".$host;
			}
			$this->user->fields["type"]="post-only";
			$this->user->fields["realname"]=$name;
			$this->user->fields["can_assign_job"]="no";
			$this->user->addToDB();
			$update[0]="password";
			$this->user->fields["password"]=$password;
    		$this->user->updateInDB($update);
		}

	}

	function getErr()
	{
		return $this->err;
	}
	function getUser()
	{
		return $this->user;
	}
}
?>
