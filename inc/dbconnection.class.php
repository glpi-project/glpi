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

use Glpi\Database\AbstractDatabase;
use Glpi\DatabaseFactory;
use Symfony\Component\Yaml\Yaml;

/**
 *  Database class for Mysql
**/
class DBConnection extends CommonDBTM {

   static protected $notable = true;


   static function getTypeName($nb = 0) {
      return _n('SQL replica', 'SQL replicas', $nb);
   }


   /**
    * Create GLPI main configuration file
    *
    * @since 9.1
    *
    * @param string $driver   Database driver
    * @param string $host     Database host
    * @param string $user     Dataabse user name
    * @param string $password Database user password
    * @param string $dbname   Database name
    *
    * @return boolean
    *
   **/
   static function createMainConfig($driver, $host, $user, $password, $dbname) {
      $yaml = Yaml::dump([
         'driver' => $driver,
         'host'   => $host,
         'user'   => $user,
         'pass'   => $password,
         'dbname' => $dbname
      ]);
      return Toolbox::writeConfig('db.yaml', $yaml);
   }


   /**
    * Create slave DB configuration file
    *
    * @param string $driver   Database driver
    * @param string $host     Database host
    * @param string $user     Dataabse user name
    * @param string $password Database user password
    * @param string $dbname   Database name
    *
    * @return boolean
   **/
   static function createSlaveConnectionFile($driver, $host, $user = null, $password = null, $dbname = null) {
      $conf = [
         'driver' => $driver,
         'host'   => trim($host)
      ];

      if ($user !== null) {
         $conf['user'] = $user;
      }

      if ($password !== null) {
         $conf['pass'] = $password;
      }

      if ($dbname !== null) {
         $conf['dbname'] = $dbname;
      }

      if (strpos($conf['host'], ' ')) {
         $hosts = explode(' ', $conf['host']);
         $conf['host'] = [];
         foreach ($hosts as $host) {
            if (!empty($host)) {
               $conf['host'][] = $host;
            }
         }
         if (!count($conf['host'])) {
            // no host configured
            Toolbox::logerror('No slave host configured!');
            return false;
         }
      }

      $yaml = Yaml::dump($conf);
      return Toolbox::writeConfig('db.slave.yaml', $yaml);
   }


   /**
    * Indicates is the DB replicate is active or not
    *
    * @return true if active / false if not active
   **/
   static function isDBSlaveActive() {
      return file_exists(GLPI_CONFIG_DIR . "/db.slave.yaml");
   }


   /**
    * Read slave DB configuration file
    *
    * @param integer $server host number (default NULL)
    *
    * @return AbstractDatabase object
   **/
   static function getDBSlaveConf($server = null): AbstractDatabase {

      if (self::isDBSlaveActive()) {
         return DatabaseFactory::create(null, true, $server);
      }
   }


   /**
    * Create a default slave DB configuration file
   **/
   static function createDBSlaveConfig() {
      self::createSlaveConnectionFile("localhost", "glpi", "glpi", "glpi");
   }


   /**
    * Save changes to the slave DB configuration file
    *
    * @param $host
    * @param $user
    * @param $password
    * @param $DBname
   **/
   static function saveDBSlaveConf($host, $user, $password, $DBname) {
      self::createSlaveConnectionFile($host, $user, $password, $DBname);
   }


   /**
    * Delete slave DB configuration file
    */
   static function deleteDBSlaveConfig() {
      unlink(GLPI_CONFIG_DIR . "/db.slave.yaml");
   }


   /**
    * Switch database connection to slave
   **/
   static function switchToSlave() {
      global $DB;

      if (self::isDBSlaveActive()) {
         $DB = DatabaseFactory::create(null, true);
         return $DB->isConnected();
      }
      return false;
   }


   /**
    * Switch database connection to master
   **/
   static function switchToMaster() {
      global $DB;

      $DB = DatabaseFactory::create();
      return $DB->isConnected();
   }


