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
 *  Database class for PostgreSQL
**/
class Postgres extends AbstractDatabase
{

    public function getDriver(): string
    {
        return 'pgsql';
    }

    public function connect($server = null)
    {
        $this->connected = false;
        $dsn = $this->getDsn($server);

        if (null === $dsn) {
            return;
        }

        $this->dbh = new PDO(
            "$dsn;dbname={$this->dbdefault}",
            $this->dbuser,
            rawurldecode($this->dbpassword)
        );
        $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        /*if (GLPI_FORCE_EMPTY_SQL_MODE) {
            $this->dbh->query("SET SESSION sql_mode = ''");
        }*/

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

    public function insertId(string $table)
    {
        return (int)$this->dbh->lastInsertID($table . '_id_seq');
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
        return true;
        $name          = addslashes($this->dbdefault.'.'.$name);
        $query         = "SELECT GET_LOCK('$name', 0)";
        $result        = $this->rawQuery($query);
        list($lock_ok) = $this->fetchRow($result);

        return (bool)$lock_ok;
    }

    public function releaseLock(string $name): bool
    {
        return true;
        $name          = addslashes($this->dbdefault.'.'.$name);
        $query         = "SELECT RELEASE_LOCK('$name')";
        $result        = $this->rawQuery($query);
        list($lock_ok) = $this->fetchRow($result);

        return (bool)$lock_ok;
    }

    public static function getQuoteNameChar(): string
    {
        return '"';
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
        $raw = $req['version'];
        return $raw;
    }


    public function setTimezone($timezone) :AbstractDatabase
    {
       //setup timezone
        if ($this->areTimezonesAvailable()) {
            date_default_timezone_set($timezone);
            $this->dbh->query("SET TIME ZONE ".$this->quote($timezone));
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
         'SELECT' => 'name',
         'FROM'   => new \QueryExpression('pg_timezone_names()'),
         'WHERE'  => ['name' => $from_php]
        ]);

        while ($from_pgsql = $iterator->next()) {
            $now->setTimezone(new \DateTimeZone($from_pgsql['name']));
            $list[$from_pgsql['name']] = $from_pgsql['name'] . $now->format(" (T P)");
        }

        return $list;
    }

    public function notTzMigrated() :int
    {
        return 0;
    }

    public function getPrepareParameters() :array
    {
        return [];
    }

    protected function getDeleteSql() :string
    {
        return 'DELETE FROM %from';
    }
}
