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

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}

/**
 *  Database class for Mysql
 */
class DBmysql {

	//! Database Host
	var $dbhost	= ""; 
	//! Database User
	var $dbuser = "";
	//! Database Password 
	var $dbpassword	= "";
	//! Default Database
	var $dbdefault	= "";
	//! Database Handler
	var $dbh;
	//! Database Error
	var $error = 0;

	/// Slave management
	var $slave = false;
	/** Is it a first connection ?
	* Indicates if the first connection attempt is successful or not
	* if first attempt fail -> display a warning which indicates
	* that glpi is in readonly
	*/
	var $first_connection = true;
	/// Is connected to the DB ?
	var $connected = false;
	
	/**
	 * Constructor / Connect to the MySQL Database
	 *
	 * Use dbhost, dbuser, dbpassword and dbdefault
	 * Die if connection or database selection failed
	 *
	 * @return nothing 
	 */
	function DBmysql(){
		$this->connected=false;
		$this->dbh = @mysql_connect($this->dbhost, $this->dbuser, rawurldecode($this->dbpassword)) or $this->error = 1;
		if ($this->dbh){
			@mysql_query("SET NAMES '" . (isset($this->dbenc) ? $this->dbenc : "utf8") . "'",$this->dbh);
			mysql_select_db($this->dbdefault) or $this->error = 1;
			$this->connected=true;
		} else {
			$this->connected=false;
		}
	}
	/**
	 * Execute a MySQL query
	 * @param $query Query to execute
	 * @return Query result handler
	 */
	function query($query) {
		global $CFG_GLPI,$DEBUG_SQL,$SQL_TOTAL_REQUEST;

		if ($CFG_GLPI["debug"]) {
			if ($CFG_GLPI["debug_sql"]){		
				$SQL_TOTAL_REQUEST++;
				$DEBUG_SQL["queries"][$SQL_TOTAL_REQUEST]=$query;

				if ($CFG_GLPI["debug_profile"]){		
					$TIMER=new Script_Timer;
					$TIMER->Start_Timer();
				}
			}
		}

		//mysql_ping($this->dbh);
		$res=@mysql_query($query,$this->dbh);
		
		if (!$res) {
			$this->DBmysql();
			$res=mysql_query($query,$this->dbh);
			
			if (!$res) {
				$error = "*** MySQL query error : \n***\nSQL: ".addslashes($query)."\nError: ". mysql_error()."\n";
				if (function_exists("debug_backtrace")) {
					$error .= "Backtrace :\n";
					$traces=debug_backtrace();
					foreach ($traces as $trace) {
						$error .= $trace["file"] . ":" . $trace["line"] . "\t\t"
								. (isset($trace["class"]) ? $trace["class"] : "")
								. (isset($trace["type"]) ? $trace["type"] : "")
								. (isset($trace["function"]) ? $trace["function"]."()" : "")
								. "\n";
					}
				} else {
					$error .= "Script: " ; 
				}
				$error .= $_SERVER["SCRIPT_FILENAME"]. "\n";
				logInFile("sql-errors",$error);
		
				if ($CFG_GLPI["debug"]&&$CFG_GLPI["debug_sql"]){
					$DEBUG_SQL["errors"][$SQL_TOTAL_REQUEST]=$this->error();
				}
			}
		}

		if ($CFG_GLPI["debug"]) {
			if ($CFG_GLPI["debug_profile"]&&$CFG_GLPI["debug_sql"]){		
				$TIME=$TIMER->Get_Time();
				$DEBUG_SQL["times"][$SQL_TOTAL_REQUEST]=$TIME;
			}
		}

		return $res;
	}
	/**
	 * Give result from a mysql result
	 * @param $result MySQL result handler
	 * @param $i Row to give
	 * @param $field Field to give
	 * @return Value of the Row $i and the Field $field of the Mysql $result
	 */
	function result($result, $i, $field) {
		$value=get_magic_quotes_runtime()?stripslashes_deep(mysql_result($result, $i, $field)):mysql_result($result, $i, $field);
		return $value;
	}
	/**
	 * Give number of rows of a Mysql result
	 * @param $result MySQL result handler
	 * @return number of rows
	 */
	function numrows($result) {
		return mysql_num_rows($result);
	}
	/**
	 * Fetch array of the next row of a Mysql query
	 * @param $result MySQL result handler
	 * @return result array
	 */
	function fetch_array($result) {
		$value=get_magic_quotes_runtime()?stripslashes_deep(mysql_fetch_array($result)):mysql_fetch_array($result);
		return $value;
	}
	/**
	 * Fetch row of the next row of a Mysql query
	 * @param $result MySQL result handler
	 * @return result row
	 */
	function fetch_row($result) {
		$value=get_magic_quotes_runtime()?stripslashes_deep(mysql_fetch_row($result)):mysql_fetch_row($result);
		return $value;
	}
	/**
	 * Fetch assoc of the next row of a Mysql query
	 * @param $result MySQL result handler
	 * @return result associative array
	 */
	function fetch_assoc($result) {
		$value=get_magic_quotes_runtime()?stripslashes_deep(mysql_fetch_assoc($result)):mysql_fetch_assoc($result);
		return $value;
	}
	/**
	 * Move current pointer of a Mysql result to the specific row
	 * @param $result MySQL result handler
	 * @param $num row to move current pointer
	 * @return boolean
	 */
	function data_seek($result,$num){
		return mysql_data_seek ($result,$num);
	}
	/**
	 * Give ID of the last insert item by Mysql
	 * @return item ID
	 */
	function insert_id() {
		return mysql_insert_id($this->dbh);
	}
	/**
	 * Give number of fields of a Mysql result
	 * @param $result MySQL result handler
	 * @return number of fields
	 */
	function num_fields($result) {
		return mysql_num_fields($result);
	}
	/**
	 * Give name of a field of a Mysql result
	 * @param $result MySQL result handler
	 * @param $nb number of column of the field
	 * @return name of the field
	 */
	function field_name($result,$nb){
		return mysql_field_name($result,$nb);
	}
	/**
	 * Get flags of a field of a mysql result 
	 * @param $result MySQL result handler
	 * @param $field field name
	 * @return flags of the field
	 */
	function field_flags($result,$field){
		return mysql_field_flags($result,$field);
	}
	/**
	 * List tables in database 
	 * @param $table table name condition (glpi_% as default to retrieve only glpi tables)
	 * @return list of tables
	 */
	function list_tables($table="glpi_%") {
		return $this->query("SHOW TABLES LIKE '".$table."'");
	}
	/**
	 * List fields of a table
	 * @param $table table name condition
	 * @return list of fields
	 */
	function list_fields($table) {
		$result = $this->query("SHOW COLUMNS FROM $table");
		if ($result) {
			if ($this->numrows($result) > 0) {
				while ($data = mysql_fetch_assoc($result)){
					$ret[$data["Field"]]= $data;
				}
				return $ret;
			} else return array();
		} else return false;


	}
	/**
	 * Get number of affected rows in previous MySQL operation
	 * @return number of affected rows on success, and -1 if the last query failed.
	 */
	function affected_rows() {
		return mysql_affected_rows($this->dbh);
	}
	/**
	 * Free result memory
	 * @return Returns TRUE on success or FALSE on failure.
	 */
	function free_result($result) {
		return mysql_free_result($result);
	}
	/**
	 * Returns the numerical value of the error message from previous MySQL operation
	 * @return error number from the last MySQL function, or 0 (zero) if no error occurred.
	 */
	function errno(){
		return mysql_errno($this->dbh);
	}

	/**
	 * Returns the text of the error message from previous MySQL operation
	 * @return error text from the last MySQL function, or '' (empty string) if no error occurred.
	 */
	function error(){
		return mysql_error($this->dbh);
	}
	/**
	 * Close MySQL connection
	 * @return TRUE on success or FALSE on failure.
	 */
	function close(){
		return @mysql_close($this->dbh);
	}
	
	/**
	 * is a slave database ?
	 * @return boolean 
	 */
	function isSlave(){
		return $this->slave;
	}
}
?>
