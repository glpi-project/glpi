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
// And Julien Dombre for externals identifications
// And Marco Gaiarin for ldap features
*/


class DBmysql {

	var $dbhost	= ""; 
	var $dbuser = ""; 
	var $dbpassword	= "";
	var $dbdefault	= "";
	var $dbh;
	var $error = 0;

	function DB()
	{  // Constructor
		$this->dbh = mysql_connect($this->dbhost, $this->dbuser, $this->dbpassword) or $this->error = 1;
		mysql_select_db($this->dbdefault) or $this->error = 1;
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
	function fetch_assoc($result) {
		return mysql_fetch_assoc($result);
	}
	function num_fields($result) {
		return mysql_num_fields($result);
	}
	function field_name($result,$nb)
	{
		return mysql_field_name($result,$nb);
	}
	function list_tables() {
		return mysql_list_tables($this->dbdefault);
	}
	function list_fields($table) {
		return mysql_list_fields($this->dbdefault,$table);
	}
	function affected_rows() {
		return mysql_affected_rows($this->dbh);
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
		$query = "SELECT * FROM glpi_connect_wire WHERE (end1 = '$ID' AND type = '$this->type')";
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
		$query = "SELECT * FROM glpi_computers WHERE (ID = '$ID')";
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

		$query = "DELETE from glpi_connect_wire WHERE (end1 = '$ID' AND type = '$this->type')";
		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
	}

	function addToDB() {
		$db = new DB;

		// Build query
		$query = "INSERT INTO glpi_connect_wire (end1,end2,type) VALUES ('$this->end1','$this->end2','$this->type')";
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
	function Identification($name)
	{
		$this->err = "";
		$this->user = new User($name);
	}


	//return 1 if the (IMAP/pop) connection to host $host, using login $login and pass $pass
	// is successfull
	//else return 0
	function connection_imap($host,$login,$pass)
	{
		// we prevent some delay...
		if (empty($host)) {
			return false;
		}

		error_reporting(16);
		if($mbox = imap_open($host,$login,$pass))
		//if($mbox)$mbox =
		{
			imap_close($mbox);
			return true;
		}

		$this->err = imap_last_error();
		imap_close($mbox);
		return false;
	}

  // return 1 if the connection to the LDAP host, auth mode, was successful
  // $condition is used to restrict login ($condition is set in glpi/config/config.php 
  function connection_ldap($host,$basedn,$login,$pass,$condition)
  {
		// we prevent some delay...
		if (empty($host)) {
			return false;
		}
  	error_reporting(16);
  	$dn = "uid=" . $login . "," . $basedn;
  	$rv = false;
  	if ( $conn = ldap_connect($host) )
  	{
  		// switch to protocol version 3 to make ssl work
  		ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3) ;
//  		if ( ldap_bind($conn, $dn, $pass) ) {
//  			$rv = true;

  		if (ldap_bind($conn, $dn, $pass) ) {
                     $filter="(uid=$login)";
                     if ($condition!="") $filter="(& $filter $condition)";
                     $sr=ldap_search($conn, $basedn, $filter);
                     $info = ldap_get_entries ( $conn, $sr );
                     if ( $info["count"] == 1 )
                     {
                        $rv=true;
                     }
                     else
                     {
                       $this->err = "Not allowed to log in";
                     }
  		}
  		else
  		{
  			$this->err = ldap_error();
  		}
  		ldap_close($conn);
  	}
  	else
  	{
  		$this->err = ldap_error();
  	}
  	
  	return($rv);

  } // connection_ldap()
 
 
// Gets the dn using anonymous Ldap login
 function ldap_get_dn($host,$ldap_base_dn,$login,$rdn,$rpass)
 {

  // we prevent some delay...
  if (empty($host)) {
	return false;
  }

  $ldap_server=$host;
  $ldap_login_attr = "uid";                          
  $ldap_dn ="";
	error_reporting(16);
  $ds = ldap_connect ($ldap_server);
  if (!$ds)
    {
     return $false;
    }
  ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3) ;
  if ($rdn=="") $r = ldap_bind ( $ds);
  else $r = ldap_bind ( $ds,$rdn,$rpass);
  if (!$r)
      {
       ldap_close ( $ds );
       return false;
      }
      
    $sr = ldap_search ($ds, $ldap_base_dn, "($ldap_login_attr=$login)");

    if (!$sr)
       {
       ldap_close ( $ds );
       return false;
       }
       
    $info = ldap_get_entries ( $ds, $sr );

    if ( $info["count"] != 1 )
       {
       ldap_free_result ( $sr );
       ldap_close ( $ds );
       return false;
       }
   ldap_free_result ( $sr );
   ldap_close ( $ds );
   $thedn=explode(",", $info[0]["dn"]);
   unset($thedn[0]);
   return implode(",",$thedn);
  }  		 // ldap_get_dn()
 		

