<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.

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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

/**
 *  Identification class used to login
 */
class Identification
{
	//! Error string
	var $err;
	/** User class variable
	 * @see User
	 */
	var $user;
	//! External authentification variable : boolean
	var $extauth=0;

	/**
	 * Constructor
	 *
	 * @return nothing 
	 *
	 */
	function Identification()
	{
		$this->err = "";
		$this->user = new User();
	}


	/**
	 * Is the user exists in the DB
	 *
	 * @return 0 (Not in the DB -> check external auth), 1 ( Exist in the DB with a password -> check first local connection and external after), 2 (Exist in the DB with no password -> check only external auth)
	 *
	 */
	function userExists($name){
		global $db;

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
	/**
	 * Try a IMAP/POP connection
	 *
	 * @param $host IMAP/POP host to connect
	 * @param $login Login to try
	 * @param $pass Password to try
	 *
	 * @return boolean : connection success
	 *
	 */
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

	/**
	 * Try a LDAP connection
	 *
	 * @param $host LDAP host to connect
	 * @param $dn Basedn to use
	 * @param $login Login to try
	 * @param $pass Password to try
	 * @param $condition Condition used to restrict login
	 * @param $port LDAP port
	 *
	 * @return boolean : connection success
	 *
	 */
	function connection_ldap($host,$dn,$login,$pass,$condition,$port)
	{
		global $cfg_glpi;

		// we prevent some delay...
		if (empty($host)) {
			return false;
		}
		error_reporting(16);

		//$dn = $cfg_glpi["ldap_login"] ."=" . $login . "," . $basedn;
		$rv = false;
		if ( $ds=ldap_connect($host,$port) )
		{
			// switch to protocol version 3 to make ssl work
			ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3) ;
			ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);

			if ($cfg_glpi["ldap_use_tls"]){
				if (!ldap_start_tls($ds)) {
					$this->err .= ldap_error($ds)."<br>";
					return false;
				} 
			}
			if (ldap_bind($ds, $dn, $pass) ) {
				$filter="(".$cfg_glpi["ldap_login"]."=$login)";
				if ($condition!="") $filter="(& $filter $condition)";
				$thedn=explode(",", $dn);
				unset($thedn[0]);
				$basedn=implode(",",$thedn);

				$sr=ldap_search($ds, $basedn, $filter);
				$info = ldap_get_entries ( $ds, $sr );
				if ( $info["count"] == 1 )
				{
					//Hook to implement to restrict access by checking the ldap directory
					if (do_hook_function("restrict_ldap_auth",$info))
						$rv=true;
					else
						$this->err .= "Restricted ldap authentication failed<br>\n";

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
			ldap_close($ds);
		}
		else
		{
			$this->err .= ldap_error($ds)."<br>";
		}

		return($rv);

	} // connection_ldap()


	/**
	 * Find a user in a LDAP and return is BaseDN
	 *
	 * @param $host LDAP host to connect
	 * @param $ldap_base_dn Basedn to use
	 * @param $login Login to search
	 * @param $rdn Root Basedn to connect
	 * @param $rpass Root Password to connect
	 * @param $port LDAP port
	 *
	 * @return String : basedn of the user / false if not founded
	 *
	 */
	function ldap_get_dn($host,$ldap_base_dn,$login,$rdn,$rpass,$port)
	{
		global $cfg_glpi;

		// we prevent some delay...
		if (empty($host)) {
			return false;
		}

		$ldap_login_attr = $cfg_glpi["ldap_login"];
		$ldap_dn ="";
		error_reporting(16);

		$ds = ldap_connect ($host,$port);

		if (!$ds)
		{
			$this->err.=ldap_error($ds)."<br>";
			return false;

		}

		ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3) ;
		ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);

