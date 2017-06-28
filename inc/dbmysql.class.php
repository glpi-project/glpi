<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 *  Database class for Mysql
**/
class DBmysql {

   //! Database Host - string or Array of string (round robin)
   public $dbhost             = "";
   //! Database User
   public $dbuser             = "";
   //! Database Password
   public $dbpassword         = "";
   //! Default Database
   public $dbdefault          = "";
   //! Database Handler
   public $dbh;
   //! Database Error
   public $error              = 0;

   // Slave management
   public $slave              = false;
   /** Is it a first connection ?
    * Indicates if the first connection attempt is successful or not
    * if first attempt fail -> display a warning which indicates that glpi is in readonly
   **/
   public $first_connection   = true;
   // Is connected to the DB ?
   public $connected          = false;

   //to calculate execution time
   public $execution_time          = false;

   //to simulate transactions (for tests)
   public $objcreated = [];

   /**
    * Constructor / Connect to the MySQL Database
    *
    * @param integer $choice host number (default NULL)
    *
    * @return void
    */
   function __construct($choice=null) {
      $this->connect($choice);
   }

   /**
    * Connect using current database settings
    * Use dbhost, dbuser, dbpassword and dbdefault
    *
    * @param integer $choice host number (default NULL)
    *
    * @return void
    */
   function connect($choice=null) {
      $this->connected = false;

      if (is_array($this->dbhost)) {
         // Round robin choice
         $i    = (isset($choice) ? $choice : mt_rand(0, count($this->dbhost)-1));
         $host = $this->dbhost[$i];

      } else {
         $host = $this->dbhost;
      }

      $hostport = explode(":", $host);
      if (count($hostport) < 2) {
         // Host
         $this->dbh = @new mysqli($host, $this->dbuser, rawurldecode($this->dbpassword),
                                  $this->dbdefault);

      } else if (intval($hostport[1])>0) {
         // Host:port
         $this->dbh = @new mysqli($hostport[0], $this->dbuser, rawurldecode($this->dbpassword),
                                  $this->dbdefault, $hostport[1]);
      } else {
         // :Socket
         $this->dbh = @new mysqli($hostport[0], $this->dbuser, rawurldecode($this->dbpassword),
                                  $this->dbdefault, ini_get('mysqli.default_port'), $hostport[1]);
      }

      if ($this->dbh->connect_error) {
         $this->connected = false;
         $this->error     = 1;
      } else {
         $this->dbh->set_charset(isset($this->dbenc) ? $this->dbenc : "utf8");

         if (GLPI_FORCE_EMPTY_SQL_MODE) {
            $this->dbh->query("SET SESSION sql_mode = ''");
         }
         $this->connected = true;
      }
   }

   /**
    * Escapes special characters in a string for use in an SQL statement,
    * taking into account the current charset of the connection
    *
    * @since 0.84
    *
    * @param string $string String to escape
    *
    * @return string escaped string
    */
   function escape($string) {
      return $this->dbh->real_escape_string($string);
   }

   /**
    * Execute a MySQL query
    *
    * @param string $query Query to execute
    *
    * @var array   $CFG_GLPI
    * @var array   $DEBUG_SQL
    * @var integer $SQL_TOTAL_REQUEST
    *
    * @return mysqli_result|boolean Query result handler
    *
    * @throws GlpitestSQLError
    */
   function query($query) {
      global $CFG_GLPI, $DEBUG_SQL, $SQL_TOTAL_REQUEST;

      if (($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE)
          && $CFG_GLPI["debug_sql"]) {
         $SQL_TOTAL_REQUEST++;
         $DEBUG_SQL["queries"][$SQL_TOTAL_REQUEST] = $query;
      }
      if (($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE)
         && $CFG_GLPI["debug_sql"] || $this->execution_time === true) {
         $TIMER                                    = new Timer();
         $TIMER->start();
      }

      $res = @$this->dbh->query($query);
      if (!$res) {
         // no translation for error logs
         $error = "  *** MySQL query error:\n  SQL: ".addslashes($query)."\n  Error: ".
                   $this->dbh->error."\n";
         $error .= Toolbox::backtrace(false, 'DBmysql->query()', array('Toolbox::backtrace()'));

         Toolbox::logInFile("sql-errors", $error);
         if (class_exists('GlpitestSQLError')) { // For unit test
            throw new GlpitestSQLError($error);
         }

         if (($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE)
             && $CFG_GLPI["debug_sql"]) {
            $DEBUG_SQL["errors"][$SQL_TOTAL_REQUEST] = $this->error();
         }
      }

      if (($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE)
          && $CFG_GLPI["debug_sql"]) {
         $TIME                                   = $TIMER->getTime();
         $DEBUG_SQL["times"][$SQL_TOTAL_REQUEST] = $TIME;
      }
      if ($this->execution_time === true) {
         $this->execution_time = $TIMER->getTime(0, true);
      }
      return $res;
   }

