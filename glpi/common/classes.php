<?php
/*
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2005 by the INDEPNET Development Team.
 
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
// And Julien Dombre for externals identifications
// And Marco Gaiarin for ldap features

class DBmysql {

	var $dbhost	= ""; 
	var $dbuser = ""; 
	var $dbpassword	= "";
	var $dbdefault	= "";
	var $dbh;
	var $error = 0;

	function DBmysql()
	{  // Constructor
		$this->dbh = mysql_connect($this->dbhost, $this->dbuser, $this->dbpassword) or $this->error = 1;
		mysql_select_db($this->dbdefault) or $this->error = 1;
	}
	function query($query) {
		global $cfg_debug,$DEBUG_SQL_STRING,$SQL_TOTAL_TIMER, $SQL_TOTAL_REQUEST;
		
		if ($cfg_debug["active"]) {
			if ($cfg_debug["sql"]){		
				$SQL_TOTAL_REQUEST++;
				$DEBUG_SQL_STRING.="N°".$SQL_TOTAL_REQUEST." : <br>".$query;
				
				if ($cfg_debug["profile"]){		
					$TIMER=new Script_Timer;
					$TIMER->Start_Timer();
				}
			}
		}
		$res=mysql_query($query);

		if ($cfg_debug["active"]) {
			if ($cfg_debug["profile"]&&$cfg_debug["sql"]){		
				$TIME=$TIMER->Get_Time();
				$DEBUG_SQL_STRING.="<br><b>Time: </b>".$TIME."s";
				$SQL_TOTAL_TIMER+=$TIME;
			}
			if ($cfg_debug["sql"]){
				$DEBUG_SQL_STRING.="<hr>";
			}
		}
		
		return $res;
	}
	function result($result, $i, $field) {
		$value=get_magic_quotes_runtime()?stripslashes_deep(mysql_result($result, $i, $field)):mysql_result($result, $i, $field);
		return htmlentities_deep($value);
	}
	function numrows($result) {
		return mysql_num_rows($result);
	}
	function fetch_array($result) {
		$value=get_magic_quotes_runtime()?stripslashes_deep(mysql_fetch_array($result)):mysql_fetch_array($result);
		return htmlentities_deep($value);
	}
	function fetch_row($result) {
		$value=get_magic_quotes_runtime()?stripslashes_deep(mysql_fetch_row($result)):mysql_fetch_row($result);
		return htmlentities_deep($value);	
	}
	function fetch_assoc($result) {
		$value=get_magic_quotes_runtime()?stripslashes_deep(mysql_fetch_assoc($result)):mysql_fetch_assoc($result);
		return htmlentities_deep($value);
	}
	function insert_id() {
 		return mysql_insert_id();
 	}
	function num_fields($result) {
		return mysql_num_fields($result);
	}
	function field_name($result,$nb)
	{
		return mysql_field_name($result,$nb);
	}
	function field_flags($result,$field)
	{
		return mysql_field_flags($result,$field);
	}
	function list_tables() {
		return mysql_list_tables($this->dbdefault);
	}
	function table_name($result,$nb) {
		return mysql_tablename($result,$nb);
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
			if ($db->numrows($result)==0) return false;
			$data = $db->fetch_array($result);
			if (isset($data["end2"])) $this->end2 = $data["end2"];
			return $this->end2;
		} else {
				return false;
		}
	}

	function getComputerData($ID) {
		$db = new DB;
		$query = "SELECT * FROM glpi_computers WHERE (ID = '$ID')";
		if ($result=$db->query($query)) {
			if ($db->numrows($result)==0) return false;
			$data = $db->fetch_array($result);
			$this->device_name = $data["name"];
			$this->deleted = $data["deleted"];
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
		$result=$db->query($query);
		return $db->insert_id();
	}

}

class Identification
{
	var $err;
	var $user;
	var $extauth=0;

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

		$this->err .= imap_last_error()."<br>";
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
				       $this->err.="User not found or several users found.<br>\n";
                     }
  		}
  		else
  		{
  			$this->err .= ldap_error($ds)."<br>";
  		}
  		ldap_close($conn);
  	}
  	else
  	{
  		$this->err .= ldap_error($ds)."<br>";
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
     $this->err.=ldap_error($ds)."<br>";
     return false;
     
    }
  ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3) ;

  if ($rdn=="") $r = ldap_bind ( $ds);
  else $r = ldap_bind ( $ds,$rdn,$rpass);

  if (!$r)
      {
       $this->err.=ldap_error($ds)."<br>";
       ldap_close ( $ds );
       return false;
      }

    $sr = ldap_search ($ds, $ldap_base_dn, "($ldap_login_attr=$login)");

    if (!$sr)
       {
   	$this->err.=ldap_error($ds)."<br>";
       ldap_close ( $ds );
       return false;
       }
       
    $info = ldap_get_entries ( $ds, $sr );

    if ( $info["count"] != 1 )
       {
       $this->err.="User not found or several users found.<br>\n";
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
 		
 // return 1 if the connection to the LDAP host, auth mode, was successful
  // $condition is used to restrict login ($condition is set in glpi/config/config.php 
  function connection_ldap_active_directory($host,$basedn,$login,$pass,$condition)
  {
		// we prevent some delay...
		if (empty($host)) {
			return false;
		}
  	error_reporting(16);
  	$dn = $basedn;
  	$rv = false;
  	if ( $conn = ldap_connect($host) )
  	{
  		// switch to protocol version 3 to make ssl work
  		ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3) ;
//  		if ( ldap_bind($conn, $dn, $pass) ) {
//  			$rv = true;

  		if (ldap_bind($conn, $dn, $pass) ) {
			$findcn=explode(",O",$dn);
 			$findcn=explode("=",$findcn[0]);
 			$findcn[1]=str_replace('\,', ',', $findcn[1]);
 			$filter="(CN=".$findcn[1].")";
                     if ($condition!="") $filter="(& $filter $condition)";
                     $sr=ldap_search($conn, $basedn, $filter);
                     $info = ldap_get_entries ( $conn, $sr );
                     if ( $info["count"] == 1 )
                     {
                        $rv=true;
                     }
                     else
                     {
                       $this->err.="User not found or several users found.<br>\n";
                     }
  		}
  		else
  		{
  			$this->err .= ldap_error($ds)."<br>";
  		}
  		ldap_close($conn);
  	}
  	else
  	{
  		$this->err .= ldap_error($ds)."<br>";
  	}
  	
  	return($rv);

  } // connection_ldap_active_directory()
 
 
// Gets the dn using anonymous Ldap login
 function ldap_get_dn_active_directory($host,$ldap_base_dn,$login,$rdn,$rpass)
 {

  // we prevent some delay...
  if (empty($host)) {
	return false;
  }

  $ldap_server=$host;
  $ldap_login_attr = "sAMAccountName";                          
  $ldap_dn ="";
	error_reporting(16);
  $ds = ldap_connect ($ldap_server);
  if (!$ds)
    {
 	$this->err .= ldap_error($ds)."<br>";
     return false;
    }
    //echo "CONNECT";
  ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3) ;
  ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
  
  if ($rdn=="") {$r = ldap_bind ( $ds);//echo "sans";
  }
  else {$r = ldap_bind ( $ds,$rdn,$rpass);//echo "avec";
  }
  //echo $rdn."---".$rpass."---".$ds;
  if (!$r)
      {
		$this->err .= ldap_error($ds)."<br>";
       ldap_close ( $ds );
       return false;
      }
      //echo "BIND";
    $sr = ldap_search ($ds, $ldap_base_dn, "($ldap_login_attr=$login)");
//echo " SEARCH";
    if (!$sr)
       {
		$this->err .= ldap_error($ds)."<br>";
       ldap_close ( $ds );
       return false;
       }
       
    $info = ldap_get_entries ( $ds, $sr );

    if ( $info["count"] != 1 )
       {
       $this->err.="User not found or several users found.<br>\n";
       ldap_free_result ( $sr );
       ldap_close ( $ds );
       return false;
       }
   ldap_free_result ( $sr );
   ldap_close ( $ds );
   $thedn=explode(",", $info[0]["dn"]);
   //unset($thedn[0]);
   return implode(",",$thedn);
  }  		 // ldap_get_dn_active_directory()
 		

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
			$this->err .= "Empty Password<br>";
			return false;
		}
		
		$db = new DB;
		$query = "SELECT password, password_md5 from glpi_users where (name = '".$name."')";
		$result = $db->query($query);
		if (!$result){
		$this->err .= "Unknown username<br>";
		return false;	
		}
		$query2 = "SELECT PASSWORD('".$password."') as password";
		$result2 = $db->query($query2);
		if (!$result2){
		$this->err .= "Bad username or password<br>";
		return false;	
		}
		if($result&&$result2)
		{
			if($db->numrows($result) == 1&&$db->numrows($result2) == 1)
			{
				$pass1=$db->result($result,0,"password");
				$pass2=$db->result($result2,0,"password");
				$password_md5_db=$db->result($result,0,"password_md5");
				$password_md5_post = md5($password);
				if (strcmp($pass1,$pass2)==0) 
				{
					if(empty($password_md5_db)) {
						$password_md5_db = md5($password);
						$query3 = "update glpi_users set password_md5 = '".$password_md5_db."' where (name = '".$name."')";
						$db->query($query3);
					}
					return true;
				}
				elseif(strcmp($password_md5_db,$password_md5_post)==0) {
					return true;
				}
				else {
				$this->err .= "Bad username or password<br>";
				return false;
				}
			}
			else
			{
				$this->err .= "Bad username or password<br>";
				return false;
			}
		}

		$this->err .= "Erreur numero : ".$db->errno().": ";
		$this->err .= $db->error();
		return false;

	} // connection_db()


	// Set Cookie for this user
	function setCookies()
	{
		$ID = $this->user->fields['ID'];
		$name = $this->user->fields['name'];
		$realname = $this->user->fields['realname'];
		$password = md5($this->user->fields['password']);
		$type = $this->user->fields['type'];
		$language = $this->user->prefs['language'];
		$tracking_order = $this->user->prefs['tracking_order'];
		//echo $tracking_order;
		if(!session_id()) session_start();
		$_SESSION["glpiID"] = $ID;
		$_SESSION["glpipass"] = $password;
		$_SESSION["glpiname"] = $name;
		$_SESSION["glpirealname"] = $realname;
		$_SESSION["glpitype"] = $type;
		$_SESSION["glpilanguage"] = $language;
		$_SESSION["tracking_order"] = $tracking_order;
		$_SESSION["authorisation"] = true;
		$_SESSION["extauth"] = $this->extauth;
		$_SESSION["glpisearchcount"] = 1;
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
		$subject="[GLPI] ";
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
		
		if ($this->type!="new") $subject .= " (Ref #".$this->job->ID.")";		
		
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
		GLOBAL $cfg_features,$cfg_mailing,$phproot;
		if ($cfg_features["mailing"]&&$this->is_valid_email($cfg_mailing["admin_email"]))
		{
			if (!is_null($this->job)&&!is_null($this->user)&&(strcmp($this->type,"new")||strcmp($this->type,"attrib")||strcmp($this->type,"followup")||strcmp($this->type,"finish")))
			{
				// get users to send mail
				$users=$this->get_users_to_send_mail();
				// get body + signature OK
				$body=$this->get_mail_body()."\n-- \n".$cfg_mailing["signature"];
				$body=ereg_replace("<br />","",$body);
				$body=ereg_replace("<br>","",$body);
				$body=stripslashes($body);
				$body=unhtmlentities($body);
				// get subject OK
				$subject=$this->get_mail_subject();
				// get sender :  OK
				$sender= $cfg_mailing["admin_email"];
				// get reply-to address : user->email ou job_email if not set OK
				$replyto=$this->get_reply_to_address ();
				// Send all mails
				require $phproot."/glpi/common/MIMEMail.php";
				for ($i=0;$i<count($users);$i++)
				{
				$mmail=new MIMEMail();
		  		$mmail->ReplyTo($replyto);
		  		$mmail->From($sender);
		  		
		  		$mmail->To($users[$i]);
		  		$mmail->Subject($subject);		  
		  		$mmail->Priority(2);
		  		$mmail->MessageStream($body);
		  		
				
				
				// Attach a file
		  		// $mmail->AttachFile($FILE);
		  		
				// Notification reception
				// $mmail->setHeader('Disposition-Notification-To', "\"".$users[0]['name']."\" <".$users[0]['email'].">"); 
		  
		  		$mmail->Send();
				}
			} else {
				echo "Type d'envoi invalide";
			}
		}
	}
}

class CommonItem{
	var $obj = NULL;	
	var $device_type=0;
	var $id_type=0;
	
	function getfromDB ($device_type,$id_device) {
		$this->id_device=$id_device;
		$this->device_type=$device_type;
		// Make new database object and fill variables

			switch ($device_type){
			case COMPUTER_TYPE :
				$this->obj=new Computer;
				break;
			case NETWORKING_TYPE :
				$this->obj=new Netdevice;
				break;
			case PRINTER_TYPE :
				$this->obj=new Printer;
				break;
			case MONITOR_TYPE : 
				$this->obj= new Monitor;	
				break;
			case PERIPHERAL_TYPE : 
				$this->obj= new Peripheral;	
				break;				
			case SOFTWARE_TYPE : 
				$this->obj= new Software;	
				break;				
			case CONTRACT_TYPE : 
				$this->obj= new Contract;	
				break;				
			case ENTERPRISE_TYPE : 
				$this->obj= new Enterprise;	
				break;	
			case KNOWBASE_TYPE : 
				$this->obj= new kbitem;	
				break;					
			}

			if ($this->obj!=NULL){
			return $this->obj->getfromDB($id_device);
			}
			else return false;
			
	}
	function setType ($device_type){
		$this->device_type=$device_type;
	}

	function getType (){
		global $lang;
		
		switch ($this->device_type){
			case GENERAL_TYPE :
				return $lang["help"][30];
				break;
			case COMPUTER_TYPE :
				return $lang["computers"][44];
				break;
			case NETWORKING_TYPE :
				return $lang["networking"][12];
				break;
			case PRINTER_TYPE :
				return $lang["printers"][4];
				break;
			case MONITOR_TYPE : 
				return $lang["monitors"][4];
				break;
			case PERIPHERAL_TYPE : 
				return $lang["peripherals"][4];
				break;				
			case SOFTWARE_TYPE : 
				return $lang["software"][10];
				break;				
			case CONTRACT_TYPE : 
				return $lang["financial"][1];
				break;				
			case ENTERPRISE_TYPE : 
				return $lang["financial"][26];
				break;
			case KNOWBASE_TYPE : 
				return $lang["knowbase"][0];
				break;						
			}
	
	}
	function getName(){
		global $lang;
		if ($this->device_type==0)
		return "";
		if ($this->device_type==KNOWBASE_TYPE&&$this->obj!=NULL&&isset($this->obj->fields["question"])&&$this->obj->fields["question"]!="")
			return $this->obj->fields["question"];
		else if ($this->obj!=NULL&&isset($this->obj->fields["name"])&&$this->obj->fields["name"]!="")
			return $this->obj->fields["name"];
		else 
			return "N/A";
	}
	function getNameID(){
		if ($this->device_type==0)
		return $this->getName();
		else return $this->getName()." (".$this->id_device.")";
	}
	
	function getLink(){
	
		global $cfg_install;
	
		switch ($this->device_type){
			case GENERAL_TYPE :
				return $this->getName();
				break;
			case COMPUTER_TYPE :
				return "<a href=\"".$cfg_install["root"]."/computers/computers-info-form.php?ID=".$this->id_device."\">".$this->getName()." (".$this->id_device.")</a>";
				break;
			case NETWORKING_TYPE :
				return "<a href=\"".$cfg_install["root"]."/networking/networking-info-form.php?ID=".$this->id_device."\">".$this->getName()." (".$this->id_device.")</a>";
				break;
			case PRINTER_TYPE :
				return "<a href=\"".$cfg_install["root"]."/printers/printers-info-form.php?ID=".$this->id_device."\">".$this->getName()." (".$this->id_device.")</a>";
				break;
			case MONITOR_TYPE : 
				return "<a href=\"".$cfg_install["root"]."/monitors/monitors-info-form.php?ID=".$this->id_device."\">".$this->getName()." (".$this->id_device.")</a>";
				break;
			case PERIPHERAL_TYPE : 
				return "<a href=\"".$cfg_install["root"]."/peripherals/peripherals-info-form.php?ID=".$this->id_device."\">".$this->getName()." (".$this->id_device.")</a>";
				break;				
			case SOFTWARE_TYPE : 
				return "<a href=\"".$cfg_install["root"]."/software/software-info-form.php?ID=".$this->id_device."\">".$this->getName()." (".$this->id_device.")</a>";
				break;				
			case CONTRACT_TYPE : 
				return "<a href=\"".$cfg_install["root"]."/contracts/contracts-info-form.php?ID=".$this->id_device."\">".$this->getName()." (".$this->id_device.")</a>";
				break;				
			case ENTERPRISE_TYPE : 
				return "<a href=\"".$cfg_install["root"]."/enterprises/enterprises-info-form.php?ID=".$this->id_device."\">".$this->getName()." (".$this->id_device.")</a>";
				break;
			case KNOWBASE_TYPE : 
				return "<a href=\"".$cfg_install["root"]."/knowbase/knowbase-info-form.php?ID=".$this->id_device."\">".$this->getName()." (".$this->id_device.")</a>";
				break;						
			}

	
	}
	
}


?>
