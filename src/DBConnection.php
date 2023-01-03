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

/**
 *  Database class for Mysql
 **/
class DBConnection extends CommonDBTM
{
    /**
     * "Use timezones" property name.
     * @var string
     */
    public const PROPERTY_USE_TIMEZONES = 'use_timezones';

    /**
     * "Log deprecation warnings" property name.
     * @var string
     */
    public const PROPERTY_LOG_DEPRECATION_WARNINGS = 'log_deprecation_warnings';

    /**
     * "Use UTF8MB4" property name.
     * @var string
     */
    public const PROPERTY_USE_UTF8MB4 = 'use_utf8mb4';

    /**
     * "Allow MyISAM" property name.
     * @var string
     */
    public const PROPERTY_ALLOW_MYISAM = 'allow_myisam';

    /**
     * "Allow datetime" property name.
     * @var string
     */
    public const PROPERTY_ALLOW_DATETIME = 'allow_datetime';

    /**
     * "Allow signed integers in primary/foreign keys" property name.
     * @var string
     */
    public const PROPERTY_ALLOW_SIGNED_KEYS = 'allow_signed_keys';

    protected static $notable = true;


    public static function getTypeName($nb = 0)
    {
        return _n('SQL replica', 'SQL replicas', $nb);
    }


    /**
     * Create GLPI main configuration file
     *
     * @since 9.1
     *
     * @param string  $host                      The DB host
     * @param string  $user                      The DB user
     * @param string  $password                  The DB password
     * @param string  $dbname                    The name of the DB
     * @param boolean $use_timezones             Flag that indicates if timezones usage should be activated
     * @param boolean $log_deprecation_warnings  Flag that indicates if DB deprecation warnings should be logged
     * @param boolean $use_utf8mb4               Flag that indicates if utf8mb4 charset/collation should be used
     * @param boolean $allow_myisam              Flag that indicates if MyISAM engine usage should be allowed
     * @param boolean $allow_datetime            Flag that indicates if datetime fields usage should be allowed
     * @param boolean $allow_signed_keys         Flag that indicates if signed integers in primary/foreign keys usage should be allowed
     * @param string  $config_dir
     *
     * @return boolean
     */
    public static function createMainConfig(
        string $host,
        string $user,
        string $password,
        string $dbname,
        bool $use_timezones = false,
        bool $log_deprecation_warnings = false,
        bool $use_utf8mb4 = false,
        bool $allow_myisam = true,
        bool $allow_datetime = true,
        bool $allow_signed_keys = true,
        string $config_dir = GLPI_CONFIG_DIR
    ): bool {

        $properties = [
            'dbhost'     => $host,
            'dbuser'     => $user,
            'dbpassword' => rawurlencode($password),
            'dbdefault'  => $dbname,
        ];
        if ($use_timezones) {
            $properties[self::PROPERTY_USE_TIMEZONES] = true;
        }
        if ($log_deprecation_warnings) {
            $properties[self::PROPERTY_LOG_DEPRECATION_WARNINGS] = true;
        }
        if ($use_utf8mb4) {
            $properties[self::PROPERTY_USE_UTF8MB4] = true;
        }
        if (!$allow_myisam) {
            $properties[self::PROPERTY_ALLOW_MYISAM] = false;
        }
        if (!$allow_datetime) {
            $properties[self::PROPERTY_ALLOW_DATETIME] = false;
        }
        if (!$allow_signed_keys) {
            $properties[self::PROPERTY_ALLOW_SIGNED_KEYS] = false;
        }

        $config_str = '<?php' . "\n" . 'class DB extends DBmysql {' . "\n";
        foreach ($properties as $name => $value) {
            $config_str .= sprintf('   public $%s = %s;', $name, var_export($value, true)) . "\n";
        }
        $config_str .= '}' . "\n";

        return Toolbox::writeConfig('config_db.php', $config_str, $config_dir);
    }


    /**
     * Change a variable value in config(s) file.
     *
     * @param string $name
     * @param string $value
     * @param bool   $update_slave
     * @param string $config_dir
     *
     * @return boolean
     *
     * @since 10.0.0
     */
    public static function updateConfigProperty($name, $value, $update_slave = true, string $config_dir = GLPI_CONFIG_DIR): bool
    {
        return self::updateConfigProperties([$name => $value], $update_slave, $config_dir);
    }


