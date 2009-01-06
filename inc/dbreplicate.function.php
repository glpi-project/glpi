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

/**
 * Create slave DB configuration file
 * @param host the slave DB host
 * @param user the slave DB user
 * @param password the slave DB password
 * @param DBname the name of the slave DB
 */
function create_slave_conn_file($host, $user, $password, $DBname) {
	global $CFG_GLPI;
	$DB_str = "<?php \n class DBSlave extends DBmysql { \n var	\$slave	= true; \n var \$dbhost	= '" . $host . "'; \n var \$dbuser 	= '" . $user . "'; \n var \$dbpassword= '" . rawurlencode($password) . "'; \n var \$dbdefault	= '" . $DBname . "'; \n } \n ?>";
	$fp = fopen(GLPI_CONFIG_DIR . "/config_db_slave.php", 'wt');
	if ($fp) {
		$fw = fwrite($fp, $DB_str);
		fclose($fp);
		return true;
	} else
		return false;
}

/**
 * Indicates is the DB replicate is active or not
 * @return true if active / false if not active
 */
function isDBSlaveActive() {
	return file_exists(GLPI_CONFIG_DIR . "/config_db_slave.php");
}

/**
 * Read slave DB configuration file
 */
function getDBSlaveConf() {
	if (isDBSlaveActive()) {
		include_once (GLPI_CONFIG_DIR . "/config_db_slave.php");
		return new DBSlave;
	}
}

/**
 * Create a default slave DB configuration file
 */
function createDBSlaveConfig() {
	create_slave_conn_file("localhost", "glpi", "glpi", "glpi");
}

/**
 * Save changes to the slave DB configuration file
 */
function saveDBSlaveConf($host, $user, $password, $DBname) {
	create_slave_conn_file($host, $user, $password, $DBname);
}

/**
 * Delete slave DB configuration file
 */
function deleteDBSlaveConfig() {
	unlink(GLPI_CONFIG_DIR . "/config_db_slave.php");
}

/**
 * Switch database connection to slave
 */
function switchToSlave() {
	global $DB;
	if (isDBSlaveActive())
	{
		include_once (GLPI_CONFIG_DIR . "/config_db_slave.php");
		$DB = new DBSlave;
		return $DB->connected;
	}
	else
		return false;
}

/**
 * Switch database connection to master
 */
function switchToMaster() {
	global $DB;
	$DB = new DB;
	return $DB->connected;
}

/**
 *  Establish a connection to a mysql server (main or replicate)
 * @param $use_slave try to connect to slave server first not to main server
 * @param $required connection to the specified server is required (if connection failed, do not try to connect to the other server)
 * @param $display display error message
 */
function establishDBConnection($use_slave, $required, $display=true) {
	global $DB;
	$DB = null;

	$res=false;
	// First standard config : no use slave : try to connect to master
	if (!$use_slave){
		$res = switchToMaster();
	} 
	
	// If not already connected to master due to config or error
	if (!$res){
		// No DB slave : first connection to master give error
		if (!isDBSlaveActive()){ 

			// Slave wanted but not defined -> use master
			// Ignore $required when no slave configured
			if ($use_slave){
				$res = switchToMaster();
			}		
		// SLave DB configured
		} else { 
			// Try to connect to slave if wanted
			if ($use_slave){
				$res = switchToSlave();
			}

			// No connection to 'mandatory' server
			if (!$res && !$required){
				//Try to establish the connection to the other mysql server
				if ($use_slave){
					$res = switchToMaster();
				} else {
					$res = switchToSlave();
				}
	
				if ($res) {
					$DB->first_connection=false;	
				}
			}
		}
	}
	// Display error if needed		
	if (!$res && $display){
		displayMySQLError();
	}
	return $res;
}

/**
 *  Get delay between slave and master
 */
function getReplicateDelay() {
	include_once (GLPI_CONFIG_DIR . "/config_db_slave.php");

	return (int) (getHistoryMaxDate(new DB) - getHistoryMaxDate(new DBSlave));
}

/**
 *  Get history max date of a GLPI DB
 * @param $DBconnection DB conneciton used
 */
function getHistoryMaxDate($DBconnection) {
	$result = $DBconnection->query("SELECT UNIX_TIMESTAMP(MAX(date_mod)) as max_date FROM glpi_history");
	if ($DBconnection->numrows($result) > 0)
		return $DBconnection->result($result, 0, "max_date");
	else
		return "";
}
/**
 *  Display a common mysql connection error
 */
function displayMySQLError() {
	nullHeader("Mysql Error", $_SERVER['PHP_SELF']);

	if (!isCommandLine()) {
		echo "<div class='center'><p><strong>A link to the Mysql server could not be established. Please Check your configuration.</strong></p><p><strong>Le serveur Mysql est inaccessible. V&eacute;rifiez votre configuration</strong></p></div>";
	} else {
		echo "A link to the Mysql server could not be established. Please Check your configuration.\n";
		echo "Le serveur Mysql est inaccessible. Vérifiez votre configuration\n";
	}

	nullFooter("Mysql Error", $_SERVER['PHP_SELF']);
	die();
}

/**
 *  Cron process to check DB replicate state
 */
function cron_dbreplicate() {
	global $DB, $CFG_GLPI, $LANG;

	//Lauch cron only is : 
	// 1 the master database is avalaible
	// 2 the slave database is configurated
	if (!$DB->isSlave() && isDBSlaveActive())
	{
		$diff = getReplicateDelay();
		
		//If admin must be notified when slave is not synchronized with master
		if ($CFG_GLPI["dbreplicate_notify_desynchronization"] && $diff > $CFG_GLPI["dbreplicate_maxdelay"]) {
			$msg = $LANG["setup"][807] . " " . timestampToString($diff);
			$mmail = new glpi_phpmailer();
			$mmail->From = $CFG_GLPI["admin_email"];
			$mmail->AddReplyTo($CFG_GLPI["admin_email"], '');
			$mmail->FromName = $CFG_GLPI["dbreplicate_email"];
			$mmail->AddAddress($CFG_GLPI["dbreplicate_email"], "");
			$mmail->Subject = $LANG["setup"][808];
			$mmail->Body = $msg;
			$mmail->isHTML(false);
			if (!$mmail->Send())
				return 1;
			else
				return 0;
		}
	}
	return 0;
}
?>
