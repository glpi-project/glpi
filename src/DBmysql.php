<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

use Glpi\Application\ErrorHandler;
use Glpi\System\Requirement\DbTimezones;
use Glpi\Toolbox\Sanitizer;

/**
 *  Database class for Mysql
 **/
class DBmysql
{
   //! Database Host - string or Array of string (round robin)
    public $dbhost             = "";
   //! Database User
    public $dbuser             = "";
   //! Database Password
    public $dbpassword         = "";
   //! Default Database
    public $dbdefault          = "";

    /**
     * The database handler
     * @var mysqli
     */
    protected $dbh;

   //! Database Error
    public $error              = 0;

   // Slave management
    public $slave              = false;
    private $in_transaction;

    /**
     * Defines if connection must use SSL.
     *
     * @var boolean
     */
    public $dbssl              = false;

    /**
     * The path name to the key file (used in case of SSL connection).
     *
     * @see mysqli::ssl_set()
     * @var string|null
     */
    public $dbsslkey           = null;

    /**
     * The path name to the certificate file (used in case of SSL connection).
     *
     * @see mysqli::ssl_set()
     * @var string|null
     */
    public $dbsslcert          = null;

    /**
     * The path name to the certificate authority file (used in case of SSL connection).
     *
     * @see mysqli::ssl_set()
     * @var string|null
     */
    public $dbsslca            = null;

    /**
     * The pathname to a directory that contains trusted SSL CA certificates in PEM format
     * (used in case of SSL connection).
     *
     * @see mysqli::ssl_set()
     * @var string|null
     */
    public $dbsslcapath        = null;

    /**
     * A list of allowable ciphers to use for SSL encryption (used in case of SSL connection).
     *
     * @see mysqli::ssl_set()
     * @var string|null
     */
    public $dbsslcacipher      = null;

    /**
     * Determine if timezones should be used for timestamp fields.
     * Defaults to false to keep backward compatibility with old DB.
     *
     * @var bool
     */
    public $use_timezones = false;

    /**
     * Determine if warnings related to MySQL deprecations should be logged too.
     * Defaults to false as this option should only on development/test environment.
     *
     * @var bool
     */
    public $log_deprecation_warnings = false;

    /**
     * Determine if utf8mb4 should be used for DB connection and tables altering operations.
     * Defaults to false to keep backward compatibility with old DB.
     *
     * @var bool
     */
    public $use_utf8mb4 = false;

    /**
     * Determine if MyISAM engine usage should be allowed for tables creation/altering operations.
     * Defaults to true to keep backward compatibility with old DB.
     *
     * @var bool
     */
    public $allow_myisam = true;

    /**
     * Determine if datetime fields usage should be allowed for tables creation/altering operations.
     * Defaults to true to keep backward compatibility with old DB.
     *
     * @var bool
     */
    public $allow_datetime = true;

    /**
     * Determine if signed integers in primary/foreign keys usage should be allowed for tables creation/altering operations.
     * Defaults to true to keep backward compatibility with old DB.
     *
     * @var bool
     */
    public $allow_signed_keys = true;


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
     * Cached list fo tables.
     *
     * @var array
     * @see self::tableExists()
     */
    private $table_cache = [];

    /**
     * Cached list of fields.
     *
     * @var array
     * @see self::listFields()
     */
    private $field_cache = [];

    /**
     * Last query warnings.
     *
     * @var array
     */
    private $last_query_warnings = [];