   /**
    * Execute a MySQL query
    *
    * @since 0.84
    *
    * @param string $query   Query to execute
    * @param string $message Explaination of query (default '')
    *
    * @return mysqli_result Query result handler
    */
   function queryOrDie($query, $message='') {
      //TRANS: %1$s is the description, %2$s is the query, %3$s is the error message
      $res = $this->query($query)
             or die(sprintf(__('%1$s - Error during the database query: %2$s - Error is %3$s'),
                            $message, $query, $this->error()));
      return $res;
   }

   /**
    * Prepare a MySQL query
    *
    * @param string $query Query to prepare
    *
    * @return mysqli_stmt|boolean statement object or FALSE if an error occurred.
    *
    * @throws GlpitestSQLError
    */
   function prepare($query) {
      $res = @$this->dbh->prepare($query);
      if (!$res) {
         // no translation for error logs
         $error = "  *** MySQL prepare error:\n  SQL: ".addslashes($query)."\n  Error: ".
                   $this->dbh->error."\n";
         $error .= Toolbox::backtrace(false, 'DBmysql->prepare()', array('Toolbox::backtrace()'));

         Toolbox::logInFile("sql-errors", $error);
         if (class_exists('GlpitestSQLError')) { // For unit test
            throw new GlpitestSQLError($error);
         }

         if (($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE)
             && $CFG_GLPI["debug_sql"]) {
            $DEBUG_SQL["errors"][$SQL_TOTAL_REQUEST] = $this->error();
         }
      }
      return $res;
   }

   /**
    * Give result from a sql result
    *
    * @param mysqli_result $result MySQL result handler
    * @param int           $i      Row offset to give
    * @param type          $field  Field to give
    *
    * @return mixed Value of the Row $i and the Field $field of the Mysql $result
    */
   function result($result, $i, $field) {
      if ($result && ($result->data_seek($i))
          && ($data = $result->fetch_array())
          && isset($data[$field])) {
         return $data[$field];
      }
      return null;
   }

   /**
    * Number of rows
    *
    * @param mysqli_result $result MySQL result handler
    *
    * @return integer number of rows
    */
   function numrows($result) {
      return $result->num_rows;
   }

   /**
    * Fetch array of the next row of a Mysql query
    * Please prefer fetch_row or fetch_assoc
    *
    * @param mysqli_result $result MySQL result handler
    *
    * @return string[]|null array results
    */
   function fetch_array($result) {
      return $result->fetch_array();
   }

   /**
    * Fetch row of the next row of a Mysql query
    *
    * @param mysqli_result $result MySQL result handler
    *
    * @return mixed|null result row
    */
   function fetch_row($result) {
      return $result->fetch_row();
   }

   /**
    * Fetch assoc of the next row of a Mysql query
    *
    * @param mysqli_result $result MySQL result handler
    *
    * @return string[]|null result associative array
    */
   function fetch_assoc($result) {
      return $result->fetch_assoc();
   }

   /**
    * Fetch object of the next row of an SQL query
    *
    * @param mysqli_result $result MySQL result handler
    *
    * @return object|null
    */
   function fetch_object($result) {
      return $result->fetch_object();
   }

   /**
    * Move current pointer of a Mysql result to the specific row
    *
    * @param mysqli_result $result MySQL result handler
    * @param integer       $num    Row to move current pointer
    *
    * @return boolean
    */
   function data_seek($result, $num) {
      return $result->data_seek($num);
   }

   /**
    * Give ID of the last inserted item by Mysql
    *
    * @return mixed
    */
   function insert_id() {
      return $this->dbh->insert_id;
   }

   /**
    * Give number of fields of a Mysql result
    *
    * @param mysqli_result $result MySQL result handler
    *
    * @return int number of fields
    */
   function num_fields($result) {
      return $result->field_count;
   }

   /**
    * Give name of a field of a Mysql result
    *
    * @param mysqli_result $result MySQL result handler
    * @param integer       $nb     ID of the field
    *
    * @return string name of the field
    */
   function field_name($result, $nb) {
      $finfo = $result->fetch_fields();
      return $finfo[$nb]->name;
   }


