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

use Glpi\AbstractDatabase;

/**
 *  Database class for Mysql
**/
class DBmysql extends AbstractDatabase {

   public function getDriver(): string {
      return 'mysql';
   }

   public function connect($server = null) {
       $this->connected = false;
       $dsn = $this->getDsn($server);

      if (null === $dsn) {
          return;
      }

      $charset = isset($this->dbenc) ? $this->dbenc : "utf8";
      $this->dbh = new PDO(
         "$dsn;dbname={$this->dbdefault};charset=$charset",
         $this->dbuser,
         rawurldecode($this->dbpassword)
      );
      $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      if (GLPI_FORCE_EMPTY_SQL_MODE) {
         $this->dbh->query("SET SESSION sql_mode = ''");
      }
      $this->connected = true;
   }

   /**
    * Escapes special characters in a string for use in an SQL statement,
    * taking into account the current charset of the connection
    *
    * @since 0.84
    * @deprecated 10.0.0
    *
    * @param string $string String to escape
    *
    * @return string escaped string
    */
   function escape($string) {
       Toolbox::deprecated('Use AbstractDatabase::quote() (and note that returned string will be quoted)');
       $quoted = $this->quote($string);
       return trim($quoted, "'");
   }

   /**
    * Execute a MySQL query
    *
    * @deprecated 10.0.0
    *
    * @param string $query Query to execute
    *
    * @var array   $CFG_GLPI
    * @var array   $DEBUG_SQL
    * @var integer $SQL_TOTAL_REQUEST
    *
    * @return PDOStatement|boolean Query result handler
    *
    * @throws GlpitestSQLError
    */
   function query($query) {
       Toolbox::deprecated();
       return $this->rawQuery($query);
   }

   /**
    * Execute a MySQL query and die
    * (optionnaly with a message) if it fails
    *
    * @since 0.84
    * @deprecated 10.0.0
    *
    * @param string $query   Query to execute
    * @param string $message Explanation of query (default '')
    *
    * @return PDOStatement Query result handler
    */
   function queryOrDie($query, $message = '') {
      Toolbox::deprecated();
      return $this->rawQueryOrDie($query, $message);
   }

   /**
    * Fetch array of the next row of a Mysql query
    * Please prefer fetchRow or fetchAssoc
    *
    * @param PDOStatement $result MySQL result handler
    *
    * @return string[]|null array results
    *
    * @deprecated 10.0.0
    */
   function fetch_array($result) {
      Toolbox::deprecated('Use AbstractDatabase::fetchArray');
      return $this->fetchArray($result);
   }

   /**
    * Fetch row of the next row of a Mysql query
    *
    * @param PDOStatement $result MySQL result handler
    *
    * @return mixed|null result row
    *
    * @deprecated 10.0.0
    */
   function fetch_row($result) {
      Toolbox::deprecated('Use AbstractDatabase::fetchRow');
      return $this->fetchRow($result);
   }

   /**
    * Fetch assoc of the next row of a Mysql query
    *
    * @param PDOStatement $result MySQL result handler
    *
    * @return string[]|null result associative array
    *
    * @deprecated 10.0.0
    */
   function fetch_assoc($result) {
      Toolbox::deprecated('Use AbstractDatabase::fetchAssoc');
      return $this->fetchAssoc($result);
   }

   /**
    * Fetch object of the next row of an SQL query
    *
    * @param PDOStatement $result MySQL result handler
    *
    * @return object|null
    *
    * @deprecated 10.0.0
    */
   function fetch_object($result) {
      Toolbox::deprecated('Use AbstractDatabase::fetchObject');
      return $this->fetchObject($result);
   }

   /**
    * Move current pointer of a Mysql result to the specific row
    *
    * @param PDOStatement $result MySQL result handler
    * @param integer      $num    Row to move current pointer
    *
    * @return boolean
    *
    * @deprecated 10.0.0
    */
   function data_seek($result, $num) {
      throw new \RuntimeException('"data_seek" is not supported by PDO MySQL driver.');
   }

   /**
    * Give ID of the last inserted item by Mysql
    *
    * @param string $table Table name (required for some db engines)
    *
    * @return mixed
    *
    * @deprecated 10.0.0
    */
   public function insert_id($table = '') {
       Toolbox::deprecated('Use AbstractDatabase::insertId');
       return $this->insertId($table);
   }

   public function insertId(string $table = '') {
      return (int)$this->dbh->lastInsertID();
   }

