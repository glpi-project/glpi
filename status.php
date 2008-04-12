<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

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

define('GLPI_ROOT', '.');
$NEEDED_ITEMS=array("ocsng","user");
include (GLPI_ROOT . "/inc/includes.php");

// Force in normal mode
if ($CFG_GLPI["debug"]!=NORMAL_MODE){
	$CFG_GLPI["debug"]=NORMAL_MODE;
	restore_error_handler();
} 

// Need to be used using :
// check_http -H servername -u /glpi/status.php -s GLPI_OK


// Plain text content
header('Content-type: text/plain');

$ok_master=true;
$ok_slave=true;
$ok=true;

// Check slave server connection
if (isDBSlaveActive()){
	if (establishDBConnection(true,true,false)){
		echo "GLPI_DBSLAVE_OK\n";
	} else {
		echo "GLPI_DBSLAVE_PROBLEM\n";
		$ok_slave=false;
	}
} else {
	echo "No slave DB\n";
}

// Check main server connection
if (establishDBConnection(false,true,false)){
	echo "GLPI_DB_OK\n";
} else {
	echo "GLPI_DB_PROBLEM\n";
	$ok_master=false;
}

// Slave and master ok;
$ok = $ok_slave && $ok_master;

// Check session dir (usefull when NFS mounted))
if (is_dir(GLPI_SESSION_DIR) && is_writable(GLPI_SESSION_DIR)) {
		echo "GLPI_SESSION_DIR_OK\n";	
} else {
		echo "GLPI_SESSION_DIR_PROBLEM\n";
		$ok=false;		
}

// Reestablished DB connection
if (( $ok_master || $ok_slave ) && establishDBConnection(false,false,false)){

	// Check OCS connections
	$query = "SELECT ID, name FROM glpi_ocs_config";
	if ($result=$DB->query($query)){
		if ($DB->numrows($result)){
			echo "Check OCS servers:";
			while ($data = $DB->fetch_assoc($result)){
				echo " ".$data['name'];
				if (checkOCSconnection($data['ID'])){
					echo "_OK";
				} else {
					echo "_PROBLEM";
					$ok=false;
				}
				echo "\n";
			}
		} else {
			echo "No OCS server\n";
		}
	}
	
	// Check Auth connections
	$auth = new Identification();
	$auth->getAuthMethods();
	$ldap_methods = $auth->auth_methods["ldap"];
		
	if (count($ldap_methods)){
		echo "Check LDAP servers:";
		foreach ($ldap_methods as $method){
			echo " ".$method['name'];

			if (try_connect_ldap($method['ldap_host'],$method['ldap_port'], 
				 $method["ldap_rootdn"], $method["ldap_pass"], $method["ldap_use_tls"],"","",$method["ldap_opt_deref"],$method['ID'])){
				echo "_OK";
			} else {
				echo "_PROBLEM";
				$ok=false;
			}
			echo "\n";
		}
	} else {
		echo "No LDAP server\n";
	}

	// TODO Check mail server

	// TODO check CAS url 
	
}

echo "\n";

if ($ok){
	echo "GLPI_OK\n";
} else {
	echo "GLPI_PROBLEM\n";
}

?>