<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryParam;
use Glpi\DBAL\QuerySubQuery;
use Glpi\DBAL\QueryUnion;
use Glpi\Debug\Profile;
use Glpi\System\Requirement\DbTimezones;
use Glpi\Toolbox\SanitizedStringsDecoder;
use Safe\DateTime;

use function Safe\filesize;
use function Safe\fopen;
use function Safe\fread;
use function Safe\ini_get;
use function Safe\preg_match;
use function Safe\preg_replace;
use function Safe\preg_split;

/**
 *  Database class for Mysql
 **/
class DBmysql
{
    //! Database Host - string or Array of string (round-robin)
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

    // Slave management
    public $slave              = false;

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

    private string $current_query;

    private DBmysqlIterator $iterator;

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

    private int $transaction_level = 0;

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
            $i    = ($choice ?? mt_rand(0, count($this->dbhost) - 1));
            $host = $this->dbhost[$i];
        } else {
            $host = $this->dbhost;
        }

        // Add timeout option to avoid infinite connection
        $this->dbh->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);

        $hostport = explode(":", $host);
        if (count($hostport) < 2) {
            // Host
            @$this->dbh->real_connect($host, $this->dbuser, rawurldecode($this->dbpassword), $this->dbdefault);
        } elseif (intval($hostport[1]) > 0) {
            // Host:port
            @$this->dbh->real_connect($hostport[0], $this->dbuser, rawurldecode($this->dbpassword), $this->dbdefault, (int) $hostport[1]);
        } else {
            // :Socket
            @$this->dbh->real_connect($hostport[0], $this->dbuser, rawurldecode($this->dbpassword), $this->dbdefault, (int) ini_get('mysqli.default_port'), $hostport[1]);
        }

        if (!$this->dbh->connect_error) {
            $this->setConnectionCharset();

            // force mysqlnd to return int and float types correctly (not as strings)
            $this->dbh->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);

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
                            'name'      => 'timezone',
                        ],
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
     * @return mysqli_result|boolean Query result handler
     *
     * @deprecated 10.0.11
     */
    public function query($query)
    {
        throw new Exception('Executing direct queries is not allowed!');
    }

    /**
     * Execute a MySQL query
     *
     * @phpstan-impure Results will depend on database content.
     *
     * @param string $query Query to execute
     *
     * @return mysqli_result|boolean Query result handler
     */
    public function doQuery($query)
    {
        $debug_data = [
            'query' => $query,
            'time' => 0,
            'rows' => 0,
            'errors' => '',
            'warnings' => '',
        ];

        $start_time = microtime(true);

        $this->checkForDeprecatedTableOptions($query);

        $res = $this->dbh->query($query);
        if (!$res) {
            throw new RuntimeException(
                sprintf(
                    'MySQL query error: %s (%d) in SQL query "%s".',
                    $this->dbh->error,
                    $this->dbh->errno,
                    $query
                )
            );
        }

        $duration = (microtime(true) - $start_time) * 1000;

        $debug_data['time'] = $duration;
        $debug_data['rows'] = $this->affectedRows();

        // Trigger warning errors if any SQL warnings was produced by the query
        $sql_warnings = $this->fetchQueryWarnings(); // Ensure that we collect warning after affected rows
        if (count($sql_warnings) > 0) {
            $warnings_string = implode(
                "\n",
                array_map(
                    static fn($warning) => sprintf('%s: %s', $warning['Code'], $warning['Message']),
                    $sql_warnings
                )
            );

            $debug_data['warnings'] = $warnings_string;

            trigger_error(
                sprintf(
                    "MySQL query warnings:\n  SQL: %s\n  Warnings: \n%s",
                    $query,
                    $warnings_string
                ),
                E_USER_WARNING
            );
        }

        if (isset($_SESSION['glpi_use_mode']) && ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE)) {
            Profile::getCurrent()->addSQLQueryData(
                $debug_data['query'],
                $debug_data['time'],
                $debug_data['rows'],
                $debug_data['errors'],
                $debug_data['warnings']
            );
        }

        if ($this->execution_time === true) {
            $this->execution_time = $duration;
        }
        return $res;
    }

    /**
     * Execute a MySQL query and throw an exception
     * (optionally with a message) if it fails
     *
     * @since 0.84
     *
     * @param string $query   Query to execute
     * @param string $message Explanation of query (default '')
     *
     * @return mysqli_result Query result handler
     *
     * @deprecated 10.0.11
     */
    public function queryOrDie($query, $message = '')
    {
        throw new Exception('Executing direct queries is not allowed!');
    }

    /**
     * Execute a MySQL query and throw an exception if it fails.
     *
     * @param string $query   Query to execute
     * @param string $message Explanation of query (default '')
     *
     * @return mysqli_result Query result handler
     *
     * @deprecated 11.0.0
     */
    public function doQueryOrDie($query, $message = '')
    {
        Toolbox::deprecated('Use `DBmysql::doQuery()`.');

        return $this->doQuery($query);
    }

    /**
     * Prepare a MySQL query
     *
     * @param string $query Query to prepare
     *
     * @return mysqli_stmt
     */
    public function prepare($query)
    {
        $res = $this->dbh->prepare($query);
        if (!$res) {
            throw new RuntimeException(
                sprintf(
                    'MySQL prepare error: %s (%d) in SQL query "%s".',
                    $this->dbh->error,
                    $this->dbh->errno,
                    $query
                )
            );
        }
        $this->current_query = $query;
        return $res;
    }

    /**
     * Give result from a sql result
     *
     * @param mysqli_result $result MySQL result handler
     * @param int           $i      Row offset to give
     * @param string|int    $field  Field to give
     *
     * @return mixed Value of the Row $i and the Field $field of the Mysql $result
     */
    public function result($result, $i, $field)
    {
        if (
            $result && ($result->data_seek($i))
            && ($data = $this->fetchArray($result))
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
        return $this->decodeFetchResult($result->fetch_array());
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
        return $this->decodeFetchResult($result->fetch_row());
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
        return $this->decodeFetchResult($result->fetch_assoc());
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
        return $this->decodeFetchResult($result->fetch_object());
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
                'table_name'   => ['LIKE', $table],
            ] + $where,
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
                                    static::quoteName('information_schema.columns.table_schema')
                                ),
                            ],
                        ],
                    ],
                ],
            ],
            'WHERE'     => [
                'information_schema.tables.table_schema' => $this->dbdefault,
                'information_schema.tables.table_name'   => ['LIKE', 'glpi\_%'],
                'information_schema.tables.table_type'    => 'BASE TABLE',
                ['NOT' => ['information_schema.columns.collation_name' => null]],
                ['NOT' => ['information_schema.columns.collation_name' => ['LIKE', 'utf8mb4\_%']]],
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
            'ORDER'    => ['TABLE_NAME'],
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
                                    static::quoteName('information_schema.columns.table_schema')
                                ),
                            ],
                        ],
                    ],
                ],
            ],
            'WHERE'       => [
                'information_schema.tables.table_schema' => $this->dbdefault,
                'information_schema.tables.table_name'   => ['LIKE', 'glpi\_%'],
                'information_schema.tables.table_type'   => 'BASE TABLE',
                'information_schema.columns.data_type'   => 'datetime',
            ],
            'ORDER'       => ['TABLE_NAME'],
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
                                    static::quoteName('information_schema.columns.table_schema')
                                ),
                            ],
                        ],
                    ],
                ],
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
            'ORDER'       => ['TABLE_NAME'],
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
            'ORDER'  => ['TABLE_NAME'],
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
        $result = $this->doQuery(sprintf("SHOW COLUMNS FROM %s", self::quoteName($table)));
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
        $result->free();
        return true;
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
        $queries = $this->getQueriesFromFile($path);

        foreach ($queries as $query) {
            $this->doQuery($query);
        }

        return true;
    }

    /**
     * @internal
     *
     * @return array<string>
     */
    public function getQueriesFromFile(string $path): array
    {
        $script = fopen($path, 'r');
        if (!$script) {
            return [];
        }
        $sql_query = @fread($script, @filesize($path)) . "\n";

        $sql_query = $this->removeSqlRemarks($sql_query);

        $queries = preg_split('/;\s*$/m', $sql_query);

        $queries = array_filter($queries, static fn($query) => \trim($query) !== '');

        return $queries;
    }

    /**
     * Instanciate a Simple DBIterator
     *
     * @param array|QueryUnion  $criteria Query criteria
     *
     * @return DBmysqlIterator
     *
     * @since 11.0.0 The `$debug` parameter has been removed.
     */
    public function request($criteria)
    {
        $iterator = new DBmysqlIterator($this);
        $iterator->execute(...func_get_args()); // pass all args to be compatible with previous signature
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
        $req = $this->doQuery("SELECT @@sql_mode as mode, @@version AS vers, @@version_comment AS stype");

        if (($data = $req->fetch_array())) {
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
        $name          = $this->quote($this->dbdefault . '.' . $name);
        $query         = "SELECT GET_LOCK($name, 0)";
        $result        = $this->doQuery($query);
        [$lock_ok] = $this->fetchRow($result);

        return (bool) $lock_ok;
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
        $name          = $this->quote($this->dbdefault . '.' . $name);
        $query         = "SELECT RELEASE_LOCK($name)";
        $result        = $this->doQuery($query);
        [$lock_ok] = $this->fetchRow($result);

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
        $retrieve_all = !$this->cache_disabled && $this->table_cache === [];

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
     *
     * @psalm-taint-escape sql
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
            throw new RuntimeException(
                'Invalid field name ' . $name
            );
        }

        if (count($names) == 2) {
            $name = self::quoteName($names[0]);
            $name .= ' AS ' . self::quoteName($names[1]);
            return $name;
        }

        if (strpos($name, '.')) {
            $n = explode('.', $name, 2);
            $table = self::quoteName($n[0]);
            $field = ($n[1] === '*') ? $n[1] : self::quoteName($n[1]);
            return "$table.$field";
        }

        if (
            $name === '*'
            || preg_match('/^`[^`]+`$/', $name) === 1
        ) {
            return $name;
        }

        return sprintf(
            '`%s`',
            str_replace('`', '``', $name) // escape backticks by doubling them
        );
    }

    /**
     * Quote value for insert/update
     *
     * @param mixed $value Value
     *
     * @return mixed
     *
     * @psalm-taint-escape sql
     */
    public static function quoteValue($value)
    {
        if ($value instanceof QueryParam || $value instanceof QueryExpression) {
            //no quote for query parameters nor expressions
            $value = $value->getValue();
        } elseif ($value === null || $value === 'NULL' || $value === 'null') {
            $value = 'NULL';
        } elseif (is_bool($value)) {
            // transform boolean as int (prevent `false` to be transformed to empty string)
            $value = "'" . (int) $value . "'";
        } else {
            global $DB;
            $value = DBConnection::isDbAvailable() ? $DB->escape($value) : $value;
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

        if ($params instanceof QuerySubQuery) {
            // INSERT INTO ... SELECT Query where the sub-query returns all columns needed for the insert
            $query .= $params->getQuery();
        } else {
            $fields = [];
            $values = [];
            foreach ($params as $key => $value) {
                $fields[] = static::quoteName($key);
                if ($value instanceof QueryExpression) {
                    $values[] = $value->getValue();
                    unset($params[$key]);
                } elseif ($value instanceof QueryParam) {
                    $values[] = $value->getValue();
                } else {
                    $values[] = self::quoteValue($value);
                }
            }
            $query .= "(";
            $query .= implode(', ', $fields);
            $query .= ") VALUES (";
            $query .= implode(", ", $values);
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
     * @param QuerySubQuery|array  $params Array of field => value pairs or a QuerySubQuery for INSERT INTO ... SELECT
     *
     * @return mysqli_result|boolean Query result handler
     */
    public function insert($table, $params)
    {
        $result = $this->doQuery(
            $this->buildInsert($table, $params)
        );
        return $result;
    }

    /**
     * Insert a row in the database and throw an exception if it fails.
     *
     * @since 9.3
     *
     * @param string $table  Table name
     * @param array  $params  Query parameters ([field name => field value)
     * @param string $message Explanation of query (default '')
     *
     * @return mysqli_result|boolean Query result handler
     *
     * @deprecated 11.0.0
     */
    public function insertOrDie($table, $params, $message = '')
    {
        Toolbox::deprecated('Use `DBmysql::insert()`.');

        return $this->insert($table, $params);
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
        //when no explicit "WHERE", we only have a WHERE clause.
        if (!isset($clauses['WHERE'])) {
            $clauses  = ['WHERE' => $clauses];
        } else {
            $known_clauses = ['WHERE', 'ORDER', 'LIMIT', 'START'];
            foreach (array_keys($clauses) as $key) {
                if (!in_array($key, $known_clauses)) {
                    throw new RuntimeException(
                        str_replace(
                            '%clause',
                            $key,
                            'Trying to use an unknown clause (%clause) building update query!'
                        )
                    );
                }
            }
        }

        if (!count($clauses['WHERE'])) {
            throw new RuntimeException('Cannot run an UPDATE query without WHERE clause!');
        }
        if (!count($params)) {
            throw new RuntimeException('Cannot run an UPDATE query without parameters!');
        }

        $query  = "UPDATE " . self::quoteName($table);

        //JOINS
        $this->iterator = new DBmysqlIterator($this);
        $query .= $this->iterator->analyseJoins($joins);

        $query .= " SET ";
        foreach ($params as $field => $value) {
            if ($value instanceof QueryParam || $value instanceof QueryExpression) {
                //no quote for query parameters nor expressions
                $query .= self::quoteName($field) . " = " . $value->getValue() . ", ";
            } elseif ($value === null || $value === 'NULL' || $value === 'null') {
                $query .= self::quoteName($field) . " = NULL, ";
            } elseif (is_bool($value)) {
                // transform boolean as int (prevent `false` to be transformed to empty string)
                $query .= self::quoteName($field) . " = '" . (int) $value . "', ";
            } else {
                $query .= self::quoteName($field) . " = " . self::quoteValue($value) . ", ";
            }
        }
        $query = rtrim($query, ', ');

        $query .= " WHERE " . $this->iterator->analyseCrit($clauses['WHERE']);

        // ORDER BY
        if (isset($clauses['ORDER']) && !empty($clauses['ORDER'])) {
            $query .= $this->iterator->handleOrderClause($clauses['ORDER']);
        }

        if (isset($clauses['LIMIT']) && !empty($clauses['LIMIT'])) {
            $offset = (isset($clauses['START']) && !empty($clauses['START'])) ? $clauses['START'] : null;
            $query .= $this->iterator->handleLimits($clauses['LIMIT'], $offset);
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
        $result = $this->doQuery($query);
        return $result;
    }

    /**
     * Update a row in the database and throw an exception if it fails.
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
     *
     * @deprecated 11.0.0
     */
    public function updateOrDie($table, $params, $where, $message = '', array $joins = [])
    {
        Toolbox::deprecated('Use `DBmysql::update()`.');

        return $this->update($table, $params, $where, $joins);
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
        $query = $this->buildUpdateOrInsert($table, $params, $where, $onlyone);
        return $this->doQuery($query);
    }

    public function buildUpdateOrInsert($table, $params, $where, $onlyone = true): string
    {
        $req = $this->request(array_merge(['FROM' => $table], $where));
        $data = array_merge($where, $params);
        if ($req->count() == 0) {
            return $this->buildInsert($table, $data);
        } elseif ($req->count() == 1 || !$onlyone) {
            return $this->buildUpdate($table, $data, $where);
        } else {
            throw new RuntimeException('Update would change too many rows!');
        }
    }

    /**
     * Builds a delete statement
     *
     * @since 9.3
     *
     * @param string $table  Table name
     * @param array  $where  WHERE clause (@see DBmysqlIterator capabilities)
     * @param array  $joins  JOINS criteria array
     *
     * @since 9.4.0 $joins parameter added
     * @return string
     */
    public function buildDelete($table, $where, array $joins = [])
    {

        if (!count($where)) {
            throw new RuntimeException('Cannot run an DELETE query without WHERE clause!');
        }

        $query  = "DELETE " . self::quoteName($table) . " FROM " . self::quoteName($table);

        $this->iterator = new DBmysqlIterator($this);
        $query .= $this->iterator->analyseJoins($joins);
        $query .= " WHERE " . $this->iterator->analyseCrit($where);

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
        $result = $this->doQuery($query);
        return $result;
    }

    /**
     * Delete a row in the database and throw an exception if it fails.
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
     *
     * @deprecated 11.0.0
     */
    public function deleteOrDie($table, $where, $message = '', array $joins = [])
    {
        Toolbox::deprecated('Use `DBmysql::delete()`.');

        return $this->delete($table, $where, $joins);
    }


    /**
     * Truncate table in the database
     *
     * @since 10.0.0
     *
     * @param string $table Table name
     *
     * @return mysqli_result|boolean Query result handler
     *
     * @deprecated 11.0.0
     */
    public function truncate($table)
    {
        Toolbox::deprecated();
        // Use delete to prevent table corruption on some MySQL operations
        // (i.e. when using mysqldump without `--single-transaction` option)
        return $this->delete($table, [1]);
    }

    /**
     * Truncate table in the database or throw an exception
     * (optionally with a message) if it fails
     *
     * @since 10.0.0
     *
     * @param string $table   Table name
     * @param string $message Explanation of query (default '')
     *
     * @return mysqli_result|boolean Query result handler
     *
     * @deprecated 11.0.0
     */
    public function truncateOrDie($table, $message = '')
    {
        Toolbox::deprecated();
        return $this->deleteOrDie($table, [1], $message);
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
        $res = $this->doQuery(
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
        $res = $this->doQuery(
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
            'VIEW',
            'INDEX',
            'FOREIGN KEY',
            'FIELD',
        ];
        if (!in_array($type, $known_types)) {
            throw new InvalidArgumentException('Unknown type to drop: ' . $type);
        }

        $name = $this::quoteName($name);
        $query = 'DROP';
        if ($type != 'FIELD') {
            $query .= " $type";
        }
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
        $res = $this->doQuery('SELECT version()');
        $req = $res->fetch_array();
        $raw = $req['version()'];
        return $raw;
    }

    /**
     * Get database raw version and server name
     *
     * @return array<string, string>
     */
    public function getVersionAndServer(): array
    {
        $version_string = $this->getVersion();
        $server  = preg_match('/-MariaDB/', $version_string) ? 'MariaDB' : 'MySQL';
        $version = preg_replace('/^((\d+\.?)+).*$/', '$1', $version_string);
        return [
            'version' => $version,
            'server' => $server,
        ];
    }

    private function isInTransaction(): bool
    {
        return $this->transaction_level > 0;
    }

    private function isInNestedTransaction(): bool
    {
        return $this->transaction_level > 1;
    }

    private function formatSavePointName(int $level): string
    {
        // If we use a save point it means this is at least the second
        // transaction, for example:
        // 0 -> no transaction
        // 1 -> start transaction
        // 2 -> set save point
        // We want the name of the first savepoint to be "savepoint_0", thus
        // we need to remove 2 from the current transaction level.
        $level -= 2;
        return "savepoint_" . $level;
    }

    /**
     * Use instead of `formatSavePointName` when running raw queries.
     * This is needed because there are no methods in the mysqli object to
     * rollback a savepoint so a raw query is needed.
     */
    private function formatAndQuoteSavePointName(int $level): string
    {
        return static::quoteName($this->formatSavePointName($level));
    }

    /**
     * Starts a transaction
     */
    public function beginTransaction(): void
    {
        if (!$this->isInTransaction()) {
            // No transaction underway, simply start a fresh one.
            $success = $this->dbh->begin_transaction();
        } else {
            // A transaction is already underway, create a new savepoint.
            $savepoint = $this->formatSavePointName(
                $this->transaction_level + 1
            );
            $success = $this->dbh->savepoint($savepoint);
        }

        if ($success) {
            $this->transaction_level++;
        } else {
            throw new RuntimeException("Failed to start transaction.");
        }
    }

    /**
     * Commits a transaction
     */
    public function commit(): void
    {
        if (!$this->isInTransaction()) {
            throw new RuntimeException("Not in a transaction.");
        }

        if (!$this->isInNestedTransaction()) {
            // A simple transaction is underway, commit its data.
            $success = $this->dbh->commit();
        } else {
            // A nested transaction is already underway, release the current savepoint.
            $savepoint = $this->formatSavePointName($this->transaction_level);
            $success = $this->dbh->release_savepoint($savepoint);
        }

        if ($success) {
            $this->transaction_level--;
        } else {
            throw new RuntimeException("Failed to commit transaction.");
        }
    }

    /**
     * Rollbacks a transaction completely or to a specified savepoint
     */
    public function rollBack(): void
    {
        if (!$this->isInTransaction()) {
            throw new RuntimeException("Not in a transaction.");
        }

        if (!$this->isInNestedTransaction()) {
            // A simple transaction is underway, roll it back.
            $success = $this->dbh->rollback();
        } else {
            // A nested transaction is already underway, roolback to current savepoint.
            $savepoint = $this->formatAndQuoteSavePointName(
                $this->transaction_level
            );
            $success = $this->doQuery("ROLLBACK TO $savepoint");
        }

        if ($success) {
            $this->transaction_level--;
        } else {
            throw new RuntimeException("Failed to rollback transaction.");
        }
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
            $this->dbh->query(sprintf("SET SESSION time_zone = %s", $this->quote($timezone)));
            $_SESSION['glpi_currenttime'] = date("Y-m-d H:i:s");
        }
        return $this;
    }

    /**
     * Returns list of available timezones.
     *
     * @return string[]
     *
     * @since 9.5.0
     */
    public function getTimezones()
    {
        $list = [];

        $timezones = DateTimeZone::listIdentifiers();
        $results_queries = [];
        foreach ($timezones as $index => $timezone) {
            $results_queries[] =  new QuerySubQuery([
                'SELECT' => ['name', 'value'],
                'FROM' => new QueryExpression(
                    sprintf(
                        '(SELECT %1$s as %2$s, CONVERT_TZ(%3$s, %4$s, %5$s) as %6$s) as %7$s',
                        self::quoteValue($timezone),
                        self::quoteName('name'),
                        self::quoteValue('2000-01-01 00:00:00'),
                        self::quoteValue('GMT'),
                        self::quoteValue($timezone),
                        self::quoteName('value'),
                        self::quoteName(sprintf('timezone_%d', $index)),
                    )
                ),
                'WHERE' => [
                    ['NOT' => ['value' => null]],
                ],
            ]);
        }

        $iterator = $this->request(['FROM' => new QueryUnion($results_queries)]);
        foreach ($iterator as $row) {
            $now = new DateTime();
            $now->setTimezone(new DateTimeZone($row['name']));
            $list[$row['name']] = $row['name'] . $now->format(" (T P)");
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
     * @param string|QueryExpression $value Value to check
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

        $linecount = count($lines);
        $output = "";

        for ($i = 0; $i < $linecount; $i++) {
            if (($i != ($linecount - 1)) || (strlen($lines[$i]) > 0)) {
                if (isset($lines[$i][0])) {
                    if ($lines[$i][0] != "#" && !str_starts_with($lines[$i], "--")) {
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
            throw new RuntimeException(
                sprintf(
                    'MySQL statement error: %s (%d) in SQL query "%s".',
                    $stmt->error,
                    $stmt->errno,
                    $this->current_query
                )
            );
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
        } elseif (!$this->use_utf8mb4 && preg_match('/(?<invalid>(utf8mb4(_[^\';\s]+)?))([\';\s]|$)/', $query, $charset_matches)) {
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
        if (preg_match('/[)\s]engine\s*=\s*\'?myisam([\';\s]|$)/i', $query)) {
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

        if ($this->getSignedKeysColumns(true)->count() === 0) {
            // Disallow MyISAM if there is no core table still using this engine.
            $config_flags[DBConnection::PROPERTY_ALLOW_SIGNED_KEYS] = false;
        }

        return $config_flags;
    }

    /**
     * Decode HTML special chars on fetch operation result.
     */
    private function decodeFetchResult(array|object|false|null $values): array|object|false|null
    {
        if ($values === null || $values === false) {
            // No more results or error on fetch operation.
            return $values;
        }

        foreach ($values as $key => $value) {
            if (!is_string($value)) {
                continue;
            }

            $decoder = new SanitizedStringsDecoder();

            if ($key === 'completename') {
                $value = $decoder->decodeHtmlSpecialCharsInCompletename($value);
            } else {
                $value = $decoder->decodeHtmlSpecialChars($value);
            }

            if (is_object($values)) {
                $values->{$key} = $value;
            } else {
                $values[$key] = $value;
            }
        }

        return $values;
    }

    /**
     * Get global variables values as an associative array.
     *
     * @param array $variables List of variables to get
     *
     * @return array
     */
    final public function getGlobalVariables(array $variables): array
    {
        $query = sprintf(
            'SHOW GLOBAL VARIABLES WHERE %s IN (%s)',
            static::quoteName('Variable_name'),
            implode(', ', array_map([$this, 'quote'], $variables))
        );
        $result = $this->doQuery($query);
        $values = [];
        while ($row = $result->fetch_assoc()) {
            $values[$row['Variable_name']] = $row['Value'];
        }
        return $values;
    }

    /**
     * Get binary log status query, in regard of the MySQL/MariaDB version.
     *
     * @return string
     */
    public function getBinaryLogStatusQuery(): string
    {
        $info = $this->getVersionAndServer();

        if ($info['server'] === 'MySQL' && version_compare($info['version'], '8.4', '>=')) {
            return "SHOW BINARY LOG STATUS";
        }

        if ($info['server'] === 'MariaDB') {
            return "SHOW BINLOG STATUS";
        }

        return "SHOW MASTER STATUS";
    }

    /**
     * Get replica status query, in regard of the MySQL/MariaDB version.
     *
     * @return string
     */
    public function getReplicaStatusQuery(): string
    {
        $info = $this->getVersionAndServer();

        if ($info['server'] === 'MySQL' && version_compare($info['version'], '8.4', '>=')) {
            return "SHOW REPLICA STATUS";
        }

        return "SHOW SLAVE STATUS";
    }

    /**
     * Get replica status variables, in regard of the MySQL/MariaDB version.
     *
     * @return array<string, string>
     */
    public function getReplicaStatusVars(): array
    {
        $info = $this->getVersionAndServer();

        if ($info['server'] === 'MySQL' && version_compare($info['version'], '8.4', '>=')) {
            return [
                'io_running'            => 'Replica_IO_Running',
                'sql_running'           => 'Replica_SQL_Running',
                'source_log_file'       => 'Source_Log_File',
                'source_log_pos'        => 'Read_Source_Log_Pos',
                'seconds_behind_source' => 'Seconds_Behind_Source',
                'last_io_error'         => 'Last_IO_Error',
                'last_sql_error'        => 'Last_SQL_Error',
            ];
        }

        return [
            'io_running'            => 'Slave_IO_Running',
            'sql_running'           => 'Slave_SQL_Running',
            'source_log_file'       => 'Master_Log_File',
            'source_log_pos'        => 'Read_Master_Log_Pos',
            'seconds_behind_source' => 'Seconds_Behind_Master',
            'last_io_error'         => 'Last_IO_Error',
            'last_sql_error'        => 'Last_SQL_Error',
        ];
    }
}