   /**
    * Give number of fields of a Mysql result
    *
    * @param PDOStatement $result MySQL result handler
    *
    * @return int number of fields
    *
    * @deprecated 10.0.0
    */
   function num_fields($result) {
      Toolbox::deprecated('Use PDOStatement::columnCount()');
      return $result->columnCount();
   }

   /**
    * Give name of a field of a Mysql result
    *
    * @param PDOStatement $result MySQL result handler
    * @param integer       $nb     ID of the field
    *
    * @return string name of the field
    *
    * @deprecated 10.0.0
    */
   function field_name($result, $nb) {
      Toolbox::deprecated('Use AbstractDatabase::fieldName()');
      return $this->fieldName($result, $nb);
   }

   public function listTables(string $table = 'glpi_%', array $where = []): DBmysqlIterator {
      $iterator = $this->request([
         'SELECT' => 'TABLE_NAME',
         'FROM'   => 'information_schema.TABLES',
         'WHERE'  => [
            'TABLE_SCHEMA' => $this->dbdefault,
            'TABLE_TYPE'   => 'BASE TABLE',
            'TABLE_NAME'   => ['LIKE', $table]
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
    * @deprecated 10.0.0
    */
   function list_fields($table, $usecache = true) {
       Toolbox::deprecated('Use AbstractDatabase::listFields');
       return $this->listFields($table, $usecache);
   }

   public function listFields(string $table, bool $usecache = true) {
      static $cache = [];
      if (!$this->cache_disabled && $usecache && isset($cache[$table])) {
         return $cache[$table];
      }
      $result = $this->rawQuery("SHOW COLUMNS FROM `$table`");
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
    * @deprecated 10.0.0
    */
   public function affected_rows() {
      throw new \RuntimeException('affected_rows method could not be used... Use PDOStatement::rowCount instead.');
   }

   /**
    * Free result memory
    *
    * @param PDOStatement $result PDO statement
    *
    * @return boolean TRUE on success or FALSE on failure.
    *
    * @deprecated 10.0.0
    */
   public function free_result($result) {
      Toolbox::deprecated('Use AbstractDatabase::freeResult');
      return $this->freeResult($result);
   }

   public function getInfo(): array {
      // No translation, used in sysinfo
      $ret = [];
      $req = $this->requestRaw("SELECT @@sql_mode as mode, @@version AS vers, @@version_comment AS stype");

      if (($data = $req->next())) {
         if ($data['stype']) {
            $ret['Server Software'] = $data['stype'];
         }
         if ($data['vers']) {
            $ret['Server Version'] = $data['vers'];
         } else {
            $ret['Server Version'] = $this->dbh->getAttribute(PDO::ATTR_SERVER_VERSION);
         }
         if ($data['mode']) {
            $ret['Server SQL Mode'] = $data['mode'];
         } else {
            $ret['Server SQL Mode'] = '';
         }
      }
      $ret['Parameters'] = $this->dbuser."@".$this->dbhost."/".$this->dbdefault;
      $ret['Host info']  = $this->dbh->getAttribute(PDO::ATTR_SERVER_INFO);

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
    * @deprecated 10.0.0
    */
   static public function isMySQLStrictMode(&$msg) {
      Toolbox::deprecated();
      global $DB;

      $msg = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE,ONLY_FULL_GROUP_BY,NO_AUTO_CREATE_USER';
      $req = $DB->requestRaw("SELECT @@sql_mode as mode");
      if (($data = $req->next())) {
         return (preg_match("/STRICT_TRANS/", $data['mode'])
                 && preg_match("/NO_ZERO_/", $data['mode'])
                 && preg_match("/ONLY_FULL_GROUP_BY/", $data['mode']));
      }
      return false;
   }

   public function getLock(string $name): bool {
      $name          = addslashes($this->dbdefault.'.'.$name);
      $query         = "SELECT GET_LOCK('$name', 0)";
      $result        = $this->rawQuery($query);
      list($lock_ok) = $this->fetchRow($result);

      return (bool)$lock_ok;
   }

   public function releaseLock(string $name): bool {
      $name          = addslashes($this->dbdefault.'.'.$name);
      $query         = "SELECT RELEASE_LOCK('$name')";
      $result        = $this->rawQuery($query);
      list($lock_ok) = $this->fetchRow($result);

      return (bool)$lock_ok;
   }

   public static function getQuoteNameChar(): string {
      return '`';
   }

   public function getTableSchema(string $table, $structure = null): array {
      if ($structure === null) {
         $structure = $this->rawQuery("SHOW CREATE TABLE `$table`")->fetch();
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

   public function getVersion(): string {
      $req = $this->requestRaw('SELECT version()')->next();
      $raw = $req['version()'];
      return $raw;
   }
}