    /**
     * Change variables value in config(s) file.
     *
     * @param array  $properties
     * @param bool   $update_slave
     * @param string $config_dir
     *
     * @return boolean
     *
     * @since 10.0.0
     */
    public static function updateConfigProperties(array $properties, $update_slave = true, string $config_dir = GLPI_CONFIG_DIR): bool
    {
        $main_config_file = 'config_db.php';
        $slave_config_file = 'config_db_slave.php';

        if (!file_exists($config_dir . '/' . $main_config_file)) {
            return false;
        }

        $files = [$main_config_file];
        if ($update_slave && file_exists($config_dir . '/' . $slave_config_file)) {
            $files[] = $slave_config_file;
        }

        foreach ($files as $file) {
            if (($config_str = file_get_contents($config_dir . '/' . $file)) === false) {
                return false;
            }

            foreach ($properties as $name => $value) {
                if ($name === 'password') {
                    $value = rawurlencode($value);
                }

                $pattern = '/(?<line>' . preg_quote('$' . $name, '/') . '\s*=\s*(?<value>[^;]+)\s*;)' . '/';

                $matches = [];
                if (preg_match($pattern, $config_str, $matches)) {
                   // Property declaration is located in config file, we have to update it.
                    $updated_line = str_replace($matches['value'], var_export($value, true), $matches['line']);
                    $config_str = str_replace($matches['line'], $updated_line, $config_str);
                } else {
                   // Property declaration is not located in config file, we have to add it.
                    $ending_bracket_pos = mb_strrpos($config_str, '}');
                    $config_str = mb_substr($config_str, 0, $ending_bracket_pos)
                    . sprintf('   public $%s = %s;', $name, var_export($value, true)) . "\n"
                    . mb_substr($config_str, $ending_bracket_pos);
                }
            }

            if (!Toolbox::writeConfig($file, $config_str, $config_dir)) {
                return false;
            }
        }

        return true;
    }


    /**
     * Create slave DB configuration file
     *
     * @param string  $host                      The DB host
     * @param string  $user                      The DB user
     * @param string  $password                  The DB password
     * @param string  $dbname                    The name of the DB
     * @param boolean $use_timezones             Flag that indicates if timezones usage should be activated
     * @param boolean $log_deprecation_warnings  Flag that indicates if DB deprecation warnings should be logged
     * @param boolean $use_utf8mb4               Flag that indicates if utf8mb4 charset/collation should be used
     * @param boolean $allow_myisam              Flag that indicates if MyISAM engine usage should be allowed
     * @param boolean $allow_datetime            Flag that indicates if datetime fields usage should be allowed
     * @param boolean $allow_signed_keys         Flag that indicates if signed integers in primary/foreign keys usage should be allowed
     * @param string  $config_dir
     *
     * @return boolean for success
     **/
    public static function createSlaveConnectionFile(
        string $host,
        string $user,
        string $password,
        string $dbname,
        bool $use_timezones = false,
        bool $log_deprecation_warnings = false,
        bool $use_utf8mb4 = false,
        bool $allow_myisam = true,
        bool $allow_datetime = true,
        bool $allow_signed_keys = true,
        string $config_dir = GLPI_CONFIG_DIR
    ): bool {

       // Explode host into array (multiple values separated by a space char)
        $host = trim($host);
        if (strpos($host, ' ')) {
            $host = explode(' ', $host);
        }

        $properties = [
            'slave'      => true,
            'dbhost'     => $host,
            'dbuser'     => $user,
            'dbpassword' => rawurlencode($password),
            'dbdefault'  => $dbname,
        ];
        if ($use_timezones) {
            $properties[self::PROPERTY_USE_TIMEZONES] = true;
        }
        if ($log_deprecation_warnings) {
            $properties[self::PROPERTY_LOG_DEPRECATION_WARNINGS] = true;
        }
        if ($use_utf8mb4) {
            $properties[self::PROPERTY_USE_UTF8MB4] = true;
        }
        if (!$allow_myisam) {
            $properties[self::PROPERTY_ALLOW_MYISAM] = false;
        }
        if (!$allow_datetime) {
            $properties[self::PROPERTY_ALLOW_DATETIME] = false;
        }
        if (!$allow_signed_keys) {
            $properties[self::PROPERTY_ALLOW_SIGNED_KEYS] = false;
        }

        $config_str = '<?php' . "\n" . 'class DBSlave extends DBmysql {' . "\n";
        foreach ($properties as $name => $value) {
            $config_str .= sprintf('   public $%s = %s;', $name, var_export($value, true)) . "\n";
        }
        $config_str .= '}' . "\n";

        return Toolbox::writeConfig('config_db_slave.php', $config_str, $config_dir);
    }


    /**
     * Indicates is the DB replicate is active or not
     *
     * @return true if active / false if not active
     **/
    public static function isDBSlaveActive()
    {
        return file_exists(GLPI_CONFIG_DIR . "/config_db_slave.php");
    }


