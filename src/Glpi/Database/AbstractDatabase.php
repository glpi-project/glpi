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

namespace Glpi\Database;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

use DBmysqlIterator;
use GlpitestSQLError;
use PDO;
use PDOStatement;
use Session;
use Timer;
use Toolbox;

/**
 * @property-read string|string[] $dbhost      Database host.
 * @property-read string          $dbuser      Database user.
 * @property-read string          $dbpassword  Database password.
 * @property-read string          $dbdefault   Database name.
 *
 * @since 10.0.0
 */
abstract class AbstractDatabase
{
    /**
     * Database host.
     * Can be a string or an array of string (round robin).
     *
     * @var string|string[]
     */
    protected $dbhost;

    /**
     * Database user.
     *
     * @var string
     */
    protected $dbuser;

    /**
     * Database password.
     *
     * @var string
     */
    protected $dbpassword;

    /**
     * Database name.
     *
     * @var string
     */
    protected $dbdefault;

    /**
     * Database handler.
     *
     * @var PDO
     */
    protected $dbh;

    /**
     * Slave management.
     *
     * @var boolean
     */
    protected $slave = false;

    /**
     * Is connected to the DB ?
     *
     * @var boolean
     */
    protected $connected = false;

    /**
     * Is cache disabled ?
     *
     * @var boolean
     */
    protected $cache_disabled = false;

    /**
     * Execution time (can be a boolean flag to enable computation or the computation result).
     *
     * @var string
     */
    protected $execution_time;

    /**
     * Enable timer
     *
     * @var boolean
     */
    protected $timer_enabled = false;

    /**
     * Constructor / Connect to the database.
     *
     * @param array   $params Connection parameters
     * @param integer $server host number
     *
     * @return void
     */
    public function __construct(array $params = [], $server = null)
    {
        $requireds = [
            'driver',
            'host',
            'user',
            'pass',
            'dbname'
        ];
        $missing = [];
        foreach ($requireds as $required) {
            if (!array_key_exists($required, $params)) {
                $missing[] = $required;
            }
        }

        if (count($missing)) {
            $msg = sprintf(
                __('Missing parameters: %1$s'),
                implode(', ', $missing)
            );
            throw new \RuntimeException($msg);
        }

        $this->driver = $params['driver'];
        $this->dbhost = $params['host'];
        $this->dbuser = $params['user'];
        $this->dbpassword = $params['pass'];
        $this->dbdefault = $params['dbname'];

        $this->connect($server);
    }

    /**
     * PDO driver to use.
     *
     * @return string
     *
     * @since 10.0.0
     */
    abstract public function getDriver(): string;

