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

use DBmysqlIterator;
use Pdo;
use Toolbox;
use Config;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 *  Database class for Mysql
**/
class MySql extends AbstractDatabase
{

    public function getDriver(): string
    {
        return 'mysql';
    }

    public function connect($server = null)
    {
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

        $this->setTimezone($this->guessTimezone());
    }

   /**
    * Guess timezone
    *
    * Will  check for an existing loaded timezone from user,
    * then will check in preferences and finally will fallback to system one.
    *
    * @return string
    */
    protected function guessTimezone()
    {
        if (isset($_SESSION['glpi_tz'])) {
            $zone = $_SESSION['glpi_tz'];
        } else {
            $conf_tz = ['value' => null];
            if ($this->tableExists(Config::getTable())
                && $this->fieldExists(Config::getTable(), 'value')
            ) {
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
    * @deprecated 10.0.0
    *
    * @param string $string String to escape
    *
    * @return string escaped string
    */
    public function escape($string)
    {
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
    public function query($query)
    {
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
    public function queryOrDie($query, $message = '')
    {
        Toolbox::deprecated();
        return $this->rawQueryOrDie($query, $message);
    }

    public function insertId(string $table = '')
    {
        return (int)$this->dbh->lastInsertID();
    }

    public function listTables(string $table = 'glpi_%', array $where = []): DBmysqlIterator
    {
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
    public function getMyIsamTables(): DBmysqlIterator
    {
        $iterator = $this->listTables('glpi_%', ['engine' => 'MyIsam']);
        return $iterator;
    }

    public function listFields(string $table, bool $usecache = true)
    {
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
    public function affectedRows()
    {
        throw new \RuntimeException('affectedRows method could not be used... Use PDOStatement::rowCount instead.');
    }

    public function getInfo(): array
    {
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

    public function getLock(string $name): bool
    {
        $name          = addslashes($this->dbdefault.'.'.$name);
        $query         = "SELECT GET_LOCK('$name', 0)";
        $result        = $this->rawQuery($query);
        list($lock_ok) = $this->fetchRow($result);

        return (bool)$lock_ok;
    }

    public function releaseLock(string $name): bool
    {
        $name          = addslashes($this->dbdefault.'.'.$name);
        $query         = "SELECT RELEASE_LOCK('$name')";
        $result        = $this->rawQuery($query);
        list($lock_ok) = $this->fetchRow($result);

        return (bool)$lock_ok;
    }

    public static function getQuoteNameChar(): string
    {
        return '`';
    }

    public function getTableSchema(string $table, $structure = null): array
    {
        if ($structure === null) {
            $structure = $this->rawQuery("SHOW CREATE TABLE `$table`")->fetch();
            $structure = $structure[1];
        }

       //get table index
        $index = preg_grep(
            "/^\s\s+?KEY/",
            array_map(
                function ($idx) {
                    return rtrim($idx, ',');
                },
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
            ],
            [
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

    public function getVersion(): string
    {
        $req = $this->requestRaw('SELECT version()')->next();
        $raw = $req['version()'];
        return $raw;
    }

    public function areTimezonesAvailable(string &$msg = '') :bool
    {
        $mysql_db_res = $this->requestRaw('SHOW DATABASES LIKE ' . $this->quoteValue('mysql'));
        if ($mysql_db_res->count() === 0) {
            $msg = __('Access to timezone database (mysql) is not allowed.');
            return false;
        }
        $tz_table_res = $this->requestRaw(
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

    public function setTimezone($timezone) :AbstractDatabase
    {
       //setup timezone
        if ($this->areTimezonesAvailable()) {
           //4dev => to drop
            date_default_timezone_set($timezone);
            $this->dbh->query("SET SESSION time_zone = '$timezone'");
            $_SESSION['glpi_currenttime'] = date("Y-m-d H:i:s");
        }
        return $this;
    }

    public function getTimezones() :array
    {
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

    public function notTzMigrated() :int
    {
        global $DB;

        $result = $DB->request([
            'COUNT'       => 'cpt',
            'FROM'        => 'INFORMATION_SCHEMA.COLUMNS',
            'WHERE'       => [
               'INFORMATION_SCHEMA.COLUMNS.TABLE_SCHEMA'  => $DB->dbdefault,
               'INFORMATION_SCHEMA.COLUMNS.COLUMN_TYPE'   => ['DATETIME']
            ]
        ])->next();
        return (int)$result['cpt'];
    }
}