   /**
    * List tables in database
    *
    * @param string $table table name condition (glpi_% as default to retrieve only glpi tables)
    *
    * @return mysqli_result list of tables
    */
   function list_tables($table="glpi_%") {
      return $this->query(
         "SELECT TABLE_NAME FROM information_schema.`TABLES`
             WHERE TABLE_SCHEMA = '{$this->dbdefault}'
                AND TABLE_TYPE = 'BASE TABLE'
                AND TABLE_NAME LIKE '$table'"
      );
   }

   /**
    * List fields of a table
    *
    * @param string  $table    Table name condition
    * @param boolean $usecache If use field list cache (default true)
    *
    * @return mixed list of fields
    */
   function list_fields($table, $usecache=true) {
      static $cache = array();

      if ($usecache && isset($cache[$table])) {
         return $cache[$table];
      }
      $result = $this->query("SHOW COLUMNS FROM `$table`");
      if ($result) {
         if ($this->numrows($result) > 0) {
            $cache[$table] = array();
            while ($data = $result->fetch_assoc()) {
               $cache[$table][$data["Field"]] = $data;
            }
            return $cache[$table];
         }
         return array();
      }
      return false;
   }

   /**
    * Get number of affected rows in previous MySQL operation
    *
    * @return int number of affected rows on success, and -1 if the last query failed.
    */
   function affected_rows() {
      return $this->dbh->affected_rows;
   }

   /**
    * Free result memory
    *
    * @param mysqli_result $result MySQL result handler
    *
    * @return boolean TRUE on success or FALSE on failure.
    */
   function free_result($result) {
      return $result->free();
   }

   /**
    * Returns the numerical value of the error message from previous MySQL operation
    *
    * @return int error number from the last MySQL function, or 0 (zero) if no error occurred.
    */
   function errno() {
      return $this->dbh->errno;
   }

   /**
    * Returns the text of the error message from previous MySQL operation
    *
    * @return string error text from the last MySQL function, or '' (empty string) if no error occurred.
    */
   function error() {
      return $this->dbh->error;
   }

   /**
    * Close MySQL connection
    *
    * @return boolean TRUE on success or FALSE on failure.
    */
   function close() {
      if ($this->dbh) {
         return $this->dbh->close();
      }
      return false;
   }

   /**
    * is a slave database ?
    *
    * @return boolean
    */
   function isSlave() {
      return $this->slave;
   }