    /**
     * Executes a PDO prepared query
     *
     * @param string $query  The query string to execute
     * @param array  $params Query parameters; if any
     *
     * @return PDOStatement
     *
     * @since 10.0.0
     */
    public function execute(string $query, array $params = []): PDOStatement
    {
        $stmt = $this->dbh->prepare($query, [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);

        foreach ($params as &$value) {
            if ('null' == strtolower($value)) {
                $value = null;
            }
        }

        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Connect using current database settings.
     *
     * @param integer $server host number
     *
     * @return void
     */
    abstract public function connect($server = null);

    /**
     * Execute a RAW query.
     * Direct usage is discouraged, use specific method instead when possible.
     *
     * @param string $query  Query to execute
     * @param array  $params Query parameters; if any
     *
     * @global array   $CFG_GLPI
     * @global array   $DEBUG_SQL
     * @global integer $SQL_TOTAL_REQUEST
     *
     * @return PDOStatement|boolean Query result handler
     *
     * @throws GlpitestSQLError
     *
     * @since 10.0.0
     */
    public function rawQuery(string $query, array $params = [])
    {
        global $CFG_GLPI, $DEBUG_SQL, $SQL_TOTAL_REQUEST;

        $is_debug = isset($_SESSION['glpi_use_mode']) && ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE);
        if ($is_debug && $CFG_GLPI["debug_sql"]) {
            $SQL_TOTAL_REQUEST++;
            $DEBUG_SQL["queries"][$SQL_TOTAL_REQUEST] = $query;
        }
        if ($is_debug && $CFG_GLPI["debug_sql"] || $this->timer_enabled === true) {
            $TIMER = new Timer();
            $TIMER->start();
        }

        try {
            $res = $this->execute($query, $params);
            if ($is_debug && $CFG_GLPI["debug_sql"]) {
                $DEBUG_SQL["times"][$SQL_TOTAL_REQUEST] = $TIMER->getTime();
            }
            if ($this->timer_enabled === true) {
                $this->execution_time = $TIMER->getTime(0, true);
            }
            return $res;
        } catch (\Exception $e) {
           // no translation for error logs
            $error = "  *** SQL query error:\n  SQL: ".$query."\n  Error: ".
                   $e->getMessage()."\n";
            $error .= print_r($params, true) . "\n";
            $error .= $e->getTraceAsString();

            Toolbox::logSqlError($error);

            if (($is_debug || isAPI()) && $CFG_GLPI["debug_sql"]) {
                $DEBUG_SQL["errors"][$SQL_TOTAL_REQUEST] = $e->getMessage();
            }
        }
        return false;
    }

    /**
     * Execute a SQL query and die (optionnaly with a message) if it fails.
     *
     * @param string      $query   Query to execute
     * @param string|null $message Explanation of query
     * @param array       $params  Query parameters; if any
     *
     * @return PDOStatement
     *
     * @since 10.0.0
     */
    public function rawQueryOrDie(string $query, $message = null, array $params = []): PDOStatement
    {
        $res = $this->rawQuery($query, $params);
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
     * Prepare a SQL query.
     *
     * @param string $query Query to prepare
     *
     * @return PDOStatement|boolean statement object or FALSE if an error occurred.
     *
     * @throws GlpitestSQLError
     */
    public function prepare(string $query)
    {
        global $CFG_GLPI, $DEBUG_SQL, $SQL_TOTAL_REQUEST;

        try {
            $res = $this->dbh->prepare($query);
            return $res;
        } catch (\Exception $e) {
           // no translation for error logs
            $error = "  *** SQL prepare error:\n  SQL: ".$query."\n  Error: ".
                  $e->getMessage()."\n";
            $error .= $e->getTraceAsString();

            Toolbox::logInFile("sql-errors", $error);
            if (class_exists('GlpitestSQLError')) { // For unit test
                throw new GlpitestSQLError($error, 0, $e);
            }

            if (isset($_SESSION['glpi_use_mode'])
             && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE
             && $CFG_GLPI["debug_sql"]) {
                $SQL_TOTAL_REQUEST++;
                $DEBUG_SQL["errors"][$SQL_TOTAL_REQUEST] = $e->getMessage();
            }
        }
        return false;
    }

    /**
     * Give result from a sql result.
     *
     * @param PDOStatement   $result result handler
     * @param integer        $i      Row offset to give
     * @param integer|string $field  Field to give
     *
     * @return mixed Value of the Row $i and the Field $field of the $result
     *
     * @deprecated 10.0.0
     */
    public function result(PDOStatement $result, int $i, $field)
    {
        $seek_mode = (is_int($field) ? PDO::FETCH_NUM : PDO::FETCH_ASSOC);
        $rows = $result->fetchAll($seek_mode);

        if (false !== $rows && isset($rows[$i]) && isset($rows[$i][$field])) {
            return $rows[$i][$field];
        }
        return null;
    }

    /**
     * Number of rows.
     *
     * @param PDOStatement $result result handler
     *
     * @return integer number of rows
     */
    public function numrows(PDOStatement $result): int
    {
        return $result->rowCount();
    }

    /**
     * Fetch array of the next row of a Mysql query.
     *
     * @param PDOStatement $result result handler
     *
     * @return string[]|null array results
     *
     * @since 10.0.0
     */
    public function fetchArray(PDOStatement $result)
    {
        $result->setFetchMode(PDO::FETCH_NUM);
        return $result->fetch();
    }

    /**
     * Fetch row of the next row of a query.
     *
     * @param PDOStatement $result result handler
     *
     * @return mixed|null result row
     *
     * @since 10.0.0
     */
    public function fetchRow(PDOStatement $result)
    {
        return $result->fetch();
    }

    /**
     * Fetch assoc of the next row of a query.
     *
     * @param PDOStatement $result result handler
     *
     * @return string[]|null result associative array
     *
     * @since 10.0.0
     */
    public function fetchAssoc(PDOStatement $result)
    {
        $result->setFetchMode(PDO::FETCH_ASSOC);
        return $result->fetch();
    }

    /**
     * Fetch object of the next row of an SQL query.
     *
     * @param PDOStatement $result result handler
     *
     * @return object|null
     *
     * @since 10.0.0
     */
    public function fetchObject(PDOStatement $result)
    {
        $result->setFetchMode(PDO::FETCH_OBJ);
        return $result->fetch();
    }

    /**
     * Give ID of the last inserted item by database
     *
     * @param string $table Table name (required for some db engines)
     *
     * @return mixed
     *
     * @since 10.0.0
     */
    abstract public function insertId(string $table = '');

    /**
     * List tables in database
     *
     * @param string $table Table name condition (glpi_% as default to retrieve only glpi tables)
     * @param array  $where Where clause to append
     *
     * @return DBmysqlIterator
     */
    abstract public function listTables(string $table = 'glpi_%', array $where = []): DBmysqlIterator;

    /**
     * List fields of a table
     *
     * @param string  $table    Table name condition
     * @param boolean $usecache If use field list cache (default true)
     *
     * @return mixed list of fields
     */
    abstract public function listFields(string $table, bool $usecache = true);

    /**
     * Free result memory
     *
     * @param PDOStatement $result PDO statement
     *
     * @return boolean TRUE on success or FALSE on failure.
     *
     * @since 10.0.0
     */
    public function freeResult(PDOStatement $result): bool
    {
        return $result->closeCursor();
    }

    /**
     * Returns the numerical value of the error message from previous operation
     *
     * @return int|null error number from the last function, or 0 (zero) if no error occurred.
     */
    public function errno()
    {
        return $this->dbh->errorCode();
    }

    /**
     * Returns the text of the error message from previous operation
     *
     * @return string error text from the last function, or '' (empty string) if no error occurred.
     */
    public function error(): string
    {
        $error = $this->dbh->errorInfo();
        if (isset($error[2])) {
            return $error[2];
        } else {
            return '';
        }
    }

    /**
     * Close SQL connection
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function close(): bool
    {
        if ($this->dbh) {
            $this->dbh = null;
            return true;
        }
        return false;
    }

    /**
     * is a slave database ?
     *
     * @return boolean
     */
    public function isSlave(): bool
    {
        return $this->slave;
    }

    /**
     * Execute all the request in a file
     *
     * @param string $path with file full path
     *
     * @return boolean true if all query are successfull
     */
    public function runFile(string $path): bool
    {
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
                if ($this->rawQuery($formattedQuerytorun)) { //if no success continue to concatenate
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
     *  $DB->request("glpi_states")
     *  $DB->request("glpi_states", ['ID' => 1])
     *
     * Examples =
     *   ["id" => NULL]
     *   ["OR" => ["id" => 1, "NOT" => ["state" => 3]]]
     *   ["AND" => ["id" => 1, ["NOT" => ["state" => [3, 4, 5], "toto" => 2]]]]
     *
     * FIELDS name or array of field names
     * ORDER name or array of field names
     * LIMIT max of row to retrieve
     * START first row to retrieve
     *
     * @param string|string[] $table Table name or array of names
     * @param string|string[] $crit  String or array of fields/values, ex ["id" => 1]), if empty => all rows
     *                               (default '')
     * @param boolean         $debug To log the request (default false)
     *
     * @return DBmysqlIterator
     */
    public function request($table, $crit = "", $debug = false): DBmysqlIterator
    {
        $iterator = new DBmysqlIterator($this);
        $iterator->execute($table, $crit, $debug);
        return $iterator;
    }

    /**
     * Instanciate a Simple DBIterator on RAW SQL (discouraged!)
     *
     * Examples =
     *  $DB->requestRaw("select * from glpi_states")
     *
     * @param string $sql SQL RAW query to execute
     *
     * @return DBmysqlIterator
     *
     * @since 9.4
     */
    public function requestRaw(string $sql): DBmysqlIterator
    {
        $iterator = new DBmysqlIterator($this);
        $iterator->executeRaw($sql);
        return $iterator;
    }


    /**
     * Get information about DB connection for showSystemInformations
     *
     * @since 0.84
     *
     * @return string[] Array of label / value
     */
    abstract public function getInfo(): array;

    /**
     * Get a global DB lock
     *
     * @since 0.84
     *
     * @param string $name lock's name
     *
     * @return boolean
     */
    abstract public function getLock(string $name): bool;

    /**
     * Release a global DB lock
     *
     * @since 0.84
     *
     * @param string $name lock's name
     *
     * @return boolean
     */
    abstract public function releaseLock(string $name): bool;

    /**
     * Check if a table exists
     *
     * @since 9.2
     *
     * @param string $tablename Table name
     *
     * @return boolean
     */
    public function tableExists(string $tablename): bool
    {
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
     * @param Boolean $usecache Use cache; @see Database::listFields(), defaults to true
     *
     * @return boolean
     */
    public function fieldExists(string $table, string $field, bool $usecache = true): bool
    {
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
    public function disableTableCaching()
    {
        $this->cache_disabled = true;
    }

    /**
     * Quote a value for a specified type
     *
     * @param mixed   $value Value to quote
     * @param integer $type  Value type, defaults to PDO::PARAM_STR
     *
     * @return mixed
     *
     * @since 10.0.0
     */
    public function quote($value, int $type = \PDO::PARAM_STR)
    {
        return $this->dbh->quote($value, $type);
    }

    /**
     * Get character used to quote names
     *
     * @return string
     *
     * @since 10.0.0
     */
    abstract public static function getQuoteNameChar(): string;

    /**
     * Quote field name
     *
     * @since 9.3
     *
     * @param \QueryExpression|string $name of field to quote (or table.field)
     *
     * @return string
     */
    public function quoteName($name): string
    {
       //handle verbatim names
        if ($name instanceof \QueryExpression) {
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
            $name = $this->quoteName($names[0]);
            $name .= ' AS ' . $this->quoteName($names[1]);
            return $name;
        } else {
            if (strpos($name, '.')) {
                $n = explode('.', $name, 2);
                $table = $this->quoteName($n[0]);
                $field = ($n[1] === '*') ? $n[1] : $this->quoteName($n[1]);
                return "$table.$field";
            }
            $quote = static::getQuoteNameChar();
            $qname = $quote . str_replace($quote, $quote.$quote, $name) . $quote; // Escape backquotes
            return ($name[0] == $quote ? $name : ($name === '*') ? $name : $qname);
        }
    }

    /**
     * Quote value for insert/update
     *
     * @param mixed $value Value
     *
     * @return mixed
     *
     * @since 10.0.0 Method is more static
     */
    public function quoteValue($value)
    {
        if ($value instanceof \QueryParam || $value instanceof \QueryExpression) {
           //no quote for query parameters nor expressions
            $value = $value->getValue();
        } elseif ($value === null || $value === 'NULL' || $value === 'null') {
            $value = 'NULL';
        } elseif (!$this->isNameQuoted($value)) {
            $value = $this->quote($value);
        }
        return $value;
    }

    /**
     * Builds an insert statement
     *
     * @since 9.3
     *
     * @param \QueryExpression|string $table  Table name
     * @param array                   $params Query parameters ([field name => field value)
     *
     * @return string
     */
    public function buildInsert($table, array &$params): string
    {
        $query = "INSERT INTO " . $this->quoteName($table) . " (";

        $fields = [];
        $keys   = [];
        foreach ($params as $key => $value) {
            $fields[] = $this->quoteName($key);
            if ($value instanceof \QueryExpression) {
                $keys[] = $value->getValue();
                unset($params[$key]);
            } else {
                $keys[]   = ':' . trim($key, '`');
            }
        }

        $query .= implode(', ', $fields);
        $query .= ") VALUES (";
        $query .= implode(", ", $keys);
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
     * @return PDOStatement|boolean
     */
    public function insert(string $table, array $params)
    {
        $result = $this->rawQuery(
            $this->buildInsert($table, $params),
            $params
        );
        return $result;
    }

    /**
     * Insert a row in the database and die
     * (optionnaly with a message) if it fails
     *
     * @since 9.3
     *
     * @param string      $table   Table name
     * @param array       $params  Query parameters ([field name => field value)
     * @param string|null $message Explanation of query
     *
     * @return PDOStatement
     */
    public function insertOrDie(string $table, array $params, $message = null): PDOStatement
    {
        $insert = $this->buildInsert($table, $params);
        $res = $this->rawQuery($insert, $params);
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
     * @param \QueryExpression|string $table   Table name
     * @param array                   $params  Query parameters ([field name => field value)
     * @param array                   $clauses Clauses to use. If not 'WHERE' key specified,
     *                                         will b the WHERE clause (@see DBmysqlIterator capabilities)
     * @param array                   $joins   JOINS criteria array
     *
     * @return string
     */
    public function buildUpdate($table, array &$params, array $clauses, array $joins = []): string
    {
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

        $query  = "UPDATE ". $this->quoteName($table);

        $it = new DBmysqlIterator($this);
        //JOINS
        $query .= $it->analyzeJoins($joins);

        $query .= ' SET ';
        foreach ($params as $field => $value) {
            $subq = $this->quoteName($field) . ' = ?, ';
            if ($value instanceof \QueryExpression) {
                $subq = str_replace('?', $value->getValue(), $subq);
                unset($params[$field]);
            }
            $query .= $subq;
        }
        $query = rtrim($query, ', ');

        foreach ($params as $key => $param) {
            if ($param instanceof \QueryExpression || $param instanceof \QueryParam) {
                unset($params[$key]);
            }
        }

        $query .= " WHERE " . $it->analyseCrit($clauses['WHERE']);

        $params = array_merge(array_values($params), $it->getParameters());

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
     * @return PDOStatement|boolean
     */
    public function update(string $table, array $params, array $where, array $joins = [])
    {
        $query = $this->buildUpdate($table, $params, $where, $joins);
        $result = $this->rawQuery($query, $params);
        return $result;
    }

    /**
     * Update a row in the database or die
     * (optionnaly with a message) if it fails
     *
     * @since 9.3
     *
     * @param string      $table   Table name
     * @param array       $params  Query parameters ([:field name => field value)
     * @param array       $where   WHERE clause
     * @param string|null $message Explanation of query
     * @param array       $joins   JOINS criteria array
     *
     * @return PDOStatement Query result handler
     */
    public function updateOrDie(
        string $table,
        array $params,
        array $where,
        $message = null,
        array $joins = []
    ): PDOStatement {
        $update = $this->buildUpdate($table, $params, $where, $joins);
        $res = $this->rawQuery($update, $params);
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
     * @return PDOStatement|boolean Query result handler
     */
    public function updateOrInsert(string $table, array $params, array $where, bool $onlyone = true)
    {
        $req = $this->request($table, $where, $params);
        $data = array_merge($where, $params);
        if ($req->count() == 0) {
            return $this->insertOrDie($table, $data, 'Unable to create new element or update existing one');
        } elseif ($req->count() == 1 || !$onlyone) {
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
     * @param \QueryExpression|string $table  Table name
     * @param array                   $params Query parameters ([:field name => field value)
     * @param array                   $where  WHERE clause (@see DBmysqlIterator capabilities)
     * @param array                   $joins  JOINS criteria array
     *
     * @return string
     */
    public function buildDelete($table, array &$params, array $where, array $joins = []): string
    {

        if (!count($where)) {
            throw new \RuntimeException('Cannot run an DELETE query without WHERE clause!');
        }

        $query  = "DELETE ". $this->quoteName($table) . " FROM ". $this->quoteName($table);

        $it = new DBmysqlIterator($this);
        $query .= $it->analyzeJoins($joins);
        $query .= " WHERE " . $it->analyseCrit($where);
        $params = array_merge($params, $it->getParameters());

        return $query;
    }

    /**
     * Delete rows in the database
     *
     * @since 9.3
     *
     * @param string $table Table name
     * @param array  $where WHERE clause
     * @param array  $joins JOINS criteria array
     *
     * @return PDOStatement|boolean Query result handler
     */
    public function delete(string $table, array $where, array $joins = [])
    {
        $params = [];
        $query = $this->buildDelete($table, $params, $where, $joins);
        $result = $this->rawQuery($query, $params);
        return $result;
    }

    /**
     * Delete a row in the database and die
     * (optionnaly with a message) if it fails
     *
     * @since 9.3
     *
     * @param string      $table   Table name
     * @param array       $where   WHERE clause
     * @param string|null $message Explanation of query
     * @param array       $joins   JOINS criteria array
     *
     * @return PDOStatement Query result handler
     */
    public function deleteOrDie(string $table, array $where, $message = null, array $joins = []): PDOStatement
    {
        $params = [];
        $update = $this->buildDelete($table, $params, $where, $joins);
        $res = $this->rawQuery($update, $params);
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
     * Truncate table in the database
     *
     * @since 10.0.0
     *
     * @param string $table Table name
     *
     * @return PDOStatement|boolean Query result handler
     */
    public function truncate(string $table)
    {
        $sql = "TRUNCATE " . $this->quoteName($table);
        $result = $this->rawQuery($sql);
        return $result;
    }

    /**
     * Truncate table in the database and die
     * (optionnaly with a message) if it fails
     *
     * @since 10.0.0
     *
     * @param string      $table   Table name
     * @param string|null $message Explanation of query
     *
     * @return PDOStatement Query result handler
     */
    public function truncateOrDie(string $table, $message = null): PDOStatement
    {
        $sql = "TRUNCATE " . $this->quoteName($table);
        $res = $this->rawQuery($sql);
        if (!$res) {
           //TRANS: %1$s is the description, %2$s is the query, %3$s is the error message
            $message = sprintf(
                __('%1$s - Error during the database query: %2$s - Error is %3$s'),
                $message,
                $sql,
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
     * @param string      $table     Table name
     * @param string|null $structure Raw table structure
     *
     * @return array
     */
    abstract public function getTableSchema(string $table, $structure = null): array;

    /**
     * Get database raw version
     *
     * @return string
     */
    abstract public function getVersion(): string;

   /**
    * Starts a PDO transaction
    *
    * @return boolean
    *
    * @since 9.4
    */
    public function beginTransaction(): bool
    {
        return $this->dbh->beginTransaction();
    }

    /**
     * Commits a PDO transaction
     *
     * @return boolean
     *
     * @since 9.4
     */
    public function commit(): bool
    {
        return $this->dbh->commit();
    }

    /**
     * Roolbacks a PDO transaction
     *
     * @return boolean
     *
     * @since 9.4
     */
    public function rollBack(): bool
    {
        if ($this->inTransaction()) {
            return $this->dbh->rollBack();
        }

        return false;
    }

    /**
     * Is into a PDO transaction?
     *
     * @return boolean
     *
     * @since 10.0.0
     */
    public function inTransaction(): bool
    {
        return $this->dbh->inTransaction();
    }

    /**
     * Get database connection string
     *
     * @param string $server Which server to use
     *
     * @return null|string
     *
     * @since 10.0.0
     */
    public function getDsn($server)
    {
        if (empty($this->dbhost)) {
            return null;
        }
        if (is_array($this->dbhost)) {
           // Round robin choice
            $i    = (isset($server) ? $server : mt_rand(0, count($this->dbhost)-1));
            $host = $this->dbhost[$i];
        } else {
            $host = $this->dbhost;
        }

        $hostport = explode(":", $host);
        $driver   = $this->getDriver();
        if (count($hostport) < 2) {
           // Host
            $dsn = "$driver:host=$host";
        } elseif (intval($hostport[1])>0) {
           // Host:port
            $dsn = "$driver:host={$hostport[0]}:{$hostport[1]}";
        } else {
           // :Socket
            $dsn = "$driver:unix_socket={$hostport[1]}";
        }

        return $dsn;
    }

    /**
     * Is value quoted as database field/expression?
     *
     * @param string|\QueryExpression $value Value to check
     *
     * @return boolean
     *
     * @since 10.0.0
     */
    public static function isNameQuoted($value): bool
    {
        $quote = static::getQuoteNameChar();
        return is_string($value) && trim($value, $quote) != $value;
    }

    /**
     * Retrieve the SQL statement merged with parameters.
     *
     * @param string $sql        SQL statement
     * @param array  $parameters Query parameters
     *
     * @since 10.0.0
     *
     * @return string
     */
    public function mergeStatementWithParams(string $sql, array $parameters = []): string
    {
        foreach ($parameters as $param) {
            $pos = strpos($sql, '?');
            if ($pos !== false) {
                $sql = substr_replace($sql, $this->quote($param), $pos, strlen('?'));
            }
        }

        return $sql;
    }

    /**
     * Global getter
     *
     * @param string $name Poperty name
     *
     * @return mixed
     */
    public function __get(string $name)
    {
        $knowns = [
            'dbhost',
            'dbuser',
            'dbdefault'
        ];
        if (in_array($name, $knowns)) {
            return $this->$name;
        }
    }

    /**
     * Give name of a field/column of a Mysql result.
     *
     * @param PDOStatement $result Resultset
     * @param integer      $i      Index of the field/column
     *
     * @return string  Name of the field
     */
    public function fieldName(PDOStatement $result, int $i): string
    {
        $col = $result->getColumnMeta($i);
        return $col['name'];
    }

    /**
     * Enable timer
     *
     * @return AbstractDatabase
     */
    public function enableTimer(): AbstractDatabase
    {
        $this->timer_enabled = true;
        return $this;
    }

    /**
     * Disable timer
     *
     * @return AbstractDatabase
     */
    public function disableTimer(): AbstractDatabase
    {
        $this->timer_enabled = false;
        return $this;
    }


    /**
     * Get execution time
     *
     * @return string
     */
    public function getExecutionTime(): string
    {
        return $this->execution_time;
    }

    /**
     * Is connected to the database
     *
     * @return boolean
     */
    public function isConnected(): bool
    {
        return $this->connected;
    }

    /**
     * Check if timezones are active.
     *
     * Under MySql, a command must be run to initilize data,
     * and some privileges may be needed to read informations.
     *
     * @param string $msg Variable that would contain the reason of data unavailability.
     *
     * @return boolean
     */
    public function areTimezonesAvailable(string &$msg = '') :bool
    {
        return true;
    }

    /**
     * Set timezone on both php and database
     *
     * @param sting $timezone TimeZone to set
     *
     * @return AbstractDatabase
     */
    abstract public function setTimezone($timezone) :AbstractDatabase;

    /**
     * Get available timezones
     *
     * @return array
     */
    abstract public function getTimezones() :array;

    /**
     * Count columns that has not been migrated to timestamp type
     *
     * @return integer
     */
    public function notTzMigrated() :int
    {
        return 0;
    }
}