		if ($cfg_glpi["ldap_use_tls"]){
			if (!ldap_start_tls($ds)) {
				$this->err.=ldap_error($ds)."<br>";
				return false;
			} 
		}

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
		//$thedn=explode(",", $info[0]["dn"]);
		//unset($thedn[0]);
		//return implode(",",$thedn);
		return $info[0]["dn"];
	}  		 // ldap_get_dn()

	/**
	 * Try a Active Directory connection
	 *
	 * @param $host LDAP host to connect
	 * @param $basedn Basedn to use
	 * @param $login Login to try
	 * @param $pass Password to try
	 * @param $condition Condition used to restrict login
	 * @param $port LDAP port
	 *
	 * @return boolean : connection success
	 *
	 */
	function connection_ldap_active_directory($host,$basedn,$login,$pass,$condition,$port)
	{
		global $cfg_glpi;

		// we prevent some delay...
		if (empty($host)) {
			return false;
		}

		error_reporting(16);
		$dn = $basedn;
		$rv = false;
		if ( $ds = ldap_connect($host,$port) )
		{
			// switch to protocol version 3 to make ssl work
			ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3) ;
			ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);

			if ($cfg_glpi["ldap_use_tls"]){
				if (!ldap_start_tls($ds)) {
					$this->err .= ldap_error($ds)."<br>";
					return false;
				} 
			}

			if (ldap_bind($ds, $dn, $pass) ) {
				$findcn=explode(",O",$dn);
				// Cas ou pas de ,OU
				if ($dn==$findcn[0]) {
					$findcn=explode(",C",$dn);
				}

				$findcn=explode("=",$findcn[0]);
				$findcn[1]=str_replace('\,', ',', $findcn[1]);
				$filter="(CN=".$findcn[1].")";
				if ($condition!="") $filter="(& $filter $condition)";
				$sr=ldap_search($ds, $basedn, $filter);
				$info = ldap_get_entries ( $ds, $sr );
				if ( $info["count"] == 1 )
				{
					//Hook to implement to restrict access by checking the ldap directory
					if (do_hook_function("restrict_ldap_auth",$info))
						$rv=true;
					else
						$this->err .= "Restricted ldap authentication failed<br>\n";
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
			ldap_close($ds);
		}
		else
		{
			$this->err .= ldap_error($ds)."<br>";
		}

		return($rv);

	} // connection_ldap_active_directory()


	/**
	 * Find a user in an Active Directory and return is BaseDN
	 *
	 * @param $host LDAP host to connect
	 * @param $ldap_base_dn Basedn to use
	 * @param $login Login to search
	 * @param $rdn Root Basedn to connect
	 * @param $rpass Root Password to connect
	 * @param $port LDAP port
	 *
	 * @return String : basedn of the user / false if not founded
	 *
	 */
	function ldap_get_dn_active_directory($host,$ldap_base_dn,$login,$rdn,$rpass,$port)
	{
		global $cfg_glpi;

		// we prevent some delay...
		if (empty($host)) {
			return false;
		}

		$ldap_login_attr = "sAMAccountName";                          
		$ldap_dn ="";
		error_reporting(16);
		$ds = ldap_connect ($host,$port);
		if (!$ds)
		{
			$this->err .= ldap_error($ds)."<br>";
			return false;
		}

		ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3) ;
		ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);

		if ($cfg_glpi["ldap_use_tls"]){
			if (!ldap_start_tls($ds)) {
				$this->err .= ldap_error($ds)."<br>";
				return false;
			} 
		}

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
		global $db;
		// sanity check... we prevent empty passwords...
		//
		if ( empty($password) )
		{
			$this->err .= "Empty Password<br>";
			return false;
		}


		$query = "SELECT password, password_md5 from glpi_users where (name = '".$name."')";
		$result = $db->query($query);
		if (!$result){
			$this->err .= "Unknown username<br>";
			return false;	
		}
		if($result)
		{
			if($db->numrows($result) == 1)
			{
				$password_md5_db=$db->result($result,0,"password_md5");
				$password_md5_post = md5($password);

				if(strcmp($password_md5_db,$password_md5_post)==0) {
					return true;
				} else {

					$query2 = "SELECT PASSWORD('".addslashes($password)."') as password";
					$result2 = $db->query($query2);
					if (!$result2||$db->numrows($result2) != 1){
						$this->err .= "Bad username or password<br>";
						return false;	
					}
					$pass1=$db->result($result,0,"password");
					$pass2=$db->result($result2,0,"password");


					if (!empty($pass1)&&strcmp($pass1,$pass2)==0) 
					{
						if(empty($password_md5_db)) {
							$password_md5_db = md5($password);
							$query3 = "update glpi_users set password_md5 = '".$password_md5_db."' where (name = '".$name."')";
							$db->query($query3);
						}
						return true;
					}
				}
				$this->err .= "Bad username or password<br>";
				return false;
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


	// Init session for this user
	function initSession()
	{
		global $cfg_glpi,$db;

		if(!session_id()) session_start();
		$_SESSION["glpiID"] = $this->user->fields['ID'];
		$_SESSION["glpiname"] = $this->user->fields['name'];
		$_SESSION["glpirealname"] = $this->user->fields['realname'];
		$_SESSION["glpifirstname"] = $this->user->fields['firstname'];
		$_SESSION["glpilanguage"] = $this->user->fields['language'];
		$_SESSION["glpitracking_order"] = $this->user->fields['tracking_order'];
		$_SESSION["glpiauthorisation"] = true;
		$_SESSION["glpiextauth"] = $this->extauth;
		$_SESSION["glpisearchcount"] = array();
		$_SESSION["glpisearchcount2"] = array();
		$_SESSION["glpiroot"] = $cfg_glpi["root_doc"];
		$_SESSION["glpilist_limit"] = $cfg_glpi["list_limit"];
		$_SESSION["glpicrontimer"]=time();
		$prof=new Profile();
		$prof->getFromDBForUser($_SESSION["glpiID"]);
		$prof->cleanProfile();
		$_SESSION["glpiprofile"]=$prof->fields;
		$_SESSION["glpigroups"]=array();
		$query_gp="SELECT * FROM glpi_users_groups WHERE FK_users='".$this->user->fields['ID']."'";
		$result_gp=$db->query($query_gp);
		if ($db->numrows($result_gp)){
			while ($data=$db->fetch_array($result_gp)){
				$_SESSION["glpigroups"][]=$data["FK_groups"];
			}
		}
		do_hook("init_session");
	}

	function destroySession()
	{
		if(!session_id()) session_start();
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