   /**
    * Get Connection to slave, if exists,
    * and if configured to be used for read only request
    *
    * @return AbstractDatabase object
   **/
   static function getReadConnection() {
      global $DB, $CFG_GLPI;

      if ($CFG_GLPI['use_slave_for_search']
          && !$DB->isSlave()
          && self::isDBSlaveActive()) {

         $DBread = DatabaseFactory::create(null, true);

         if ($DBread->isConnected()) {
            $sql = [
               'SELECT' => ['MAX' => 'id AS maxid'],
               'FROM'   => 'glpi_logs'
            ];

            switch ($CFG_GLPI['use_slave_for_search']) {
               case 3 : // If synced or read-only account
                  if (Session::isReadOnlyAccount()) {
                     return $DBread;
                  }
                  // nobreak;

               case 1 : // If synced (all changes)
                  $slave  = $DBread->request($sql)->next();
                  $master = $DB->request($sql)->next();
                  if (isset($slave['maxid']) && isset($master['maxid'])
                      && ($slave['maxid'] == $master['maxid'])) {
                     // Latest Master change available on Slave
                     return $DBread;
                  }
                  break;

               case 2 : // If synced (current user changes or profile in read only)
                  if (!isset($_SESSION['glpi_maxhistory'])) {
                     // No change yet
                     return $DBread;
                  }
                  $slave  = $DBread->request($sql)->next();
                  if (isset($slave['maxid'])
                      && ($slave['maxid'] >= $_SESSION['glpi_maxhistory'])) {
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
   static function establishDBConnection($use_slave, $required, $display = true) {
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
    * @param $choice integer, host number (default NULL)
    *
    * @return integer
   **/
   static function getReplicateDelay($choice = null) {

      $db = DatabaseFactory::create();
      $sdb = DatabaseFactory::create(null, true, $choice);
      return (int) (self::getHistoryMaxDate($db)
                    - self::getHistoryMaxDate($sdb));
   }


   /**
    *  Get history max date of a GLPI DB
    *
    * @param $DBconnection DB conneciton used
   **/
   static function getHistoryMaxDate($DBconnection) {

      if ($DBconnection->isConnected()) {
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
   static function displayMySQLError() {

      if (!isCommandLine()) {
         Html::nullHeader("Mysql Error", '');
         echo "<div class='center'><p class ='b'>
                A link to the SQL server could not be established. Please check your configuration.
                </p><p class='b'>
                Le serveur Mysql est inaccessible. Vérifiez votre configuration</p>
               </div>";
         Html::nullFooter();
      } else {
         echo "A link to the SQL server could not be established. Please check your configuration.\n";
         echo "Le serveur Mysql est inaccessible. Vérifiez votre configuration\n";
      }

      die(1);
   }


   /**
    * @param $name
   **/
   static function cronInfo($name) {

      return ['description' => __('Check the SQL replica'),
                   'parameter'   => __('Max delay between master and slave (minutes)')];
   }


   /**
    * Cron process to check DB replicate state
    *
    * @param Crontask $task to log and get param
    *
    * @return integer
   **/
   static function cronCheckDBreplicate(Crontask $task) {
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
               $task->log(sprintf(__('SQL server: %1$s, difference between master and slave: %2$s'),
                                  $name, Html::timestampToString($diff, true)));
            }

            if ($diff > ($task->fields['param']*60)) {
               //Raise event if replicate is not synchronized
               $options = ['diff'        => $diff,
                                'name'        => $name,
                                'entities_id' => 0]; // entity to avoid warning in getReplyTo
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
   static function showAllReplicateDelay() {

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
            printf(
               __('%1$s: %2$s')."<br>",
               __('Difference between master and slave'),
               ($diff ? Html::timestampToString($diff, 1) : __('None'))
            );
         }
      }
   }


   /**
    * @param $width
   **/
   function showSystemInformations($width) {

      // No need to translate, this part always display in english (for copy/paste to forum)

      echo "<tr class='tab_bg_2'><th>".self::getTypeName(Session::getPluralNumber())."</th></tr>";

      echo "<tr class='tab_bg_1'><td><pre>\n&nbsp;\n";
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
    * @param enable of disable cron task (true by default)
   **/
   static function changeCronTaskStatus($enable = true) {

      $cron           = new CronTask();
      $cron->getFromDBbyName('DBConnection', 'CheckDBreplicate');
      $input = [
         'id'    => $cron->fields['id'],
         'state' => ($enable ? 1 : 0)
      ];
      $cron->update($input);
   }

}