    /**
     * Read slave DB configuration file
     *
     * @param integer $choice  Host number (default NULL)
     *
     * @return DBmysql object
     **/
    public static function getDBSlaveConf($choice = null)
    {

        if (self::isDBSlaveActive()) {
            include_once(GLPI_CONFIG_DIR . "/config_db_slave.php");
            return new DBSlave($choice);
        }
    }


    /**
     * Create a default slave DB configuration file
     **/
    public static function createDBSlaveConfig()
    {
        global $DB;
        self::createSlaveConnectionFile(
            "localhost",
            "glpi",
            "glpi",
            "glpi",
            $DB->use_timezones,
            $DB->log_deprecation_warnings,
            $DB->use_utf8mb4,
            $DB->allow_myisam,
            $DB->allow_datetime,
            $DB->allow_signed_keys
        );
    }


    /**
     * Save changes to the slave DB configuration file
     *
     * @param $host
     * @param $user
     * @param $password
     * @param $DBname
     **/
    public static function saveDBSlaveConf($host, $user, $password, $DBname)
    {
        global $DB;
        self::createSlaveConnectionFile(
            $host,
            $user,
            $password,
            $DBname,
            $DB->use_timezones,
            $DB->log_deprecation_warnings,
            $DB->use_utf8mb4,
            $DB->allow_myisam,
            $DB->allow_datetime,
            $DB->allow_signed_keys
        );
    }


    /**
     * Delete slave DB configuration file
     */
    public static function deleteDBSlaveConfig()
    {
        unlink(GLPI_CONFIG_DIR . "/config_db_slave.php");
    }


    /**
     * Switch database connection to slave
     **/
    public static function switchToSlave()
    {
        global $DB;

        if (self::isDBSlaveActive()) {
            include_once(GLPI_CONFIG_DIR . "/config_db_slave.php");
            $DB = new DBSlave();
            return $DB->connected;
        }
        return false;
    }


    /**
     * Switch database connection to master
     **/
    public static function switchToMaster()
    {
        global $DB;

        $DB = new DB();
        return $DB->connected;
    }


    /**
     * Get Connection to slave, if exists,
     * and if configured to be used for read only request
     *
     * @return DBmysql object
     **/
    public static function getReadConnection()
    {
        global $DB, $CFG_GLPI;

        if (
            $CFG_GLPI['use_slave_for_search']
            && !$DB->isSlave()
            && self::isDBSlaveActive()
        ) {
            include_once(GLPI_CONFIG_DIR . "/config_db_slave.php");
            $DBread = new DBSlave();

            if ($DBread->connected) {
                $sql = "SELECT MAX(`id`) AS maxid
                    FROM `glpi_logs`";

                switch ($CFG_GLPI['use_slave_for_search']) {
                    case 3: // If synced or read-only account
                        if (Session::isReadOnlyAccount()) {
                             return $DBread;
                        }
                      // nobreak;

                    case 1: // If synced (all changes)
                        $slave  = $DBread->request($sql)->current();
                        $master = $DB->request($sql)->current();
                        if (
                            isset($slave['maxid']) && isset($master['maxid'])
                            && ($slave['maxid'] == $master['maxid'])
                        ) {
                        // Latest Master change available on Slave
                            return $DBread;
                        }
                        break;

                    case 2: // If synced (current user changes or profile in read only)
                        if (!isset($_SESSION['glpi_maxhistory'])) {
                          // No change yet
                            return $DBread;
                        }
                        $slave  = $DBread->request($sql)->current();
                        if (
                            isset($slave['maxid'])
                            && ($slave['maxid'] >= $_SESSION['glpi_maxhistory'])
                        ) {
                         // Latest current user change avaiable on Slave
                            return $DBread;
                        }
                        break;

                    default: // Always
                        return $DBread;
                }
            }
        }
        return $DB;
    }


