<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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

/// DB class to connect to a OCS server
class DBocs extends DBmysql {

   ///Store the id of the ocs server
   var $ocsservers_id = -1;

   /**
    * Constructor
    * @param $ID ID of the ocs server ID
   **/
   function __construct($ID) {
      global $CFG_GLPI;

      $this->ocsservers_id = $ID;
      if ($CFG_GLPI["use_ocs_mode"]) {
         $data = OcsServer::getConfig($ID);
         $this->dbhost = $data["ocs_db_host"];
         $this->dbuser = $data["ocs_db_user"];
         $this->dbpassword = rawurldecode($data["ocs_db_passwd"]);
         $this->dbdefault = $data["ocs_db_name"];
         $this->dbenc = $data["ocs_db_utf8"] ? "utf8" : "latin1";
         parent::__construct();
      }
   }

   /**
    * Get current ocs server ID
    * @return ID of the ocs server ID
   **/
   function getServerID() {
      return $this->ocsservers_id;
   }

}

/**
 *  Database class for Mysql
 */
class DBmysql {

   //! Database Host - string or Array of string (round robin)
   var $dbhost = "";
   //! Database User
   var $dbuser = "";
   //! Database Password
   var $dbpassword = "";
   //! Default Database
   var $dbdefault = "";
   //! Database Handler
   var $dbh;
   //! Database Error
   var $error = 0;

   /// Slave management
   var $slave = false;
   /** Is it a first connection ?
   * Indicates if the first connection attempt is successful or not
   * if first attempt fail -> display a warning which indicates that glpi is in readonly
   */
   var $first_connection = true;
   /// Is connected to the DB ?
   var $connected = false;

   /**
    * Constructor / Connect to the MySQL Database
    *
    * try to connect
    * @return nothing
    */
   function __construct() {
      $this->connect();
   }