	// void;
	//try to connect to DB
	//update the instance variable user with the user who has the name $name
	//and the password is $password in the DB.
	//If not found or can't connect to DB updates the instance variable err
	//with an eventual error message
	function connection_db($name,$password)
	{

		// sanity check... we prevent empty passwords...
		//
		if ( empty($password) )
		{
			$this->err = "Empty Password";
			return false;
		}
		
		$db = new DB;
		$query = "SELECT password from glpi_users where (name = '".$name."')";
		$result = $db->query($query);
		$query2 = "SELECT PASSWORD('".$password."') as password";
		$result2 = $db->query($query2);
		
		if($result&&$result2)
		{
			if($db->numrows($result) == 1&&$db->numrows($result2) == 1)
			{
				$pass1=$db->result($result,0,"password");
				$pass2=$db->result($result2,0,"password");
				if ($pass1==$pass2)
				return true;
				else {
				$this->err = "Bad username or password";
				return false;
				}
			}
			else
			{
				$this->err = "Bad username or password";
				return false;
			}
		}

		$this->err = "Erreur numero : ".$db->errno().": ";
		$this->err += $db->error();
		return false;

	} // connection_db()


	// Set Cookie for this user
	function setCookies()
	{
		$name = $this->user->fields['name'];
		$password = md5($this->user->fields['password']);
		$type = $this->user->fields['type'];
		$language = $this->user->prefs['language'];
		$tracking_order = $this->user->prefs['tracking_order'];
		//echo $tracking_order;
		session_start();
		$_SESSION["glpipass"] = $password;
		$_SESSION["glpiname"] = $name;
		$_SESSION["glpitype"] = $type;
		$_SESSION["glpilanguage"] = $language;
		$_SESSION["tracking_order"] = $tracking_order;
		$_SESSION["authorisation"] = true;
	}