    /**
     *  Establish a connection to a mysql server (main or replicate)
     *
     * @param boolean $use_slave try to connect to slave server first not to main server
     * @param boolean $required  connection to the specified server is required
     *                           (if connection failed, do not try to connect to the other server)
     * @param boolean $display   display error message (true by default)
     *
     * @return boolean True if successfull, false otherwise
     **/
    public static function establishDBConnection($use_slave, $required, $display = true)
    {
        global $DB;

        $DB  = null;
        $res = false;

       // First standard config : no use slave : try to connect to master
        if (!$use_slave) {
            $res = self::switchToMaster();
        }

       // If not already connected to master due to config or error
        if (!$res) {
           // No DB slave : first connection to master give error
            if (!self::isDBSlaveActive()) {
               // Slave wanted but not defined -> use master
               // Ignore $required when no slave configured
                if ($use_slave) {
                    $res = self::switchToMaster();
                }
            } else { // Slave DB configured
               // Try to connect to slave if wanted
                if ($use_slave) {
                    $res = self::switchToSlave();
                }

               // No connection to 'mandatory' server
                if (!$res && !$required) {
                   //Try to establish the connection to the other mysql server
                    if ($use_slave) {
                        $res = self::switchToMaster();
                    } else {
                        $res = self::switchToSlave();
                    }
                    if ($res) {
                        $DB->first_connection = false;
                    }
                }
            }
        }

       // Display error if needed
        if (!$res && $display) {
            self::displayMySQLError();
        }
        return $res;
    }


    /**
     * Get delay between slave and master
     *
     * @param integer $choice  Host number (default NULL)
     *
     * @return integer
     **/
    public static function getReplicateDelay($choice = null)
    {

        include_once(GLPI_CONFIG_DIR . "/config_db_slave.php");
        return (int) (self::getHistoryMaxDate(new DB())
                    - self::getHistoryMaxDate(new DBSlave($choice)));
    }