   /**
    * Connect using current database settings
    *
    * Use dbhost, dbuser, dbpassword and dbdefault
    *
    * @return nothing
    */
   function connect() {

      $this->connected=false;

      if (is_array($this->dbhost)) {
         // Round robin choice
         $i = mt_rand(0,count($this->dbhost)-1);
         $host = $this->dbhost[$i];
         //logDebug("Chosen server $i = $host");
      } else {
         $host = $this->dbhost;
      }
      $this->dbh = @mysql_connect($host, $this->dbuser, rawurldecode($this->dbpassword))
                   or $this->error = 1;
      if ($this->dbh) { // connexion ok
         @mysql_query("SET NAMES '" . (isset($this->dbenc) ? $this->dbenc : "utf8") . "'",$this->dbh);
         $select= mysql_select_db($this->dbdefault)
                  or $this->error = 1;
         if ($select) { // select ok
            $this->connected=true;
         } else { // select wrong
            $this->connected=false;
         }
      } else { // connexion wrong
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

      if ($_SESSION['glpi_use_mode']==DEBUG_MODE && $CFG_GLPI["debug_sql"]) {
         $SQL_TOTAL_REQUEST++;
         $DEBUG_SQL["queries"][$SQL_TOTAL_REQUEST]=$query;
         $TIMER=new Timer;
         $TIMER->start();
      }

      $res=@mysql_query($query,$this->dbh);
      if (!$res) {
         $this->connect();
         $res=mysql_query($query,$this->dbh);
         if (!$res) {
            $error = "*** MySQL query error : \n***\nSQL: ".addslashes($query)."\nError: ".
                      mysql_error()."\n";
            if (function_exists("debug_backtrace")) {
               $error .= "Backtrace :\n";
               $traces=debug_backtrace();
               foreach ($traces as $trace) {
                  $error .= (isset($trace["file"]) ? $trace["file"] : "") . "&nbsp;:" .
                            (isset($trace["line"]) ? $trace["line"] : "") . "\t\t" .
                            (isset($trace["class"]) ? $trace["class"] : "") .
                            (isset($trace["type"]) ? $trace["type"] : "") .
                            (isset($trace["function"]) ? $trace["function"]."()" : "") ."\n";
               }
            } else {
               $error .= "Script : " ;
            }
            $error .= $_SERVER["SCRIPT_FILENAME"]. "\n";
            logInFile("sql-errors",$error."\n");

            if ($_SESSION['glpi_use_mode']==DEBUG_MODE && $CFG_GLPI["debug_sql"]) {
               $DEBUG_SQL["errors"][$SQL_TOTAL_REQUEST]=$this->error();
            }
         }
      }

      if ($_SESSION['glpi_use_mode']==DEBUG_MODE && $CFG_GLPI["debug_sql"]) {
         $TIME=$TIMER->getTime();
         $DEBUG_SQL["times"][$SQL_TOTAL_REQUEST]=$TIME;
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

      $value=get_magic_quotes_runtime()?stripslashes_deep(mysql_result($result, $i, $field))
                                       :mysql_result($result, $i, $field);
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

      $value=get_magic_quotes_runtime()?stripslashes_deep(mysql_fetch_array($result))
                                       :mysql_fetch_array($result);
      return $value;
   }

   /**
    * Fetch row of the next row of a Mysql query
    * @param $result MySQL result handler
    * @return result row
    */
   function fetch_row($result) {

      $value=get_magic_quotes_runtime()?stripslashes_deep(mysql_fetch_row($result))
                                       :mysql_fetch_row($result);
      return $value;
   }

   /**
    * Fetch assoc of the next row of a Mysql query
    * @param $result MySQL result handler
    * @return result associative array
    */
   function fetch_assoc($result) {

      $value=get_magic_quotes_runtime()?stripslashes_deep(mysql_fetch_assoc($result))
                                       :mysql_fetch_assoc($result);
      return $value;
   }

   /**
    * Move current pointer of a Mysql result to the specific row
    * @param $result MySQL result handler
    * @param $num row to move current pointer
    * @return boolean
    */
   function data_seek($result,$num) {
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
   function field_name($result,$nb) {
      return mysql_field_name($result,$nb);
   }

   /**
    * Get flags of a field of a mysql result
    * @param $result MySQL result handler
    * @param $field field name
    * @return flags of the field
    */
   function field_flags($result,$field) {
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

      $result = $this->query("SHOW COLUMNS FROM `$table`");
      if ($result) {
         if ($this->numrows($result) > 0) {
            while ($data = mysql_fetch_assoc($result)) {
               $ret[$data["Field"]]= $data;
            }
            return $ret;
         } else {
            return array();
         }
      } else {
         return false;
      }
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
    * @param $result MySQL result handler
    * @return Returns TRUE on success or FALSE on failure.
    */
   function free_result($result) {
      return mysql_free_result($result);
   }

   /**
    * Returns the numerical value of the error message from previous MySQL operation
    * @return error number from the last MySQL function, or 0 (zero) if no error occurred.
    */
   function errno() {
      return mysql_errno($this->dbh);
   }

   /**
    * Returns the text of the error message from previous MySQL operation
    * @return error text from the last MySQL function, or '' (empty string) if no error occurred.
    */
   function error() {
      return mysql_error($this->dbh);
   }

   /**
    * Close MySQL connection
    * @return TRUE on success or FALSE on failure.
    */
   function close() {
      return @mysql_close($this->dbh);
   }

   /**
    * is a slave database ?
    * @return boolean
    */
   function isSlave() {
      return $this->slave;
   }

   /**
    * Execute all the request in a file
    *
    * @param $path string with file full path
    *
    * @return boolean true if all query are successfull
    */
   function runFile ($path) {

      $DBf_handle = fopen($path, "rt");
      if (!$DBf_handle) {
         return false;
      }

      $formattedQuery = "";
      $lastresult=false;
      while (!feof($DBf_handle)) {
         // specify read length to be able to read long lines
         $buffer = fgets($DBf_handle,102400);

         // do not strip comments due to problems when # in begin of a data line
         $formattedQuery .= $buffer;
         if (substr(rtrim($formattedQuery),-1) == ";") {

            if (get_magic_quotes_runtime()) {
               $formattedQuerytorun = stripslashes($formattedQuery);
            } else {
               $formattedQuerytorun = $formattedQuery;
            }

            // Do not use the $DB->query
            if ($this->query($formattedQuerytorun)) { //if no success continue to concatenate
               $formattedQuery = "";
               $lastresult=true;
            } else {
               $lastresult=false;
            }
         }
      }

      return $lastresult;
   }

   /**
    * Instanciate a Simple DBIterator
    *
    * Examples =
    *  foreach ($DB->request("select * from glpi_states") as $data) { ... }
    *  foreach ($DB->request("glpi_states") as $ID => $data) { ... }
    *  foreach ($DB->request("glpi_states", "ID=1") as $ID => $data) { ... }
    *  foreach ($DB->request("glpi_states", "", "name") as $ID => $data) { ... }
    *  foreach ($DB->request("glpi_computers",array("name"=>"SBEI003W","entities_id"=>1),array("serial","otherserial")) { ... }
    *
    * @param $tableorsql table name, array of names or SQL query
    * @param $crit string or array of filed/values, ex array("id"=>1), if empty => all rows
    *
    * Examples =
    *   array("id"=>NULL)
    *   array("OR"=>array("id"=>1, "NOT"=>array("state"=>3)));
    *   array("AND"=>array("id"=>1, array("NOT"=>array("state"=>array(3,4,5),"toto"=>2))))
    *
    * param 'field' name or array of field names
    * param 'orderby' filed name or array of field names
    * param 'limit' max of row to retrieve
    * param 'start' first row to retrieve
    *
    * @return DBIterator
    **/
   public function request ($tableorsql, $crit="") {
      return new DBmysqlIterator ($this, $tableorsql, $crit);
   }

}

/*
 * Helper for simple query => see $DBmysql->requete
 */
class DBmysqlIterator  implements Iterator {
   /// DBmysql object
   private $con;
   /// Current SQL query
   private $sql;
   /// Current result
   private $res = false;
   /// Current row
   private $row;

   /**
    * Constructor
    *
    * @param $dbconnexion Database Connnexion (must be a CommonDBTM object)
    * @param $table table name
    * @param $crit string or array of filed/values, ex array("id"=>1), if empty => all rows
    *
    **/
   function __construct ($dbconnexion, $table, $crit="") {

      $this->conn = $dbconnexion;
      if (is_string($table) && strpos($table, " ")) {
         $this->sql = $table;
      } else {
         // Check field, orderby, limit, start in criterias
         $field="";
         $orderby="";
         $limit=0;
         $start=0;
         if (is_array($crit) && count($crit)) {
            foreach ($crit as $key => $val) {
               if ($key==="FIELDS") {
                  $field=$val;
                  unset($crit[$key]);
               } else if ($key==="ORDER") {
                  $orderby=$val;
                  unset($crit[$key]);
               } else if ($key==="LIMIT") {
                  $limit=$val;
                  unset($crit[$key]);
               } else if ($key==="START") {
                  $start=$val;
                  unset($crit[$key]);
               }
            }
         }
         // SELECT field list
         if (is_array($field)) {
            $this->sql = "";
            foreach ($field as $t => $f) {
               if (is_numeric($t)) {
                  $this->sql .= (empty($this->sql) ? "SELECT " : ",") . $f;
               } else if (is_array($f)) {
                  $this->sql .= (empty($this->sql) ? "SELECT $t." : ",$t.") . implode(",$t.",$f);
               } else {
                  $this->sql .= (empty($this->sql) ? "SELECT " : ",") . "$t.$f";
               }
            }
         } else if (empty($field)) {
            $this->sql = "SELECT *";
         } else {
            $this->sql = "SELECT `$field`";
         }
         // FROM table list
         if (is_array($table)) {
            $this->sql .= " FROM `".implode("`, `",$table)."`";
         } else {
            $this->sql .= " FROM `$table`";
         }
         // WHERE criteria list
         if (!empty($crit)) {
            $this->sql .= " WHERE ".$this->analyseCrit($crit);
         }
         // ORDER BY
         if (is_array($orderby)) {
            $this->sql .= " ORDER BY `".implode("`, `",$orderby)."`";
         } else if (!empty($orderby)) {
            $this->sql .= " ORDER BY `$orderby`";
         }
         if (is_numeric($limit) && $limit>0) {
            $this->sql .= " LIMIT $limit";
            if (is_numeric($start) && $start>0) {
               $this->sql .= " OFFSET $start";
            }
         }
      }
      $this->res = $this->conn->query($this->sql);
   }

   function __destruct () {

      if ($this->res) {
         $this->conn->free_result($this->res);
      }
   }

   private function analyseCrit ($crit, $bool="AND") {

      if (!is_array($crit)) {
         return $crit;
      }
      $ret = "";
      foreach ($crit as $name => $value) {
         if (!empty($ret)) {
            $ret .= " $bool ";
         }
         if (is_numeric($name)) {
            // No Key case => recurse.
            $ret .= "(" . $this->analyseCrit($value, $bool) . ")";
         } else if ($name==="OR" || $name==="AND") {
            // Binary logical operator
            $ret .= "(" . $this->analyseCrit($value, $name) . ")";
         } else if ($name==="NOT") {
            // Uninary logicial operator
            $ret .= " NOT (" . $this->analyseCrit($value, "AND") . ")";
         } else if ($name==="FKEY") {
            // Foreign Key condition
            if (is_array($value) && count($value)==2) {
               reset($value);
               list($t1,$f1)=each($value);
               list($t2,$f2)=each($value);
               $ret .= (is_numeric($t1) ? "$f1" : "$t1.$f1") . "=" .
                       (is_numeric($t2) ? "$f2" : "$t2.$f2");
            } else {
               trigger_error("BAD FOREIGN KEY", E_USER_ERROR);
            }
         } else if (is_array($value)) {
            // Array of Value
            $ret .= "$name IN ('". implode("','",$value)."')";
         } else if (is_null($value)) {
            // NULL condition
            $ret .= "$name IS NULL";
         } else if (is_numeric($value)) {
            // Integer
            $ret .= "$name=$value";
         } else {
            // String
            $ret .= "$name='$value'";
         }
      }
      return $ret;
   }

   public function rewind () {

      if ($this->res && $this->conn->numrows($this->res)) {
         $this->conn->data_seek($this->res,0);
      }
      return $this->next();
   }

   public function current() {
      return $this->row;
   }

   public function key() {
      return (isset($this->row["id"]) ? $this->row["id"] : 0);
   }

   public function next() {

      if (!$this->res) {
         return false;
      }
      $this->row = $this->conn->fetch_assoc($this->res);
      return $this->row;
   }

   public function valid() {
      return $this->res && $this->row;
   }

   public function numrows() {
      return ($this->res ? $this->conn->numrows($this->res) : 0);
   }
}
?>