	function eraseCookies()
	{
		$_SESSION = array();
		session_destroy();
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

class Mailing
{
	var $type=NULL;
	var $job=NULL;
	// User who change the status of the job
	var $user=NULL;
 
	function Mailing ($type="",$job=NULL,$user=NULL)
	{
		$this->type=$type;
		$this->job=$job;
		$this->user=$user;
//		$this->test_type();
	}
	function is_valid_email($email="")
	{
		if( !eregi( "^" .
			"[a-z0-9]+([_\\.-][a-z0-9]+)*" .    //user
            "@" .
            "([a-z0-9]+([\.-][a-z0-9]+)*)+" .   //domain
            "\\.[a-z]{2,}" .                    //sld, tld 
            "$", $email)
                        )
        {
        //echo "Erreur: '$email' n'est pas une adresse mail valide!<br>";
        return false;
        }
		else return true;
	}
	function test_type()
	{
		if (!(get_class($this->job)=="Job"))
			$this->job=NULL;
		if (!(get_class($this->user)=="User"))
			$this->user=NULL;	
	}
	// Return array of emails of people to send mail
	function get_users_to_send_mail()
	{
		GLOBAL $cfg_mailing;
		
		$emails=array();
		$nb=0;
		$db = new DB;
		
		if ($cfg_mailing[$this->type]["admin"]&&$this->is_valid_email($cfg_mailing["admin_email"])&&!in_array($cfg_mailing["admin_email"],$emails))
		{
			$emails[$nb]=$cfg_mailing["admin_email"];
			$nb++;
		}

		if ($cfg_mailing[$this->type]["all_admin"])
		{
			$query = "SELECT email FROM glpi_users WHERE (".searchUserbyType("admin").")";
			if ($result = $db->query($query)) 
			{
				while ($row = $db->fetch_row($result))
				{
					// Test du format du mail et de sa non existance dans la table
					if ($this->is_valid_email($row[0])&&!in_array($row[0],$emails))
					{
						$emails[$nb]=$row[0];
						$nb++;
					}
				}
			}
		}	

		if ($cfg_mailing[$this->type]["all_normal"])
		{
			$query = "SELECT email FROM glpi_users WHERE (".searchUserbyType("normal").")";
			if ($result = $db->query($query)) 
			{
				while ($row = $db->fetch_row($result))
				{
					// Test du format du mail et de sa non existance dans la table
					if ($this->is_valid_email($row[0])&&!in_array($row[0],$emails))
					{
						$emails[$nb]=$row[0];
						$nb++;
					}
				}
			}
		}	

		if ($cfg_mailing[$this->type]["attrib"]&&$this->job->assign)
		{
			$query2 = "SELECT email FROM glpi_users WHERE (name = '".$this->job->assign."')";
			if ($result2 = $db->query($query2)) 
			{
				if ($db->numrows($result2)==1)
				{
					$row2 = $db->fetch_row($result2);
					if ($this->is_valid_email($row2[0])&&!in_array($row2[0],$emails))
						{
							$emails[$nb]=$row2[0];
							$nb++;
						}
				}
			}
		}

		if ($cfg_mailing[$this->type]["user"]&&$this->job->emailupdates=="yes")
		{
			if ($this->is_valid_email($this->job->uemail)&&!in_array($this->job->uemail,$emails))
			{
				$emails[$nb]=$this->job->uemail;
				$nb++;
			}
		}
		return $emails;
	}

	// Format the mail body to send
	function get_mail_body()
	{
		// Create message body from Job and type
		$body="";
		
		$body.=$this->job->textDescription();
		if ($this->type!="new") $body.=$this->job->textFollowups();
		
		return $body;
	}
	// Format the mail subject to send
	function get_mail_subject()
	{
		GLOBAL $lang;
		
		// Create the message subject 
		$subject="";
		switch ($this->type){
			case "new":
			$subject.=$lang["mailing"][9];
				break;
			case "attrib":
			$subject.=$lang["mailing"][12];
				break;
			case "followup":
			$subject.=$lang["mailing"][10];
				break;
			case "finish":
			$subject.=$lang["mailing"][11].$this->job->closedate;			
				break;
			default :
			$subject.=$lang["mailing"][13];
				break;
		}
		
		if ($this->type!="new") $subject .= " (ref ".$this->job->ID.")";		
		
		return $subject;
	}
	
	function get_reply_to_address ()
	{
		GLOBAL $cfg_mailing;
	$replyto="";

	switch ($this->type){
			case "new":
				if ($this->is_valid_email($this->job->uemail)) $replyto=$this->job->uemail;
				else $replyto=$cfg_mailing["admin_email"];
				break;
			case "followup":
				if ($this->is_valid_email($this->user->fields["email"])) $replyto=$this->user->fields["email"];
				else $replyto=$cfg_mailing["admin_email"];
				break;
			default :
				$replyto=$cfg_mailing["admin_email"];
				break;
		}
	return $replyto;		
	}
	// Send email 
	function send()
	{
		GLOBAL $cfg_features,$cfg_mailing;
		if ($cfg_features["mailing"]&&$this->is_valid_email($cfg_mailing["admin_email"]))
		{
			if (!is_null($this->job)&&!is_null($this->user)&&(strcmp($this->type,"new")||strcmp($this->type,"attrib")||strcmp($this->type,"followup")||strcmp($this->type,"finish")))
			{
				// get users to send mail
				$users=$this->get_users_to_send_mail();
				// get body + signature OK
				$body=$this->get_mail_body()."\n".$cfg_mailing["signature"];
				$body=ereg_replace("<br />","",$body);
				$body=ereg_replace("<br>","",$body);
				$body=stripslashes($body);

				// get subject OK
				$subject=$this->get_mail_subject();
				// get sender :  OK
				$sender= $cfg_mailing["admin_email"];
				// get reply-to address : user->email ou job_email if not set OK
				$replyto=$this->get_reply_to_address ();
				// Send all mails
				for ($i=0;$i<count($users);$i++)
				{
				mail($users[$i],$subject,$body,
				"From: $sender\r\n" .
			    "Reply-To: $replyto\r\n" .
     		   "X-Mailer: PHP/" . phpversion()) ;
				}
			} else {
				echo "Type d'envoi invalide";
			}
		}
	}



}
?>