    /**
     * Constructor / Connect to the MySQL Database
     *
     * @param integer $choice host number (default NULL)
     *
     * @return void
     */
    public function __construct($choice = null)
    {
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
    public function connect($choice = null)
    {
        $this->connected = false;

       // Do not trigger errors nor throw exceptions at PHP level
       // as we already extract error and log while fetching result.
        mysqli_report(MYSQLI_REPORT_OFF);

        $this->dbh = @new mysqli();
        if ($this->dbssl) {
            $this->dbh->ssl_set(
                $this->dbsslkey,
                $this->dbsslcert,
                $this->dbsslca,
                $this->dbsslcapath,
                $this->dbsslcacipher
            );
        }

        if (is_array($this->dbhost)) {
           // Round robin choice
            $i    = (isset($choice) ? $choice : mt_rand(0, count($this->dbhost) - 1));
            $host = $this->dbhost[$i];
        } else {
            $host = $this->dbhost;
        }

        $hostport = explode(":", $host);
        if (count($hostport) < 2) {
           // Host
            $this->dbh->real_connect($host, $this->dbuser, rawurldecode($this->dbpassword), $this->dbdefault);
        } else if (intval($hostport[1]) > 0) {
           // Host:port
            $this->dbh->real_connect($hostport[0], $this->dbuser, rawurldecode($this->dbpassword), $this->dbdefault, $hostport[1]);
        } else {
            // :Socket
            $this->dbh->real_connect($hostport[0], $this->dbuser, rawurldecode($this->dbpassword), $this->dbdefault, ini_get('mysqli.default_port'), $hostport[1]);
        }

        if ($this->dbh->connect_error) {
            $this->connected = false;
            $this->error     = 1;
        } else if (!defined('MYSQLI_OPT_INT_AND_FLOAT_NATIVE')) {
            $this->connected = false;
            $this->error     = 2;
        } else {
            $this->setConnectionCharset();

           // force mysqlnd to return int and float types correctly (not as strings)
            $this->dbh->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, true);

            $this->dbh->query("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''))");

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
    public function guessTimezone()
    {
        if ($this->use_timezones) {
            if (isset($_SESSION['glpi_tz'])) {
                $zone = $_SESSION['glpi_tz'];
            } else {
                $conf_tz = ['value' => null];
                if (
                    $this->tableExists(Config::getTable())
                    && $this->fieldExists(Config::getTable(), 'value')
                ) {
                    $conf_tz = $this->request([
                        'SELECT' => 'value',
                        'FROM'   => Config::getTable(),
                        'WHERE'  => [
                            'context'   => 'core',
                            'name'      => 'timezone'
                        ]
                    ])->current();
                }
                $zone = !empty($conf_tz['value']) ? $conf_tz['value'] : date_default_timezone_get();
            }
        } else {
            $zone = date_default_timezone_get();
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
    public function escape($string)
    {
        if (!is_string($string)) {
            return $string;
        }
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
     */
    public function query($query)
    {
        global $CFG_GLPI, $DEBUG_SQL, $SQL_TOTAL_REQUEST;

        //FIXME Remove use of $DEBUG_SQL and $SQL_TOTAL_REQUEST

        $debug_data = [
            'query' => $query,
            'time' => 0,
            'rows' => 0,
            'errors' => '',
            'warnings' => '',
        ];

        $is_debug = isset($_SESSION['glpi_use_mode']) && ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE);
        if ($is_debug && $CFG_GLPI["debug_sql"]) {
            $SQL_TOTAL_REQUEST++;
            $DEBUG_SQL["queries"][$SQL_TOTAL_REQUEST] = $query;
        }

        $TIMER = new Timer();
        $TIMER->start();

        $this->checkForDeprecatedTableOptions($query);

        $res = $this->dbh->query($query);
        if (!$res) {
           // no translation for error logs
            $error = "  *** MySQL query error:\n  SQL: " . $query . "\n  Error: " .
                   $this->dbh->error . "\n";
            $error .= Toolbox::backtrace(false, 'DBmysql->query()', ['Toolbox::backtrace()']);

            Toolbox::logSqlError($error);

            ErrorHandler::getInstance()->handleSqlError($this->dbh->errno, $this->dbh->error, $query);

            if (($is_debug || isAPI()) && $CFG_GLPI["debug_sql"]) {
                $DEBUG_SQL["errors"][$SQL_TOTAL_REQUEST] = $this->error();
                $debug_data['errors'] = $this->error();
            }
        }

        if ($is_debug && $CFG_GLPI["debug_sql"]) {
            $TIME = $TIMER->getTime();
            $debug_data['time'] = (int) ($TIME * 1000);
            $debug_data['rows'] = $this->affectedRows();
            $DEBUG_SQL["times"][$SQL_TOTAL_REQUEST] = $TIME;
            $DEBUG_SQL['rows'][$SQL_TOTAL_REQUEST] = $this->affectedRows();
        }

        $this->last_query_warnings = $this->fetchQueryWarnings();
        $DEBUG_SQL['warnings'][$SQL_TOTAL_REQUEST] = $this->last_query_warnings;

        $warnings_string = implode(
            "\n",
            array_map(
                static function ($warning) {
                    return sprintf('%s: %s', $warning['Code'], $warning['Message']);
                },
                $this->last_query_warnings
            )
        );
        $debug_data['warnings'] = $warnings_string;

        // Output warnings in SQL log
        if (!empty($this->last_query_warnings)) {
            $message = sprintf(
                "  *** MySQL query warnings:\n  SQL: %s\n  Warnings: \n%s\n",
                $query,
                $warnings_string
            );
            $message .= Toolbox::backtrace(false, 'DBmysql->query()', ['Toolbox::backtrace()']);
            Toolbox::logSqlWarning($message);

            ErrorHandler::getInstance()->handleSqlWarnings($this->last_query_warnings, $query);
        }

        \Glpi\Debug\Profile::getCurrent()->addSQLQueryData(
            $debug_data['query'],
            $debug_data['time'],
            $debug_data['rows'],
            $debug_data['errors'],
            $debug_data['warnings']
        );
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
    public function queryOrDie($query, $message = '')
    {
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
     */
    public function prepare($query)
    {
        global $CFG_GLPI, $DEBUG_SQL, $SQL_TOTAL_REQUEST;

        $res = $this->dbh->prepare($query);
        if (!$res) {
           // no translation for error logs
            $error = "  *** MySQL prepare error:\n  SQL: " . $query . "\n  Error: " .
                   $this->dbh->error . "\n";
            $error .= Toolbox::backtrace(false, 'DBmysql->prepare()', ['Toolbox::backtrace()']);

            Toolbox::logSqlError($error);

            ErrorHandler::getInstance()->handleSqlError($this->dbh->errno, $this->dbh->error, $query);

            if (
                isset($_SESSION['glpi_use_mode'])
                && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE
                && $CFG_GLPI["debug_sql"]
            ) {
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
    public function result($result, $i, $field)
    {
        if (
            $result && ($result->data_seek($i))
            && ($data = $result->fetch_array())
            && isset($data[$field])
        ) {
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
    public function numrows($result)
    {
        return $result->num_rows;
    }

    /**
     * Fetch array of the next row of a Mysql query
     * Please prefer fetchRow or fetchAssoc
     *
     * @param mysqli_result $result MySQL result handler
     *
     * @return string[]|null array results
     */
    public function fetchArray($result)
    {
        return $result->fetch_array();
    }

    /**
     * Fetch row of the next row of a Mysql query
     *
     * @param mysqli_result $result MySQL result handler
     *
     * @return mixed|null result row
     */
    public function fetchRow($result)
    {
        return $result->fetch_row();
    }

    /**
     * Fetch assoc of the next row of a Mysql query
     *
     * @param mysqli_result $result MySQL result handler
     *
     * @return string[]|null result associative array
     */
    public function fetchAssoc($result)
    {
        return $result->fetch_assoc();
    }

    /**
     * Fetch object of the next row of an SQL query
     *
     * @param mysqli_result $result MySQL result handler
     *
     * @return object|null
     */
    public function fetchObject($result)
    {
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
    public function dataSeek($result, $num)
    {
        return $result->data_seek($num);
    }

    /**
     * Give ID of the last inserted item by Mysql
     *
     * @return mixed
     */
    public function insertId()
    {
        $insert_id = $this->dbh->insert_id;

        if ($insert_id === 0) {
            // See https://www.php.net/manual/en/mysqli.insert-id.php
            // `$this->dbh->insert_id` will return 0 value if `INSERT` statement did not change the `AUTO_INCREMENT` value.
            // We have to retrieve it manually via `LAST_INSERT_ID()`.
            $insert_id = $this->dbh->query('SELECT LAST_INSERT_ID()')->fetch_row()[0];
        }
        return $insert_id;
    }

    /**
     * Give number of fields of a Mysql result
     *
     * @param mysqli_result $result MySQL result handler
     *
     * @return int number of fields
     */
    public function numFields($result)
    {
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
    public function fieldName($result, $nb)
    {
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
    public function listTables($table = 'glpi\_%', array $where = [])
    {
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
     * @param bool $exclude_plugins
     *
     * @return DBmysqlIterator
     */
    public function getMyIsamTables(bool $exclude_plugins = false): DBmysqlIterator
    {
        $criteria = [
            'engine' => 'MyIsam',
        ];
        if ($exclude_plugins) {
            $criteria[] = ['NOT' => ['information_schema.tables.table_name' => ['LIKE', 'glpi\_plugin\_%']]];
        }

        $iterator = $this->listTables('glpi\_%', $criteria);

        return $iterator;
    }

    /**
     * Returns tables not using "utf8mb4_unicode_ci" collation.
     *
     * @param bool $exclude_plugins
     *
     * @return DBmysqlIterator
     */
    public function getNonUtf8mb4Tables(bool $exclude_plugins = false): DBmysqlIterator
    {

       // Find tables that does not use utf8mb4 collation
        $tables_query = [
            'SELECT'     => ['information_schema.tables.table_name as TABLE_NAME'],
            'DISTINCT'   => true,
            'FROM'       => 'information_schema.tables',
            'WHERE'     => [
                'information_schema.tables.table_schema' => $this->dbdefault,
                'information_schema.tables.table_name'   => ['LIKE', 'glpi\_%'],
                'information_schema.tables.table_type'    => 'BASE TABLE',
                ['NOT' => ['information_schema.tables.table_collation' => 'utf8mb4_unicode_ci']],
            ],
        ];

       // Find columns that does not use utf8mb4 collation
        $columns_query = [
            'SELECT'     => ['information_schema.columns.table_name as TABLE_NAME'],
            'DISTINCT'   => true,
            'FROM'       => 'information_schema.columns',
            'INNER JOIN' => [
                'information_schema.tables' => [
                    'FKEY' => [
                        'information_schema.tables'  => 'table_name',
                        'information_schema.columns' => 'table_name',
                        [
                            'AND' => [
                                'information_schema.tables.table_schema' => new QueryExpression(
                                    $this->quoteName('information_schema.columns.table_schema')
                                ),
                            ]
                        ],
                    ]
                ]
            ],
            'WHERE'     => [
                'information_schema.tables.table_schema' => $this->dbdefault,
                'information_schema.tables.table_name'   => ['LIKE', 'glpi\_%'],
                'information_schema.tables.table_type'    => 'BASE TABLE',
                ['NOT' => ['information_schema.columns.collation_name' => null]],
                ['NOT' => ['information_schema.columns.collation_name' => 'utf8mb4_unicode_ci']]
            ],
        ];

        if ($exclude_plugins) {
            $tables_query['WHERE'][] = ['NOT' => ['information_schema.tables.table_name' => ['LIKE', 'glpi\_plugin\_%']]];
            $columns_query['WHERE'][] = ['NOT' => ['information_schema.tables.table_name' => ['LIKE', 'glpi\_plugin\_%']]];
        }

        $iterator = $this->request([
            'SELECT'   => ['TABLE_NAME'],
            'DISTINCT' => true,
            'FROM'     => new QueryUnion([$tables_query, $columns_query], true),
            'ORDER'    => ['TABLE_NAME']
        ]);

        return $iterator;
    }

    /**
     * Returns tables not compatible with timezone usage, i.e. having "datetime" columns.
     *
     * @param bool $exclude_plugins
     *
     * @return DBmysqlIterator
     *
     * @since 10.0.0
     */
    public function getTzIncompatibleTables(bool $exclude_plugins = false): DBmysqlIterator
    {

        $query = [
            'SELECT'       => ['information_schema.columns.table_name as TABLE_NAME'],
            'DISTINCT'     => true,
            'FROM'         => 'information_schema.columns',
            'INNER JOIN'   => [
                'information_schema.tables' => [
                    'FKEY' => [
                        'information_schema.tables'  => 'table_name',
                        'information_schema.columns' => 'table_name',
                        [
                            'AND' => [
                                'information_schema.tables.table_schema' => new QueryExpression(
                                    $this->quoteName('information_schema.columns.table_schema')
                                ),
                            ]
                        ],
                    ]
                ]
            ],
            'WHERE'       => [
                'information_schema.tables.table_schema' => $this->dbdefault,
                'information_schema.tables.table_name'   => ['LIKE', 'glpi\_%'],
                'information_schema.tables.table_type'   => 'BASE TABLE',
                'information_schema.columns.data_type'   => 'datetime',
            ],
            'ORDER'       => ['TABLE_NAME']
        ];

        if ($exclude_plugins) {
            $query['WHERE'][] = ['NOT' => ['information_schema.tables.table_name' => ['LIKE', 'glpi\_plugin\_%']]];
        }

        $iterator = $this->request($query);

        return $iterator;
    }


    /**
     * Returns columns that uses signed integers for primary/foreign keys.
     *
     * @param bool $exclude_plugins
     *
     * @return DBmysqlIterator
     *
     * @since 9.5.7
     */
    public function getSignedKeysColumns(bool $exclude_plugins = false)
    {
        $query = [
            'SELECT'       => [
                'information_schema.columns.table_name as TABLE_NAME',
                'information_schema.columns.column_name as COLUMN_NAME',
                'information_schema.columns.data_type as DATA_TYPE',
                'information_schema.columns.column_default as COLUMN_DEFAULT',
                'information_schema.columns.is_nullable as IS_NULLABLE',
                'information_schema.columns.extra as EXTRA',
            ],
            'FROM'         => 'information_schema.columns',
            'INNER JOIN'   => [
                'information_schema.tables' => [
                    'FKEY' => [
                        'information_schema.tables'  => 'table_name',
                        'information_schema.columns' => 'table_name',
                        [
                            'AND' => [
                                'information_schema.tables.table_schema' => new QueryExpression(
                                    $this->quoteName('information_schema.columns.table_schema')
                                ),
                            ]
                        ],
                    ]
                ]
            ],
            'WHERE'       => [
                'information_schema.tables.table_schema'  => $this->dbdefault,
                'information_schema.tables.table_name'    => ['LIKE', 'glpi\_%'],
                'information_schema.tables.table_type'    => 'BASE TABLE',
                [
                    'OR' => [
                        ['information_schema.columns.column_name' => 'id'],
                        ['information_schema.columns.column_name' => ['LIKE', '%\_id']],
                        ['information_schema.columns.column_name' => ['LIKE', '%\_id\_%']],
                    ],
                ],
                'information_schema.columns.data_type' => ['tinyint', 'smallint', 'mediumint', 'int', 'bigint'],
                ['NOT' => ['information_schema.columns.column_type' => ['LIKE', '%unsigned%']]],
            ],
            'ORDER'       => ['TABLE_NAME']
        ];

        if ($exclude_plugins) {
            $query['WHERE'][] = ['NOT' => ['information_schema.tables.table_name' => ['LIKE', 'glpi\_plugin\_%']]];
        }

        $iterator = $this->request($query);

        return $iterator;
    }

    /**
     * Returns foreign keys constraints.
     *
     * @return DBmysqlIterator
     *
     * @since 9.5.7
     */
    public function getForeignKeysContraints()
    {
        $query = [
            'SELECT' => [
                'table_schema as TABLE_SCHEMA',
                'table_name as TABLE_NAME',
                'column_name as COLUMN_NAME',
                'constraint_name as CONSTRAINT_NAME',
                'referenced_table_name as REFERENCED_TABLE_NAME',
                'referenced_column_name as REFERENCED_COLUMN_NAME',
                'ordinal_position as ORDINAL_POSITION',
            ],
            'FROM'   => 'information_schema.key_column_usage',
            'WHERE'  => [
                'referenced_table_schema' => $this->dbdefault,
                'referenced_table_name'   => ['LIKE', 'glpi\_%'],
            ],
            'ORDER'  => ['TABLE_NAME']
        ];

        $iterator = $this->request($query);

        return $iterator;
    }

    /**
     * List fields of a table
     *
     * @param string  $table    Table name condition
     * @param boolean $usecache If use field list cache (default true)
     *
     * @return mixed list of fields
     */
    public function listFields($table, $usecache = true)
    {

        if (!$this->cache_disabled && $usecache && isset($this->field_cache[$table])) {
            return $this->field_cache[$table];
        }
        $result = $this->query("SHOW COLUMNS FROM `$table`");
        if ($result) {
            if ($this->numrows($result) > 0) {
                $this->field_cache[$table] = [];
                while ($data = $this->fetchAssoc($result)) {
                    $this->field_cache[$table][$data["Field"]] = $data;
                }
                return $this->field_cache[$table];
            }
            return [];
        }
        return false;
    }

    /**
     * Get field of a table
     *
     * @param string  $table
     * @param string  $field
     * @param boolean $usecache
     *
     * @return array|null Field characteristics
     */
    public function getField(string $table, string $field, $usecache = true): ?array
    {

        $fields = $this->listFields($table, $usecache);
        return $fields[$field] ?? null;
    }

    /**
     * Get number of affected rows in previous MySQL operation
     *
     * @return int number of affected rows on success, and -1 if the last query failed.
     */
    public function affectedRows()
    {
        return $this->dbh->affected_rows;
    }

    /**
     * Free result memory
     *
     * @param mysqli_result $result MySQL result handler
     *
     * @return boolean
     */
    public function freeResult($result)
    {
        return $result->free();
    }

    /**
     * Returns the numerical value of the error message from previous MySQL operation
     *
     * @return int error number from the last MySQL function, or 0 (zero) if no error occurred.
     */
    public function errno()
    {
        return $this->dbh->errno;
    }

    /**
     * Returns the text of the error message from previous MySQL operation
     *
     * @return string error text from the last MySQL function, or '' (empty string) if no error occurred.
     */
    public function error()
    {
        return $this->dbh->error;
    }

    /**
     * Close MySQL connection
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function close()
    {
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
    public function isSlave()
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
    public function runFile($path)
    {
        $script = fopen($path, 'r');
        if (!$script) {
            return false;
        }
        $sql_query = @fread(
            $script,
            @filesize($path)
        ) . "\n";
        $sql_query = html_entity_decode($sql_query, ENT_COMPAT, 'UTF-8');

        $sql_query = $this->removeSqlRemarks($sql_query);
        $queries = preg_split('/;\s*$/m', $sql_query);

        foreach ($queries as $query) {
            $query = trim($query);
            if ($query != '') {
                $query = htmlentities($query, ENT_COMPAT, 'UTF-8');
                if (!$this->query($query)) {
                    return false;
                }
                if (!isCommandLine()) {
                  // Flush will prevent proxy to timeout as it will receive data.
                  // Flush requires a content to be sent, so we sent spaces as multiple spaces
                  // will be shown as a single one on browser.
                    echo ' ';
                    Html::glpi_flush();
                }
            }
        }

        return true;
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
    public function request($tableorsql, $crit = "", $debug = false)
    {
        $iterator = new DBmysqlIterator($this);
        $iterator->execute($tableorsql, $crit, $debug);
        return $iterator;
    }


    /**
     * Get information about DB connection for showSystemInformation
     *
     * @since 0.84
     *
     * @return string[] Array of label / value
     */
    public function getInfo()
    {
       // No translation, used in sysinfo
        $ret = [];
        $req = $this->request("SELECT @@sql_mode as mode, @@version AS vers, @@version_comment AS stype");

        if (($data = $req->current())) {
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
        $ret['Parameters'] = $this->dbuser . "@" . $this->dbhost . "/" . $this->dbdefault;
        $ret['Host info']  = $this->dbh->host_info;

        return $ret;
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
    public function getLock($name)
    {
        $name          = addslashes($this->dbdefault . '.' . $name);
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
    public function releaseLock($name)
    {
        $name          = addslashes($this->dbdefault . '.' . $name);
        $query         = "SELECT RELEASE_LOCK('$name')";
        $result        = $this->query($query);
        list($lock_ok) = $this->fetchRow($result);

        return $lock_ok;
    }


    /**
     * Check if a table exists
     *
     * @since 9.2
     * @since 9.5 Added $usecache parameter.
     *
     * @param string  $tablename Table name
     * @param boolean $usecache  If use table list cache
     *
     * @return boolean
     **/
    public function tableExists($tablename, $usecache = true)
    {

        if (!$this->cache_disabled && $usecache && in_array($tablename, $this->table_cache)) {
            return true;
        }

       // Retrieve all tables if cache is empty but enabled, in order to fill cache
       // with all known tables
        $retrieve_all = !$this->cache_disabled && empty($this->table_cache);

        $result = $this->listTables($retrieve_all ? 'glpi\_%' : $tablename);
        $found_tables = [];
        foreach ($result as $data) {
            $found_tables[] = $data['TABLE_NAME'];
        }

        if (!$this->cache_disabled) {
            $this->table_cache = array_unique(array_merge($this->table_cache, $found_tables));
        }

        if (in_array($tablename, $found_tables)) {
            return true;
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
    public function fieldExists($table, $field, $usecache = true)
    {
        if (!$this->tableExists($table, $usecache)) {
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
    public function disableTableCaching()
    {
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
    public static function quoteName($name)
    {
       //handle verbatim names
        if ($name instanceof QueryExpression) {
            return $name->getValue();
        }
       //handle aliases
        $names = preg_split('/\s+AS\s+/i', $name);
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
            return ($name[0] == '`' ? $name : ($name === '*' ? $name : "`$name`"));
        }
    }

    /**
     * Quote value for insert/update
     *
     * @param mixed $value Value
     *
     * @return mixed
     */
    public static function quoteValue($value)
    {
        if ($value instanceof QueryParam || $value instanceof QueryExpression) {
            //no quote for query parameters nor expressions
            $value = $value->getValue();
        } else if ($value === null || $value === 'NULL' || $value === 'null') {
            $value = 'NULL';
        } else if (is_bool($value)) {
            // transform boolean as int (prevent `false` to be transformed to empty string)
            $value = "'" . (int)$value . "'";
        } else {
            if (Sanitizer::isNsClassOrCallableIdentifier($value)) {
                // Values that corresponds to an existing namespaced class are not sanitized (see `Glpi\Toolbox\Sanitizer::sanitize()`).
                // However, they have to be escaped in SQL queries.
                // Note: method is called statically, so `$DB` may be not defined yet in edge cases (install process).
                global $DB;
                $value = $DB instanceof DBmysql && $DB->connected ? $DB->escape($value) : $value;
            }

           // phone numbers may start with '+' and will be considered as numeric
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
     * @param QuerySubQuery|array  $params Array of field => value pairs or a QuerySubQuery for INSERT INTO ... SELECT
     * @phpstan-param array<string, mixed>|QuerySubQuery $params
     *
     * @return string
     */
    public function buildInsert($table, $params)
    {
        $query = "INSERT INTO " . self::quoteName($table) . ' ';

        $fields = [];
        if ($params instanceof QuerySubQuery) {
            // INSERT INTO ... SELECT Query where the sub-query returns all columns needed for the insert
            $query .= $params->getQuery();
        } else {
            $query .= "(";
            foreach ($params as $key => &$value) {
                $fields[] = $this->quoteName($key);
                $value = $this->quoteValue($value);
            }

            $query .= implode(', ', $fields);
            $query .= ") VALUES (";
            $query .= implode(", ", $params);
            $query .= ")";
        }

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
    public function insert($table, $params)
    {
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
    public function insertOrDie($table, $params, $message = '')
    {
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
    public function buildUpdate($table, $params, $clauses, array $joins = [])
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

        $query  = "UPDATE " . self::quoteName($table);

       //JOINS
        $it = new DBmysqlIterator($this);
        $query .= $it->analyseJoins($joins);

        $query .= " SET ";
        foreach ($params as $field => $value) {
            $query .= self::quoteName($field) . " = " . $this->quoteValue($value) . ", ";
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
    public function update($table, $params, $where, array $joins = [])
    {
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
    public function updateOrDie($table, $params, $where, $message = '', array $joins = [])
    {
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
     * @param boolean $onlyone Do the update only one element, defaults to true
     *
     * @return mysqli_result|boolean Query result handler
     */
    public function updateOrInsert($table, $params, $where, $onlyone = true)
    {
        $req = $this->request($table, $where);
        $data = array_merge($where, $params);
        if ($req->count() == 0) {
            return $this->insertOrDie($table, $data, 'Unable to create new element or update existing one');
        } else if ($req->count() == 1 || !$onlyone) {
            return $this->updateOrDie($table, $data, $where, 'Unable to create new element or update existing one');
        } else {
            trigger_error('Update would change too many rows!', E_USER_WARNING);
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
    public function buildDelete($table, $where, array $joins = [])
    {

        if (!count($where)) {
            throw new \RuntimeException('Cannot run an DELETE query without WHERE clause!');
        }

        $query  = "DELETE " . self::quoteName($table) . " FROM " . self::quoteName($table);

        $it = new DBmysqlIterator($this);
        $query .= $it->analyseJoins($joins);
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
    public function delete($table, $where, array $joins = [])
    {
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
    public function deleteOrDie($table, $where, $message = '', array $joins = [])
    {
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
     * Truncate table in the database
     *
     * @since 10.0.0
     *
     * @param string $table  Table name
     *
     * @return mysqli_result|boolean Query result handler
     */
    public function truncate($table)
    {
        // Use delete to prevent table corruption on some MySQL operations
        // (i.e. when using mysqldump without `--single-transaction` option)
        return $this->delete($table, [1]);
    }

    /**
     * Truncate table in the database or die
     * (optionally with a message) if it fails
     *
     * @since 10.0.0
     *
     * @param string $table   Table name
     * @param string $message Explanation of query (default '')
     *
     * @return mysqli_result|boolean Query result handler
     */
    public function truncateOrDie($table, $message = '')
    {
        $table_name = $this::quoteName($table);
        $res = $this->query("TRUNCATE $table_name");
        if (!$res) {
           //TRANS: %1$s is the description, %2$s is the query, %3$s is the error message
            $message = sprintf(
                __('%1$s - Error during the database query: %2$s - Error is %3$s'),
                $message,
                "TRUNCATE $table",
                $this->error()
            );
            if (isCommandLine()) {
                 throw new \RuntimeException($message);
            }
            echo $message . "\n";
            die(1);
        }
        return $res;
    }

    /**
     * Drops a table
     *
     * @param string $name   Table name
     * @param bool   $exists Add IF EXISTS clause
     *
     * @return bool|mysqli_result
     */
    public function dropTable(string $name, bool $exists = false)
    {
        $res = $this->query(
            $this->buildDrop(
                $name,
                'TABLE',
                $exists
            )
        );
        return $res;
    }

    /**
     * Drops a view
     *
     * @param string $name   View name
     * @param bool   $exists Add IF EXISTS clause
     *
     * @return bool|mysqli_result
     */
    public function dropView(string $name, bool $exists = false)
    {
        $res = $this->query(
            $this->buildDrop(
                $name,
                'VIEW',
                $exists
            )
        );
        return $res;
    }

    /**
     * Builds a DROP query
     *
     * @param string $name   Name to drop
     * @param string $type   Type to drop
     * @param bool   $exists Add IF EXISTS clause
     *
     * @return string
     */
    public function buildDrop(string $name, string $type, bool $exists = false): string
    {
        $known_types = [
            'TABLE',
            'VIEW'
        ];
        if (!in_array($type, $known_types)) {
            throw new \InvalidArgumentException('Unknown type to drop: ' . $type);
        }

        $name = $this::quoteName($name);
        $query = "DROP $type";
        if ($exists) {
            $query .= ' IF EXISTS';
        }
        $query .= " $name";
        return $query;
    }

    /**
     * Get database raw version
     *
     * @return string
     */
    public function getVersion()
    {
        $req = $this->request('SELECT version()')->current();
        $raw = $req['version()'];
        return $raw;
    }

    /**
     * Starts a transaction
     *
     * @return boolean
     */
    public function beginTransaction()
    {
        if ($this->in_transaction === true) {
            trigger_error('A database transaction has already been started!', E_USER_WARNING);
        }
        $this->in_transaction = true;
        return $this->dbh->begin_transaction();
    }

    public function setSavepoint(string $name, $force = false)
    {
        if (!$this->in_transaction && $force) {
            $this->beginTransaction();
        }
        if ($this->in_transaction) {
            $this->dbh->savepoint($name);
        } else {
           // Not already in transaction or failed to start one now
            trigger_error('Unable to set DB savepoint because no transaction was started', E_USER_WARNING);
        }
    }

    /**
     * Commits a transaction
     *
     * @return boolean
     */
    public function commit()
    {
        $this->in_transaction = false;
        return $this->dbh->commit();
    }

    /**
     * Rollbacks a transaction completely or to a specified savepoint
     *
     * @return boolean
     */
    public function rollBack($savepoint = null)
    {
        if (!$savepoint) {
            $this->in_transaction = false;
            return $this->dbh->rollback();
        } else {
            return $this->rollbackTo($savepoint);
        }
    }

    /**
     * Rollbacks a transaction to a specified savepoint
     *
     * @param string $name
     *
     * @return boolean
     */
    protected function rollbackTo($name)
    {
        // No proper rollback to savepoint support in mysqli extension?
        $result = $this->query('ROLLBACK TO ' . self::quoteName($name));
        return $result !== false;
    }

    /**
     * Are we in a transaction?
     *
     * @return boolean
     */
    public function inTransaction()
    {
        return $this->in_transaction;
    }

    /**
     * Defines timezone to use.
     *
     * @param string $timezone
     *
     * @return DBmysql
     */
    public function setTimezone($timezone)
    {
       //setup timezone
        if ($this->use_timezones) {
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
    public function getTimezones()
    {
        if (!$this->use_timezones) {
            return [];
        }

        $list = []; //default $tz is empty

        $from_php = \DateTimeZone::listIdentifiers();
        $now = new \DateTime();

        $iterator = $this->request([
            'SELECT' => 'Name',
            'FROM'   => 'mysql.time_zone_name',
            'WHERE'  => ['Name' => $from_php]
        ]);

        foreach ($iterator as $from_mysql) {
            $now->setTimezone(new \DateTimeZone($from_mysql['Name']));
            $list[$from_mysql['Name']] = $from_mysql['Name'] . $now->format(" (T P)");
        }

        return $list;
    }

    /**
     * Clear cached schema information.
     *
     * @return void
     */
    public function clearSchemaCache()
    {
        $this->table_cache = [];
        $this->field_cache = [];
    }

    /**
     * Quote a value for a specified type
     * Should be used for PDO, but this will prevent heavy
     * replacements in the source code in the future.
     *
     * @param mixed   $value Value to quote
     * @param integer $type  Value type, defaults to PDO::PARAM_STR
     *
     * @return mixed
     *
     * @since 9.5.0
     */
    public function quote($value, int $type = 2/*\PDO::PARAM_STR*/)
    {
        return "'" . $this->escape($value) . "'";
       //return $this->dbh->quote($value, $type);
    }

    /**
     * Get character used to quote names for current database engine
     *
     * @return string
     *
     * @since 9.5.0
     */
    public static function getQuoteNameChar(): string
    {
        return '`';
    }

    /**
     * Is value quoted as database field/expression?
     *
     * @param string|\QueryExpression $value Value to check
     *
     * @return boolean
     *
     * @since 9.5.0
     */
    public static function isNameQuoted($value): bool
    {
        $quote = static::getQuoteNameChar();
        return is_string($value) && trim($value, $quote) != $value;
    }

    /**
     * Remove SQL comments
     * © 2011 PHPBB Group
     *
     * @param string $output SQL statements
     *
     * @return string
     */
    public function removeSqlComments($output)
    {
        $lines = explode("\n", $output);
        $output = "";

       // try to keep mem. use down
        $linecount = count($lines);

        $in_comment = false;
        for ($i = 0; $i < $linecount; $i++) {
            if (preg_match("/^\/\*/", $lines[$i])) {
                $in_comment = true;
            }

            if (!$in_comment) {
                $output .= $lines[$i] . "\n";
            }

            if (preg_match("/\*\/$/", preg_quote($lines[$i]))) {
                $in_comment = false;
            }
        }

        unset($lines);
        return trim($output);
    }

    /**
     * Remove remarks and comments from SQL
     * @see DBmysql::removeSqlComments()
     * © 2011 PHPBB Group
     *
     * @param $string $sql SQL statements
     *
     * @return string
     */
    public function removeSqlRemarks($sql)
    {
        $lines = explode("\n", $sql);

       // try to keep mem. use down
        $sql = "";

        $linecount = count($lines);
        $output = "";

        for ($i = 0; $i < $linecount; $i++) {
            if (($i != ($linecount - 1)) || (strlen($lines[$i]) > 0)) {
                if (isset($lines[$i][0])) {
                    if ($lines[$i][0] != "#" && substr($lines[$i], 0, 2) != "--") {
                        $output .= $lines[$i] . "\n";
                    } else {
                        $output .= "\n";
                    }
                }
                // Trading a bit of speed for lower mem. use here.
                $lines[$i] = "";
            }
        }
        return trim($this->removeSqlComments($output));
    }

    /**
     * Fetch warnings from last query.
     *
     * @return array
     */
    private function fetchQueryWarnings(): array
    {
        $warnings = [];

        if ($this->dbh->warning_count > 0 && $warnings_result = $this->dbh->query('SHOW WARNINGS')) {
           // Warnings to exclude
            $excludes = [];

            if (!$this->use_utf8mb4 || !$this->log_deprecation_warnings) {
                // Exclude warnings related to usage of "utf8mb3" charset, as database has not been migrated yet.
                $excludes[] = 1287; // 'utf8mb3' is deprecated and will be removed in a future release. Please use utf8mb4 instead.
                $excludes[] = 3719; // 'utf8' is currently an alias for the character set UTF8MB3, but will be an alias for UTF8MB4 in a future release. Please consider using UTF8MB4 in order to be unambiguous.
                $excludes[] = 3778; // 'utf8_unicode_ci' is a collation of the deprecated character set UTF8MB3. Please consider using UTF8MB4 with an appropriate collation instead.
            }
            if (!$this->log_deprecation_warnings) {
                // Mute deprecations related to elements that are heavilly used in old migrations and in plugins
                // as it may require a lot of work to fix them.
                $excludes[] = 1681; // Integer display width is deprecated and will be removed in a future release.
            }

            while ($warning = $warnings_result->fetch_assoc()) {
                if ($warning['Level'] === 'Note' || in_array($warning['Code'], $excludes)) {
                    continue;
                }
                $warnings[] = $warning;
            }
        }

        return $warnings;
    }

    /**
     * Get SQL warnings related to last query.
     * @return array
     */
    public function getLastQueryWarnings(): array
    {
        return $this->last_query_warnings;
    }

    /**
     * Set charset to use for DB connection.
     *
     * @return void
     */
    public function setConnectionCharset(): void
    {
        DBConnection::setConnectionCharset($this->dbh, $this->use_utf8mb4);
    }

    /**
     * Executes a prepared statement
     *
     * @param mysqli_stmt $stmt Statement to execute
     *
     * @return void
     */
    public function executeStatement(mysqli_stmt $stmt): void
    {
        if (!$stmt->execute()) {
            trigger_error($stmt->error, E_USER_ERROR);
        }
    }

    /**
     * Check for deprecated table options during ALTER/CREATE TABLE queries.
     *
     * @param string $query
     *
     * @return void
     */
    private function checkForDeprecatedTableOptions(string $query): void
    {
        $table_matches = [];
        $table_pattern = '/'
            . '(ALTER|CREATE)'
            . '(\s+TEMPORARY)?'
            . '\s+TABLE'
            . '(\s+IF\s+NOT\s+EXISTS)?'
            . '\s*(\s|`)(?<table>[^`\s]+)(\s|`)'
            . '/i';
        if (preg_match($table_pattern, $query, $table_matches) !== 1) {
            return;
        }

        // Wrong UTF8 charset/collation
        $charset_matches = [];
        if ($this->use_utf8mb4 && preg_match('/(?<invalid>(utf8(_[^\';\s]+)?))([\';\s]|$)/', $query, $charset_matches)) {
            trigger_error(
                sprintf(
                    'Usage of "%s" charset/collation detected, should be "%s"',
                    $charset_matches['invalid'],
                    str_replace('utf8', 'utf8mb4', $charset_matches['invalid'])
                ),
                E_USER_WARNING
            );
        } else if (!$this->use_utf8mb4 && preg_match('/(?<invalid>(utf8mb4(_[^\';\s]+)?))([\';\s]|$)/', $query, $charset_matches)) {
            trigger_error(
                sprintf(
                    'Usage of "%s" charset/collation detected, should be "%s"',
                    $charset_matches['invalid'],
                    str_replace('utf8mb4', 'utf8', $charset_matches['invalid'])
                ),
                E_USER_WARNING
            );
        }

        // Usage of MyISAM
        if (!$this->allow_myisam && preg_match('/[)\s]engine\s*=\s*\'?myisam([\';\s]|$)/i', $query)) {
            trigger_error('Usage of "MyISAM" engine is discouraged, please use "InnoDB" engine.', E_USER_WARNING);
        }

        // Usage of datetime
        if (!$this->allow_datetime && preg_match('/ datetime /i', $query)) {
            trigger_error('Usage of "DATETIME" fields is discouraged, please use "TIMESTAMP" fields instead.', E_USER_WARNING);
        }

        // Usage of signed integers in primary/foreign keys
        $pattern = '/'
            . '(\s|`)(?<field>id|[^`\s]+_id|[^`\s]+_id_[^`\s]+)(\s|`)' // `id`, `xxx_id` or `xxx_id_yyy` field
            . '\s*'
            . '(tiny|small|medium|big)?int' // with int type
            . '(?!\s+unsigned)' // not unsigned
            . '/i';
        $field_matches = [];
        if (!$this->allow_signed_keys && preg_match($pattern, $query, $field_matches)) {
            trigger_error(
                sprintf(
                    'Usage of signed integers in primary or foreign keys is discouraged, please use unsigned integers instead in `%s`.`%s`.',
                    $table_matches['table'],
                    $field_matches['field']
                ),
                E_USER_WARNING
            );
        }
    }

    /**
     * Return configuration boolean properties computed using current state of tables.
     *
     * @return array
     */
    public function getComputedConfigBooleanFlags(): array
    {
        $config_flags = [];

        if ($this->getTzIncompatibleTables(true)->count() === 0) {
           // Disallow datetime if there is no core table still using this field type.
            $config_flags[DBConnection::PROPERTY_ALLOW_DATETIME] = false;

            $timezones_requirement = new DbTimezones($this);
            if ($timezones_requirement->isValidated()) {
                // Activate timezone usage if timezones are available and all tables are already migrated.
                $config_flags[DBConnection::PROPERTY_USE_TIMEZONES] = true;
            }
        }

        if ($this->getNonUtf8mb4Tables(true)->count() === 0) {
           // Use utf8mb4 charset for update process if there all core table are using this charset.
            $config_flags[DBConnection::PROPERTY_USE_UTF8MB4] = true;
        }

        if ($this->getMyIsamTables(true)->count() === 0) {
           // Disallow MyISAM if there is no core table still using this engine.
            $config_flags[DBConnection::PROPERTY_ALLOW_MYISAM] = false;
        }

        if ($this->getSignedKeysColumns(true)->count() === 0) {
           // Disallow MyISAM if there is no core table still using this engine.
            $config_flags[DBConnection::PROPERTY_ALLOW_SIGNED_KEYS] = false;
        }

        return $config_flags;
    }
}