    /**
     *  Get history max date of a GLPI DB
     *
     * @param DBMysql $DBconnection DB connection used
     *
     * @return int|mixed|null
     */
    public static function getHistoryMaxDate($DBconnection)
    {

        if ($DBconnection->connected) {
            $result = $DBconnection->query("SELECT UNIX_TIMESTAMP(MAX(`date_mod`)) AS max_date
                                         FROM `glpi_logs`");
            if ($DBconnection->numrows($result) > 0) {
                 return $DBconnection->result($result, 0, "max_date");
            }
        }
        return 0;
    }


    /**
     *  Display a common mysql connection error
     **/
    public static function displayMySQLError()
    {
        global $DB;

        $error = $DB instanceof DBmysql ? $DB->error : 1;
        switch ($error) {
            case 2:
                $en_msg = "Use of mysqlnd driver is required for exchanges with the MySQL server.";
                $fr_msg = "L'utilisation du driver mysqlnd est requise pour les échanges avec le serveur MySQL.";
                break;
            case 1:
            default:
                $fr_msg = "Le serveur Mysql est inaccessible. Vérifiez votre configuration.";
                $en_msg = "A link to the SQL server could not be established. Please check your configuration.";
                break;
        }

        if (!isCommandLine()) {
            Html::nullHeader("Mysql Error", '');
            echo "<div class='center'><p class ='b'>$en_msg</p><p class='b'>$fr_msg</p></div>";
            Html::nullFooter();
        } else {
            echo "$en_msg\n$fr_msg\n";
        }

        die(1);
    }


    /**
     * @param $name
     **/
    public static function cronInfo($name)
    {

        return ['description' => __('Check the SQL replica'),
            'parameter'   => __('Max delay between main and replica (minutes)')
        ];
    }


    /**
     * Cron process to check DB replicate state
     *
     * @param CronTask $task to log and get param
     *
     * @return integer
     **/
    public static function cronCheckDBreplicate(CronTask $task)
    {
        global $DB;

       //Lauch cron only is :
       // 1 the master database is avalaible
       // 2 the slave database is configurated
        if (!$DB->isSlave() && self::isDBSlaveActive()) {
            $DBslave = self::getDBSlaveConf();
            if (is_array($DBslave->dbhost)) {
                $hosts = $DBslave->dbhost;
            } else {
                $hosts = [$DBslave->dbhost];
            }

            foreach ($hosts as $num => $name) {
                $diff = self::getReplicateDelay($num);

               // Quite strange, but allow simple stat
                $task->addVolume($diff);
                if ($diff > 1000000000) { // very large means slave is disconnect
                    $task->log(sprintf(__s("SQL server: %s can't connect to the database"), $name));
                } else {
                                  //TRANS: %1$s is the server name, %2$s is the time
                    $task->log(sprintf(
                        __('SQL server: %1$s, difference between main and replica: %2$s'),
                        $name,
                        Html::timestampToString($diff, true)
                    ));
                }

                if ($diff > ($task->fields['param'] * 60)) {
                   //Raise event if replicate is not synchronized
                    $options = ['diff'        => $diff,
                        'name'        => $name,
                        'entities_id' => 0
                    ]; // entity to avoid warning in getReplyTo
                    NotificationEvent::raiseEvent('desynchronization', new self(), $options);
                }
            }
            return 1;
        }
        return 0;
    }


    /**
     * Display in HTML, delay between master and slave
     * 1 line per slave is multiple
     **/
    public static function showAllReplicateDelay()
    {

        $DBslave = self::getDBSlaveConf();

        if (is_array($DBslave->dbhost)) {
            $hosts = $DBslave->dbhost;
        } else {
            $hosts = [$DBslave->dbhost];
        }

        foreach ($hosts as $num => $name) {
            $diff = self::getReplicateDelay($num);
           //TRANS: %s is namez of server Mysql
            printf(__('%1$s: %2$s'), __('SQL server'), $name);
            echo " - ";
            if ($diff > 1000000000) {
                echo __("can't connect to the database") . "<br>";
            } else if ($diff) {
                printf(
                    __('%1$s: %2$s') . "<br>",
                    __('Difference between main and replica'),
                    Html::timestampToString($diff, 1)
                );
            } else {
                printf(__('%1$s: %2$s') . "<br>", __('Difference between main and replica'), __('None'));
            }
        }
    }


    /**
     * @param $width
     **/
    public function showSystemInformations($width)
    {

       // No need to translate, this part always display in english (for copy/paste to forum)

        echo "<tr class='tab_bg_2'><th class='section-header'>" . self::getTypeName(Session::getPluralNumber()) . "</th></tr>";

        echo "<tr class='tab_bg_1'><td><pre class='section-content'>\n&nbsp;\n";
        if (self::isDBSlaveActive()) {
            echo "Active\n";
            self::showAllReplicateDelay();
        } else {
            echo "Not active\n";
        }
        echo "\n</pre></td></tr>";
    }


    /**
     * Enable or disable db replication check cron task
     *
     * @param boolean $enable Enable or disable cron task (true by default)
     **/
    public static function changeCronTaskStatus($enable = true)
    {

        $cron           = new CronTask();
        $cron->getFromDBbyName('DBConnection', 'CheckDBreplicate');
        $input = [
            'id'    => $cron->fields['id'],
            'state' => ($enable ? 1 : 0)
        ];
        $cron->update($input);
    }


    /**
     * Set charset to use for DB connection handler.
     *
     * @param mysqli $dbh
     * @param bool   $use_utf8mb4
     *
     * @return void
     *
     * @since 10.0.0
     */
    public static function setConnectionCharset(mysqli $dbh, bool $use_utf8mb4): void
    {
        $charset = $use_utf8mb4 ? 'utf8mb4' : 'utf8';

        $dbh->set_charset($charset);

       // The mysqli::set_charset function will make COLLATE to be defined to the default one for used charset.
       // As we are not using the default COLLATE, we have to define it using `SET NAMES` query.
        switch ($charset) {
            case 'utf8':
                // Legacy charset, should be deprecated in next major version.
                $dbh->query("SET NAMES 'utf8' COLLATE 'utf8_unicode_ci';");
                break;
            case 'utf8mb4':
                $dbh->query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';");
                break;
            default:
                throw new \Exception(sprintf('Charset "%s" is not supported.', $charset));
            break;
        }
    }

    /**
     * Return default charset to use.
     *
     * @return string
     *
     * @since 10.0.0
     */
    public static function getDefaultCharset(): string
    {
        global $DB;

        if ($DB instanceof DBmysql && !$DB->use_utf8mb4) {
            return 'utf8';
        }

        return 'utf8mb4';
    }

    /**
     * Return default collation to use.
     *
     * @return string
     *
     * @since 10.0.0
     */
    public static function getDefaultCollation(): string
    {
        global $DB;

        if ($DB instanceof DBmysql && !$DB->use_utf8mb4) {
            return 'utf8_unicode_ci';
        }

        return 'utf8mb4_unicode_ci';
    }

    /**
     * Return default sign option to use for primary (and foreign) key fields.
     *
     * @return string
     *
     * @since 10.0.0
     */
    public static function getDefaultPrimaryKeySignOption(): string
    {
        global $DB;

        if ($DB instanceof DBmysql && $DB->allow_signed_keys) {
            return '';
        }

        return 'unsigned';
    }

    /**
     * Return a DB instance using given connection parameters.
     *
     * @param string $host
     * @param string $user
     * @param string $password
     * @param string $dbname
     *
     * @return DBmysql
     */
    public static function getDbInstanceUsingParameters(string $host, string $user, string $password, string $dbname): DBmysql
    {
        return new class ($host, $user, $password, $dbname) extends DBmysql {
            public function __construct($host, $user, $password, $dbname)
            {
                  $this->dbhost     = $host;
                  $this->dbuser     = $user;
                  $this->dbpassword = $password;
                  $this->dbdefault  = $dbname;
                  parent::__construct();
            }
        };
    }
}
