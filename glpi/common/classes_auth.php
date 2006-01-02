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

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

class Identification
{
	var $err;
	var $user;
	var $extauth=0;

	//constructor for class Identification
	function Identification()
	{
		$this->err = "";
		$this->user = new User();
	}

	
	// Is the user exists in the DB with ?
	// return 0 -> Not in the DB -> check external auth
	// return 1 -> Exist in the DB with a password -> check first local connection and external after
	// return 2 -> Exist in the DB with no password -> check only external auth
	function userExists($name){
		$db = new DB;

		$query = "SELECT * from glpi_users WHERE name='$name'";
		$result=$db->query($query);
		if ($db->numrows($result)==0) return 0;
		else {
			$pwd=$db->result($result,0,"password");
			$pwdmd5=$db->result($result,0,"password_md5");
			if (empty($pwd)&&empty($pwdmd5))
				return 2;
			else return 1;
		}

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
  function connection_ldap($host,$basedn,$login,$pass,$condition,$port)
  {
	global $cfg_login;
	
		// we prevent some delay...
		if (empty($host)) {
			return false;
		}
  	error_reporting(16);
  	
	$dn = $cfg_login['ldap']['login'] ."=" . $login . "," . $basedn;
  	$rv = false;
  	if ( $conn = ldap_connect($host,$port) )
  	{
  		// switch to protocol version 3 to make ssl work
  		ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3) ;

  		if (ldap_bind($conn, $dn, $pass) ) {
                     $filter="(".$cfg_login['ldap']['login']."=$login)";
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
	global $cfg_login;
  // we prevent some delay...
  if (empty($host)) {
	return false;
  }
  $ldap_server=$host;
  $ldap_login_attr = $cfg_login['ldap']['login'];
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

  		if (ldap_bind($conn, $dn, $pass) ) {
			$findcn=explode(",O",$dn);
              // Cas ou pas de ,OU
             if ($dn==$findcn[0]) {
              $findcn=explode(",C",$dn);
             }

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
    
  ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3) ;
  ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
  
  if ($rdn=="") {$r = ldap_bind ( $ds);
  }
  else {$r = ldap_bind ( $ds,$rdn,$rpass);
  }
  if (!$r)
      {
		$this->err .= ldap_error($ds)."<br>";
       ldap_close ( $ds );
       return false;
      }
    $sr = ldap_search ($ds, $ldap_base_dn, "($ldap_login_attr=$login)");
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
		global $cfg_install,$cfg_features;

		$ID = $this->user->fields['ID'];
		$name = $this->user->fields['name'];
		$realname = $this->user->fields['realname'];
		$password = md5($this->user->fields['password']);
		$type = $this->user->fields['type'];
		$language = $this->user->fields['language'];
		$tracking_order = $this->user->fields['tracking_order'];

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
		$_SESSION["glpisearchcount"] = array();
		$_SESSION["root"] = $cfg_install["root"];
		$_SESSION["list_limit"] = $cfg_features["list_limit"];
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


?>
