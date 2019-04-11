<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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
   private $dbh;
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

   private $cache_disabled = false;

   /**
    * Constructor / Connect to the MySQL Database
    *
    * @param integer $choice host number (default NULL)
    *
    * @return void
    */
   function __construct($choice = null) {
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
   function connect($choice = null) {
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

         $this->setTimezone($this->guessTimezone());
      }
   }

   /**
    * Guess timezone
    *
    * Will  check for an existing loaded timezone from user,
    * then will check in preferences and finally will fallback to system one.
    *
    * @return string
    *
    * @since 9.5.0
    */
   protected function guessTimezone() {
      if (isset($_SESSION['glpi_tz'])) {
         $zone = $_SESSION['glpi_tz'];
      } else {
         $conf_tz = ['value' => null];
         if ($this->tableExists(Config::getTable())
             && $this->fieldExists(Config::getTable(), 'value')) {
            $conf_tz = $this->request([
               'SELECT' => 'value',
               'FROM'   => Config::getTable(),
               'WHERE'  => [
                  'context'   => 'core',
                  'name'      => 'timezone'
                ]
            ])->next();
         }
         $zone = !empty($conf_tz['value']) ? $conf_tz['value'] : date_default_timezone_get();
      }

      return $zone;
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

      $is_debug = isset($_SESSION['glpi_use_mode']) && ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE);
      if ($is_debug && $CFG_GLPI["debug_sql"]) {
         $SQL_TOTAL_REQUEST++;
         $DEBUG_SQL["queries"][$SQL_TOTAL_REQUEST] = $query;
      }
      if ($is_debug && $CFG_GLPI["debug_sql"] || $this->execution_time === true) {
         $TIMER                                    = new Timer();
         $TIMER->start();
      }

      $res = @$this->dbh->query($query);
      if (!$res) {
         // no translation for error logs
         $error = "  *** MySQL query error:\n  SQL: ".$query."\n  Error: ".
                   $this->dbh->error."\n";
         $error .= Toolbox::backtrace(false, 'DBmysql->query()', ['Toolbox::backtrace()']);

         Toolbox::logSqlError($error);

         if (($is_debug || isAPI()) && $CFG_GLPI["debug_sql"]) {
            $DEBUG_SQL["errors"][$SQL_TOTAL_REQUEST] = $this->error();
         }
      }

      if ($is_debug && $CFG_GLPI["debug_sql"]) {
         $TIME                                   = $TIMER->getTime();
         $DEBUG_SQL["times"][$SQL_TOTAL_REQUEST] = $TIME;
      }
      if ($this->execution_time === true) {
         $this->execution_time = $TIMER->getTime(0, true);
      }
      return $res;
   }

   /**
    * Execute a MySQL query and die
    * (optionnaly with a message) if it fails
    *
    * @since 0.84
    *
    * @param string $query   Query to execute
    * @param string $message Explanation of query (default '')
    *
    * @return mysqli_result Query result handler
    */
   function queryOrDie($query, $message = '') {
      $res = $this->query($query);
      if (!$res) {
         //TRANS: %1$s is the description, %2$s is the query, %3$s is the error message
         $message = sprintf(
            __('%1$s - Error during the database query: %2$s - Error is %3$s'),
            $message,
            $query,
            $this->error()
         );
         if (isCommandLine()) {
            throw new \RuntimeException($message);
         } else {
            echo $message . "\n";
            die(1);
         }
      }
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
      global $CFG_GLPI, $DEBUG_SQL, $SQL_TOTAL_REQUEST;

      $res = @$this->dbh->prepare($query);
      if (!$res) {
         // no translation for error logs
         $error = "  *** MySQL prepare error:\n  SQL: ".$query."\n  Error: ".
                   $this->dbh->error."\n";
         $error .= Toolbox::backtrace(false, 'DBmysql->prepare()', ['Toolbox::backtrace()']);

         Toolbox::logInFile("sql-errors", $error);
         if (class_exists('GlpitestSQLError')) { // For unit test
            throw new GlpitestSQLError($error);
         }

         if (isset($_SESSION['glpi_use_mode'])
             && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE
             && $CFG_GLPI["debug_sql"]) {
            $SQL_TOTAL_REQUEST++;
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
    * @param string        $field  Field to give
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
    * Please prefer fetchRow or fetchAssoc
    *
    * @param mysqli_result $result MySQL result handler
    *
    * @return string[]|null array results
    *
    * @deprecated 9.5.0
    */
   function fetch_array($result) {
      Toolbox::deprecated('Use DBmysql::fetchArray()');
      return $this->fetchArray($result);
   }

   /**
    * Fetch array of the next row of a Mysql query
    * Please prefer fetchRow or fetchAssoc
    *
    * @param mysqli_result $result MySQL result handler
    *
    * @return string[]|null array results
    */
   function fetchArray($result) {
      return $result->fetch_array();
   }

   /**
    * Fetch row of the next row of a Mysql query
    *
    * @param mysqli_result $result MySQL result handler
    *
    * @return mixed|null result row
    *
    * @deprecated 9.5.0
    */
   function fetch_row($result) {
      Toolbox::deprecated('Use DBmysql::fetchRow()');
      return $this->fetchRow($result);
   }

   /**
    * Fetch row of the next row of a Mysql query
    *
    * @param mysqli_result $result MySQL result handler
    *
    * @return mixed|null result row
    */
   function fetchRow($result) {
      return $result->fetch_row();
   }

   /**
    * Fetch assoc of the next row of a Mysql query
    *
    * @param mysqli_result $result MySQL result handler
    *
    * @return string[]|null result associative array
    *
    * @deprecated 9.5.0
    */
   function fetch_assoc($result) {
      Toolbox::deprecated('Use DBmysql::fetchAssoc()');
      return $this->fetchAssoc($result);
   }

   /**
    * Fetch assoc of the next row of a Mysql query
    *
    * @param mysqli_result $result MySQL result handler
    *
    * @return string[]|null result associative array
    */
   function fetchAssoc($result) {
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
      Toolbox::deprecated('Use DBmysql::fetchObject()');
      return $this->fetchObject();
   }

   /**
    * Fetch object of the next row of an SQL query
    *
    * @param mysqli_result $result MySQL result handler
    *
    * @return object|null
    */
   function fetchObject($result) {
      return $result->fetch_object();
   }

   /**
    * Move current pointer of a Mysql result to the specific row
    *
    * @deprecated 9.5.0
    *
    * @param mysqli_result $result MySQL result handler
    * @param integer       $num    Row to move current pointer
    *
    * @return boolean
    */
   function data_seek($result, $num) {
      Toolbox::deprecated('Use DBmysql::dataSeek()');
      return $this->dataSeek($result, $num);
   }

   /**
    * Move current pointer of a Mysql result to the specific row
    *
    * @param mysqli_result $result MySQL result handler
    * @param integer       $num    Row to move current pointer
    *
    * @return boolean
    */
   function dataSeek($result, $num) {
      return $result->data_seek($num);
   }


   /**
    * Give ID of the last inserted item by Mysql
    *
    * @return mixed
    *
    * @deprecated 9.5.0
    */
   function insert_id() {
      Toolbox::deprecated('Use DBmysql::insertId()');
      return $this->insertId();
   }

   /**
    * Give ID of the last inserted item by Mysql
    *
    * @return mixed
    *
    * @deprecated 9.5.0
    */
   function insertId() {
      return $this->dbh->insert_id;
   }

   /**
    * Give number of fields of a Mysql result
    *
    * @deprecated 9.5.0
    *
    * @param mysqli_result $result MySQL result handler
    *
    * @return int number of fields
    */
   function num_fields($result) {
      Toolbox::deprecated('Use DBmysql::numFields()');
      return $this->numFields($result);
   }

   /**
    * Give number of fields of a Mysql result
    *
    * @param mysqli_result $result MySQL result handler
    *
    * @return int number of fields
    */
   function numFields($result) {
      return $result->field_count;
   }


   /**
    * Give name of a field of a Mysql result
    *
    * @param mysqli_result $result MySQL result handler
    * @param integer       $nb     ID of the field
    *
    * @return string name of the field
    *
    * @deprecated 9.5.0
    */
   function field_name($result, $nb) {
      Toolbox::deprecated('Use DBmysql::fieldName()');
      return $this->fieldName($result, $nb);
   }

   /**
    * Give name of a field of a Mysql result
    *
    * @param mysqli_result $result MySQL result handler
    * @param integer       $nb     ID of the field
    *
    * @return string name of the field
    *
    * @deprecated 9.5.0
    */
   function fieldName($result, $nb) {
      $finfo = $result->fetch_fields();
      return $finfo[$nb]->name;
   }


   /**
    * List tables in database
    *
    * @param string $table Table name condition (glpi_% as default to retrieve only glpi tables)
    * @param array  $where Where clause to append
    *
    * @return DBmysqlIterator
    */
   function listTables($table = 'glpi_%', array $where = []) {
      $iterator = $this->request([
         'SELECT' => 'table_name as TABLE_NAME',
         'FROM'   => 'information_schema.tables',
         'WHERE'  => [
            'table_schema' => $this->dbdefault,
            'table_type'   => 'BASE TABLE',
            'table_name'   => ['LIKE', $table]
         ] + $where
      ]);
      return $iterator;
   }

   /**
    * Returns tables using "MyIsam" engine.
    *
    * @return DBmysqlIterator
    */
   public function getMyIsamTables(): DBmysqlIterator {
      $iterator = $this->listTables('glpi_%', ['engine' => 'MyIsam']);
      return $iterator;
   }

   /**
    * List fields of a table
    *
    * @param string  $table    Table name condition
    * @param boolean $usecache If use field list cache (default true)
    *
    * @return mixed list of fields
    *
    * @deprecated 9.5.0
    */
   function list_fields($table, $usecache = true) {
       Toolbox::deprecated('Use DBmysql::listFields()');
      return $this->listFields($table, $usecache);
   }

   /**
    * List fields of a table
    *
    * @param string  $table    Table name condition
    * @param boolean $usecache If use field list cache (default true)
    *
    * @return mixed list of fields
    */
   function listFields($table, $usecache = true) {

      static $cache = [];

      if (!$this->cache_disabled && $usecache && isset($cache[$table])) {
         return $cache[$table];
      }
      $result = $this->query("SHOW COLUMNS FROM `$table`");
      if ($result) {
         if ($this->numrows($result) > 0) {
            $cache[$table] = [];
            while ($data = $this->fetchAssoc($result)) {
               $cache[$table][$data["Field"]] = $data;
            }
            return $cache[$table];
         }
         return [];
      }
      return false;
   }

   /**
    * Get number of affected rows in previous MySQL operation
    *
    * @return int number of affected rows on success, and -1 if the last query failed.
    *
    * @deprecated 9.5.0
    */
   function affected_rows() {
      Toolbox::deprecated('Use DBmysql::affectedRows()');
      return $this->affectedRows();
   }

   /**
    * Get number of affected rows in previous MySQL operation
    *
    * @return int number of affected rows on success, and -1 if the last query failed.
    */
   function affectedRows() {
      return $this->dbh->affected_rows;
   }


   /**
    * Free result memory
    *
    * @param mysqli_result $result MySQL result handler
    *
    * @return boolean
    *
    * @deprecated 9.5.0
    */
   function free_result($result) {
      Toolbox::deprecated('Use DBmysql::freeResult()');
      return $this->freeResult($result);
   }

   /**
    * Free result memory
    *
    * @param mysqli_result $result MySQL result handler
    *
    * @return boolean
    */
   function freeResult($result) {
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
      if ($this->connected && $this->dbh) {
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
               if (!isCommandLine()) {
                  // Flush will prevent proxy to timeout as it will receive data.
                  // Flush requires a content to be sent, so we sent sp&aces as multiple spaces
                  // will be shown as a single one on browser.
                  echo ' ';
                  flush();
               }
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
   public function request ($tableorsql, $crit = "", $debug = false) {
      $iterator = new DBmysqlIterator($this);
      $iterator->execute($tableorsql, $crit, $debug);
      return $iterator;
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
      $ret = [];
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
    *
    * @var DB $DB
    *
    * @param string $msg Mode
    *
    * @return boolean
    *
    * @since 0.90
    * @deprecated 9.5.0
    */
   static public function isMySQLStrictMode(&$msg) {
      Toolbox::deprecated();
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
      list($lock_ok) = $this->fetchRow($result);

      return (bool)$lock_ok;
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
      list($lock_ok) = $this->fetchRow($result);

      return $lock_ok;
   }


   /**
    * Check if a table exists
    *
    * @since 9.2
    *
    * @param string $tablename Table name
    *
    * @return boolean
    **/
   public function tableExists($tablename) {
      // Get a list of tables contained within the database.
      $result = $this->listTables("%$tablename%");

      if (count($result)) {
         while ($data = $result->next()) {
            if ($data['TABLE_NAME'] === $tablename) {
               return true;
            }
         }
      }

      return false;
   }

   /**
    * Check if a field exists
    *
    * @since 9.2
    *
    * @param string  $table    Table name for the field we're looking for
    * @param string  $field    Field name
    * @param Boolean $usecache Use cache; @see DBmysql::listFields(), defaults to true
    *
    * @return boolean
    **/
   public function fieldExists($table, $field, $usecache = true) {
      if (!$this->tableExists($table)) {
         trigger_error("Table $table does not exists", E_USER_WARNING);
         return false;
      }

      if ($fields = $this->listFields($table, $usecache)) {
         if (isset($fields[$field])) {
            return true;
         }
         return false;
      }
      return false;
   }

   /**
    * Disable table cache globally; usefull for migrations
    *
    * @return void
    */
   public function disableTableCaching() {
      $this->cache_disabled = true;
   }

   /**
    * Quote field name
    *
    * @since 9.3
    *
    * @param string $name of field to quote (or table.field)
    *
    * @return string
    */
   public static function quoteName($name) {
      //handle verbatim names
      if ($name instanceof QueryExpression) {
         return $name->getValue();
      }
      //handle aliases
      $names = preg_split('/ AS /i', $name);
      if (count($names) > 2) {
         throw new \RuntimeException(
            'Invalid field name ' . $name
         );
      }
      if (count($names) == 2) {
         $name = self::quoteName($names[0]);
         $name .= ' AS ' . self::quoteName($names[1]);
         return $name;
      } else {
         if (strpos($name, '.')) {
            $n = explode('.', $name, 2);
            $table = self::quoteName($n[0]);
            $field = ($n[1] === '*') ? $n[1] : self::quoteName($n[1]);
            return "$table.$field";
         }
         return ($name[0]=='`' ? $name : ($name === '*') ? $name : "`$name`");
      }
   }

   /**
    * Quote value for insert/update
    *
    * @param mixed $value Value
    *
    * @return mixed
    */
   public static function quoteValue($value) {
      if ($value instanceof QueryParam || $value instanceof QueryExpression) {
         //no quote for query parameters nor expressions
         $value = $value->getValue();
      } else if ($value === null || $value === 'NULL' || $value === 'null') {
         $value = 'NULL';
      } else if (!preg_match("/^`.*?`$/", $value)) { //`field` is valid only for mysql :/
         //phone numbers may start with '+' and will be considered as numeric
         $value = "'$value'";
      }
      return $value;
   }

   /**
    * Builds an insert statement
    *
    * @since 9.3
    *
    * @param string $table  Table name
    * @param array  $params Query parameters ([field name => field value)
    *
    * @return string
    */
   public function buildInsert($table, $params) {
      $query = "INSERT INTO " . self::quoteName($table) . " (";

      $fields = [];
      foreach ($params as $key => &$value) {
         $fields[] = $this->quoteName($key);
         $value = $this->quoteValue($value);
      }

      $query .= implode(', ', $fields);
      $query .= ") VALUES (";
      $query .= implode(", ", $params);
      $query .= ")";

      return $query;
   }

   /**
    * Insert a row in the database
    *
    * @since 9.3
    *
    * @param string $table  Table name
    * @param array  $params Query parameters ([field name => field value)
    *
    * @return mysqli_result|boolean Query result handler
    */
   public function insert($table, $params) {
      $result = $this->query(
         $this->buildInsert($table, $params)
      );
      return $result;
   }

   /**
    * Insert a row in the database and die
    * (optionnaly with a message) if it fails
    *
    * @since 9.3
    *
    * @param string $table  Table name
    * @param array  $params  Query parameters ([field name => field value)
    * @param string $message Explanation of query (default '')
    *
    * @return mysqli_result|boolean Query result handler
    */
   function insertOrDie($table, $params, $message = '') {
      $insert = $this->buildInsert($table, $params);
      $res = $this->query($insert);
      if (!$res) {
         //TRANS: %1$s is the description, %2$s is the query, %3$s is the error message
         $message = sprintf(
            __('%1$s - Error during the database query: %2$s - Error is %3$s'),
            $message,
            $insert,
            $this->error()
         );
         if (isCommandLine()) {
            throw new \RuntimeException($message);
         } else {
            echo $message . "\n";
            die(1);
         }
      }
      return $res;
   }

   /**
    * Builds an update statement
    *
    * @since 9.3
    *
    * @param string $table   Table name
    * @param array  $params  Query parameters ([field name => field value)
    * @param array  $clauses Clauses to use. If not 'WHERE' key specified, will b the WHERE clause (@see DBmysqlIterator capabilities)
    * @param array  $joins  JOINS criteria array
    *
    * @since 9.4.0 $joins parameter added
    * @return string
    */
   public function buildUpdate($table, $params, $clauses, array $joins = []) {
      //when no explicit "WHERE", we only have a WHEre clause.
      if (!isset($clauses['WHERE'])) {
         $clauses  = ['WHERE' => $clauses];
      } else {
         $known_clauses = ['WHERE', 'ORDER', 'LIMIT', 'START'];
         foreach (array_keys($clauses) as $key) {
            if (!in_array($key, $known_clauses)) {
               throw new \RuntimeException(
                  str_replace(
                     '%clause',
                     $key,
                     'Trying to use an unknonw clause (%clause) building update query!'
                  )
               );
            }
         }
      }

      if (!count($clauses['WHERE'])) {
         throw new \RuntimeException('Cannot run an UPDATE query without WHERE clause!');
      }

      $query  = "UPDATE ". self::quoteName($table);

      //JOINS
      $it = new DBmysqlIterator($this);
      $query .= $it->analyzeJoins($joins);

      $query .= " SET ";
      foreach ($params as $field => $value) {
         $query .= self::quoteName($field) . " = ".$this->quoteValue($value).", ";
      }
      $query = rtrim($query, ', ');

      $query .= " WHERE " . $it->analyseCrit($clauses['WHERE']);

      // ORDER BY
      if (isset($clauses['ORDER']) && !empty($clauses['ORDER'])) {
         $query .= $it->handleOrderClause($clauses['ORDER']);
      }

      if (isset($clauses['LIMIT']) && !empty($clauses['LIMIT'])) {
         $offset = (isset($clauses['START']) && !empty($clauses['START'])) ? $clauses['START'] : null;
         $query .= $it->handleLimits($clauses['LIMIT'], $offset);
      }

      return $query;
   }

   /**
    * Update a row in the database
    *
    * @since 9.3
    *
    * @param string $table  Table name
    * @param array  $params Query parameters ([:field name => field value)
    * @param array  $where  WHERE clause
    * @param array  $joins  JOINS criteria array
    *
    * @since 9.4.0 $joins parameter added
    * @return mysqli_result|boolean Query result handler
    */
   public function update($table, $params, $where, array $joins = []) {
      $query = $this->buildUpdate($table, $params, $where, $joins);
      $result = $this->query($query);
      return $result;
   }

   /**
    * Update a row in the database or die
    * (optionnaly with a message) if it fails
    *
    * @since 9.3
    *
    * @param string $table   Table name
    * @param array  $params  Query parameters ([:field name => field value)
    * @param array  $where   WHERE clause
    * @param string $message Explanation of query (default '')
    * @param array  $joins   JOINS criteria array
    *
    * @since 9.4.0 $joins parameter added
    * @return mysqli_result|boolean Query result handler
    */
   function updateOrDie($table, $params, $where, $message = '', array $joins = []) {
      $update = $this->buildUpdate($table, $params, $where, $joins);
      $res = $this->query($update);
      if (!$res) {
         //TRANS: %1$s is the description, %2$s is the query, %3$s is the error message
         $message = sprintf(
            __('%1$s - Error during the database query: %2$s - Error is %3$s'),
            $message,
            $update,
            $this->error()
         );
         if (isCommandLine()) {
            throw new \RuntimeException($message);
         } else {
            echo $message . "\n";
            die(1);
         }
      }
      return $res;
   }

   /**
    * Update a row in the database or insert a new one
    *
    * @since 9.4
    *
    * @param string  $table   Table name
    * @param array   $params  Query parameters ([:field name => field value)
    * @param array   $where   WHERE clause
    * @param boolean $onlyone Do the update only one one element, defaults to true
    *
    * @return mysqli_result|boolean Query result handler
    */
   public function updateOrInsert($table, $params, $where, $onlyone = true) {
      $req = $this->request($table, $where);
      $data = array_merge($where, $params);
      if ($req->count() == 0) {
         return $this->insertOrDie($table, $data, 'Unable to create new element or update existing one');
      } else if ($req->count() == 1 || !$onlyone) {
         return $this->updateOrDie($table, $data, $where, 'Unable to create new element or update existing one');
      } else {
         Toolbox::logWarning('Update would change too many rows!');
         return false;
      }
   }

   /**
    * Builds a delete statement
    *
    * @since 9.3
    *
    * @param string $table  Table name
    * @param array  $params Query parameters ([field name => field value)
    * @param array  $where  WHERE clause (@see DBmysqlIterator capabilities)
    * @param array  $joins  JOINS criteria array
    *
    * @since 9.4.0 $joins parameter added
    * @return string
    */
   public function buildDelete($table, $where, array $joins = []) {

      if (!count($where)) {
         throw new \RuntimeException('Cannot run an DELETE query without WHERE clause!');
      }

      $query  = "DELETE " . self::quoteName($table) . " FROM ". self::quoteName($table);

      $it = new DBmysqlIterator($this);
      $query .= $it->analyzeJoins($joins);
      $query .= " WHERE " . $it->analyseCrit($where);

      return $query;
   }

   /**
    * Delete rows in the database
    *
    * @since 9.3
    *
    * @param string $table  Table name
    * @param array  $where  WHERE clause
    * @param array  $joins  JOINS criteria array
    *
    * @since 9.4.0 $joins parameter added
    * @return mysqli_result|boolean Query result handler
    */
   public function delete($table, $where, array $joins = []) {
      $query = $this->buildDelete($table, $where, $joins);
      $result = $this->query($query);
      return $result;
   }

   /**
    * Delete a row in the database and die
    * (optionnaly with a message) if it fails
    *
    * @since 9.3
    *
    * @param string $table   Table name
    * @param array  $where   WHERE clause
    * @param string $message Explanation of query (default '')
    * @param array  $joins   JOINS criteria array
    *
    * @since 9.4.0 $joins parameter added
    * @return mysqli_result|boolean Query result handler
    */
   function deleteOrDie($table, $where, $message = '', array $joins = []) {
      $update = $this->buildDelete($table, $where, $joins);
      $res = $this->query($update);
      if (!$res) {
         //TRANS: %1$s is the description, %2$s is the query, %3$s is the error message
         $message = sprintf(
            __('%1$s - Error during the database query: %2$s - Error is %3$s'),
            $message,
            $update,
            $this->error()
         );
         if (isCommandLine()) {
            throw new \RuntimeException($message);
         } else {
            echo $message . "\n";
            die(1);
         }

      }
      return $res;
   }


   /**
    * Get table schema
    *
    * @param string $table Table name,
    * @param string|null $structure Raw table structure
    *
    * @return array
    */
   public function getTableSchema($table, $structure = null) {
      if ($structure === null) {
         $structure = $this->query("SHOW CREATE TABLE `$table`")->fetch_row();
         $structure = $structure[1];
      }

      //get table index
      $index = preg_grep(
         "/^\s\s+?KEY/",
         array_map(
            function($idx) { return rtrim($idx, ','); },
            explode("\n", $structure)
         )
      );
      //get table schema, without index, without AUTO_INCREMENT
      $structure = preg_replace(
         [
            "/\s\s+KEY .*/",
            "/AUTO_INCREMENT=\d+ /"
         ],
         "",
         $structure
      );
      $structure = preg_replace('/,(\s)?$/m', '', $structure);
      $structure = preg_replace('/ COMMENT \'(.+)\'/', '', $structure);

      $structure = str_replace(
         [
            " COLLATE utf8_unicode_ci",
            " CHARACTER SET utf8",
            ', ',
         ], [
            '',
            '',
            ',',
         ],
         trim($structure)
      );

      //do not check engine nor collation
      $structure = preg_replace(
         '/\) ENGINE.*$/',
         '',
         $structure
      );

      //Mariadb 10.2 will return current_timestamp()
      //while older retuns CURRENT_TIMESTAMP...
      $structure = preg_replace(
         '/ CURRENT_TIMESTAMP\(\)/i',
         ' CURRENT_TIMESTAMP',
         $structure
      );

      //Mariadb 10.2 allow default values on longblob, text and longtext
      $defaults = [];
      preg_match_all(
         '/^.+ (longblob|text|longtext) .+$/m',
         $structure,
         $defaults
      );
      if (count($defaults[0])) {
         foreach ($defaults[0] as $line) {
               $structure = str_replace(
                  $line,
                  str_replace(' DEFAULT NULL', '', $line),
                  $structure
               );
         }
      }

      $structure = preg_replace("/(DEFAULT) ([-|+]?\d+)(\.\d+)?/", "$1 '$2$3'", $structure);
      //$structure = preg_replace("/(DEFAULT) (')?([-|+]?\d+)(\.\d+)(')?/", "$1 '$3'", $structure);
      $structure = preg_replace('/(BIGINT)\(\d+\)/i', '$1', $structure);
      $structure = preg_replace('/(TINYINT) /i', '$1(4) ', $structure);

      return [
         'schema' => strtolower($structure),
         'index'  => $index
      ];

   }

   /**
    * Get database raw version
    *
    * @return string
    */
   public function getVersion() {
      global $DB;
      $req = $DB->request('SELECT version()')->next();
      $raw = $req['version()'];
      return $raw;
   }

   /**
    * Starts a transaction
    *
    * @return boolean
    */
   public function beginTransaction() {
      return $this->dbh->begin_transaction();
   }

   /**
    * Commits a transaction
    *
    * @return boolean
    */
   public function commit() {
      return $this->dbh->commit();
   }

   /**
    * Roolbacks a transaction
    *
    * @return boolean
    */
   public function rollBack() {
      return $this->dbh->rollback();
   }

   /**
    * Check if timezone data is accessible and available in database.
    *
    * @param string $msg  Variable that would contain the reason of data unavailability.
    *
    * @return boolean
    *
    * @since 9.5.0
    */
   public function areTimezonesAvailable(string &$msg = '') {
      $mysql_db_res = $this->request('SHOW DATABASES LIKE ' . $this->quoteValue('mysql'));
      if ($mysql_db_res->count() === 0) {
         $msg = __('Access to timezone database (mysql) is not allowed.');
         return false;
      }

      $tz_table_res = $this->request(
         'SHOW TABLES FROM '
         . $this->quoteName('mysql')
         . ' LIKE '
         . $this->quoteValue('time_zone_name')
      );
      if ($tz_table_res->count() === 0) {
         $msg = __('Access to timezone table (mysql.time_zone_name) is not allowed.');
         return false;
      }

      $criteria = [
         'COUNT'  => 'cpt',
         'FROM'   => 'mysql.time_zone_name',
      ];
      $iterator = $this->request($criteria);
      $result = $iterator->next();
      if ($result['cpt'] == 0) {
         $msg = __('Timezones seems not loaded, see https://glpi-install.readthedocs.io/en/latest/timezones.html.');
         return false;
      }

      return true;
   }

   /**
    * Defines timezone to use.
    *
    * @param string $timezone
    *
    * @return DBmysql
    */
   public function setTimezone($timezone) {
      //setup timezone
      if ($this->areTimezonesAvailable()) {
         date_default_timezone_set($timezone);
         $this->dbh->query("SET SESSION time_zone = '$timezone'");
         $_SESSION['glpi_currenttime'] = date("Y-m-d H:i:s");
      }
      return $this;
   }

   /**
    * Returns list of timezones.
    *
    * @return string[]
    *
    * @since 9.5.0
    */
   public function getTimezones() {
      $list = []; //default $tz is empty

      $from_php = \DateTimeZone::listIdentifiers();
      $now = new \DateTime();

      $iterator = $this->request([
         'SELECT' => 'Name',
         'FROM'   => 'mysql.time_zone_name',
         'WHERE'  => ['Name' => $from_php]
      ]);

      while ($from_mysql = $iterator->next()) {
         $now->setTimezone(new \DateTimeZone($from_mysql['Name']));
         $list[$from_mysql['Name']] = $from_mysql['Name'] . $now->format(" (T P)");
      }

      return $list;
   }

   /**
    * Returns count of tables that were not migrated to be compatible with timezones usage.
    *
    * @return number
    *
    * @since 9.5.0
    */
   public function notTzMigrated() {
       global $DB;

       $result = $DB->request([
           'COUNT'       => 'cpt',
           'FROM'        => 'information_schema.columns',
           'WHERE'       => [
              'information_schema.columns.table_schema'  => $DB->dbdefault,
              'information_schema.columns.column_type'   => ['datetime']
           ]
       ])->next();
       return (int)$result['cpt'];
   }
}