   /**
    * Execute all the request in a file
    *
    * @param string $path with file full path
    *
    * @return boolean true if all query are successfull
    */
   function runFile($path) {
      $DBf_handle = fopen($path, "rt");
      if (!$DBf_handle) {
         return false;
      }

      $formattedQuery = "";
      $lastresult     = false;
      while (!feof($DBf_handle)) {
         // specify read length to be able to read long lines
         $buffer = fgets($DBf_handle, 102400);

         // do not strip comments due to problems when # in begin of a data line
         $formattedQuery .= $buffer;
         if ((substr(rtrim($formattedQuery), -1) == ";")
             && (substr(rtrim($formattedQuery), -4) != "&gt;")
             && (substr(rtrim($formattedQuery), -4) != "160;")) {

            $formattedQuerytorun = $formattedQuery;

            // Do not use the $DB->query
            if ($this->query($formattedQuerytorun)) { //if no success continue to concatenate
               $formattedQuery = "";
               $lastresult     = true;
            } else {
               $lastresult = false;
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
    * Examples =
    *   array("id"=>NULL)
    *   array("OR"=>array("id"=>1, "NOT"=>array("state"=>3)));
    *   array("AND"=>array("id"=>1, array("NOT"=>array("state"=>array(3,4,5),"toto"=>2))))
    *
    * FIELDS name or array of field names
    * ORDER name or array of field names
    * LIMIT max of row to retrieve
    * START first row to retrieve
    *
    * @param string|string[] $tableorsql Table name, array of names or SQL query
    * @param string|string[] $crit       String or array of filed/values, ex array("id"=>1), if empty => all rows
    *                                    (default '')
    * @param boolean         $debug      To log the request (default false)
    *
    * @return DBmysqlIterator
    */
   public function request ($tableorsql, $crit="", $debug=false) {
      return new DBmysqlIterator($this, $tableorsql, $crit, $debug);
   }

    /**
     *  Optimize sql table
     *
     * @var DB $DB
     *
     * @param mixed   $migration Migration class (default NULL)
     * @param boolean $cron      To know if optimize must be done (false by default)
     *
     * @return int number of tables
     */
   static function optimize_tables($migration=null, $cron=false) {
      global $DB;

      $crashed_tables = self::checkForCrashedTables();
      if (!empty($crashed_tables)) {
         Toolbox::logDebug("Cannot launch automatic action : crashed tables detected");
         return -1;
      }

      if (!is_null($migration) && method_exists($migration, 'displayMessage')) {
         $migration->displayTitle(__('Optimizing tables'));
         $migration->addNewMessageArea('optimize_table'); // to force new ajax zone
         $migration->displayMessage(sprintf(__('%1$s - %2$s'), __('optimize'), __('Start')));
      }
      $result = $DB->list_tables();
      $nb     = 0;

      while ($line = $DB->fetch_row($result)) {
         $table = $line[0];

         // For big database to reduce delay of migration
         if ($cron
             || (countElementsInTable($table) < 15000000)) {

            if (!is_null($migration) && method_exists($migration, 'displayMessage')) {
               $migration->displayMessage(sprintf(__('%1$s - %2$s'), __('optimize'), $table));
            }

            $query = "OPTIMIZE TABLE `".$table."`;";
            $DB->query($query);
            $nb++;
         }
      }
      $DB->free_result($result);

      if (!is_null($migration)
          && method_exists($migration, 'displayMessage') ) {
         $migration->displayMessage(sprintf(__('%1$s - %2$s'), __('optimize'), __('End')));
      }

      return $nb;
   }

   /**
    * Get information about DB connection for showSystemInformations
    *
    * @since 0.84
    *
    * @return string[] Array of label / value
    */
   public function getInfo() {
      // No translation, used in sysinfo
      $ret = array();
      $req = $this->request("SELECT @@sql_mode as mode, @@version AS vers, @@version_comment AS stype");

      if (($data = $req->next())) {
         if ($data['stype']) {
            $ret['Server Software'] = $data['stype'];
         }
         if ($data['vers']) {
            $ret['Server Version'] = $data['vers'];
         } else {
            $ret['Server Version'] = $this->dbh->server_info;
         }
         if ($data['mode']) {
            $ret['Server SQL Mode'] = $data['mode'];
         } else {
            $ret['Server SQL Mode'] = '';
         }
      }
      $ret['Parameters'] = $this->dbuser."@".$this->dbhost."/".$this->dbdefault;
      $ret['Host info']  = $this->dbh->host_info;

      return $ret;
   }

   /**
    * Is MySQL strict mode ?
    * @since 0.90
    *
    * @var DB $DB
    *
    * @param string $msg Mode
    *
    * @return boolean
    */
   static function isMySQLStrictMode(&$msg) {
      global $DB;

      $msg = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE,ONLY_FULL_GROUP_BY,NO_AUTO_CREATE_USER';
      $req = $DB->request("SELECT @@sql_mode as mode");
      if (($data = $req->next())) {
         return (preg_match("/STRICT_TRANS/", $data['mode'])
                 && preg_match("/NO_ZERO_/", $data['mode'])
                 && preg_match("/ONLY_FULL_GROUP_BY/", $data['mode']));
      }
      return false;
   }

   /**
    * Get a global DB lock
    *
    * @since 0.84
    *
    * @param string $name lock's name
    *
    * @return boolean
    */
   public function getLock($name) {
      $name          = addslashes($this->dbdefault.'.'.$name);
      $query         = "SELECT GET_LOCK('$name', 0)";
      $result        = $this->query($query);
      list($lock_ok) = $this->fetch_row($result);

      return $lock_ok;
   }

   /**
    * Release a global DB lock
    *
    * @since 0.84
    *
    * @param string $name lock's name
    *
    * @return boolean
    */
   public function releaseLock($name) {
      $name          = addslashes($this->dbdefault.'.'.$name);
      $query         = "SELECT RELEASE_LOCK('$name')";
      $result        = $this->query($query);
      list($lock_ok) = $this->fetch_row($result);

      return $lock_ok;
   }

   /**
   * Check for crashed MySQL Tables
   *
   * @since 0.90.2
   *
   * @var DB $DB
    *
   * @return string[] array with supposed crashed table and check message
   */
   static public function checkForCrashedTables() {
      global $DB;
      $crashed_tables = array();

      $result_tables = $DB->list_tables();

      while ($line = $DB->fetch_row($result_tables)) {
         $query  = "CHECK TABLE `".$line[0]."` FAST";
         $result  = $DB->query($query);
         if ($DB->numrows($result) > 0) {
            $row = $DB->fetch_array($result);
            if ($row['Msg_type'] != 'status' && $row['Msg_type'] != 'note') {
               $crashed_tables[] = array('table'    => $row[0],
                                         'Msg_type' => $row['Msg_type'],
                                         'Msg_text' => $row['Msg_text']);
            }
         }
      }
      return $crashed_tables;
   }
}
