<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}


/**
 *  Database class for Mysql
 */
class DBConnection extends CommonDBTM {

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][800];
   }


   /**
    * Create slave DB configuration file
    *
    * @param host the slave DB host
    * @param user the slave DB user
    * @param password the slave DB password
    * @param DBname the name of the slave DB
    */
   static function createSlaveConnectionFile($host, $user, $password, $DBname) {

      $DB_str = "<?php \n class DBSlave extends DBmysql { \n var \$slave = true; \n var \$dbhost = '" .
                  $host . "'; \n var \$dbuser = '" . $user . "'; \n var \$dbpassword= '" .
                  rawurlencode($password) . "'; \n var \$dbdefault = '" . $DBname . "'; \n } \n ?>";
      $fp = fopen(GLPI_CONFIG_DIR . "/config_db_slave.php", 'wt');
      if ($fp) {
         $fw = fwrite($fp, $DB_str);
         fclose($fp);
         return true;
      }
      return false;
   }


   /**
    * Indicates is the DB replicate is active or not
    *
    * @return true if active / false if not active
    */
   static function isDBSlaveActive() {
      return file_exists(GLPI_CONFIG_DIR . "/config_db_slave.php");
   }


   /**
    * Read slave DB configuration file
    */
   static function getDBSlaveConf() {

      if (DBConnection::isDBSlaveActive()) {
         include_once (GLPI_CONFIG_DIR . "/config_db_slave.php");
         return new DBSlave;
      }
   }


   /**
    * Create a default slave DB configuration file
    */
   static function createDBSlaveConfig() {

      DBConnection::createSlaveConnectionFile("localhost", "glpi", "glpi", "glpi");
   }


   /**
    * Save changes to the slave DB configuration file
    */
   static function saveDBSlaveConf($host, $user, $password, $DBname) {

      DBConnection::createSlaveConnectionFile($host, $user, $password, $DBname);
   }


   /**
    * Delete slave DB configuration file
    */
   static function deleteDBSlaveConfig() {

      unlink(GLPI_CONFIG_DIR . "/config_db_slave.php");
   }


   /**
    * Switch database connection to slave
    */
   static function switchToSlave() {
      global $DB;

      if (DBConnection::isDBSlaveActive()) {
         include_once (GLPI_CONFIG_DIR . "/config_db_slave.php");
         $DB = new DBSlave;
         return $DB->connected;
      }
      return false;
   }


   /**
    * Switch database connection to master
    */
   static function switchToMaster() {
      global $DB;

      $DB = new DB;
      return $DB->connected;
   }


   /**
    * Get Connection to slave, if exists,
    * and if configured to be used for read only request
    *
    * @return DBmysql object
    */
   static function getReadConnection() {
      global $DB, $CFG_GLPI;

      if ($CFG_GLPI['use_slave_for_search']
          && !$DB->isSlave()
          && DBConnection::isDBSlaveActive()) {
         include_once (GLPI_CONFIG_DIR . "/config_db_slave.php");
         $DBread = new DBSlave();

         if ($DBread->connected) {
            return $DBread;
         }
      }
      return $DB;
   }


   /**
    *  Establish a connection to a mysql server (main or replicate)
    *
    * @param $use_slave try to connect to slave server first not to main server
    * @param $required connection to the specified server is required
    *                  (if connection failed, do not try to connect to the other server)
    * @param $display display error message
    */
   static function establishDBConnection($use_slave, $required, $display=true) {
      global $DB;

      $DB  = null;
      $res = false;

      // First standard config : no use slave : try to connect to master
      if (!$use_slave) {
         $res = DBConnection::switchToMaster();
      }

      // If not already connected to master due to config or error
      if (!$res) {
         // No DB slave : first connection to master give error
         if (!DBConnection::isDBSlaveActive()) {
            // Slave wanted but not defined -> use master
            // Ignore $required when no slave configured
            if ($use_slave) {
               $res = DBConnection::switchToMaster();
            }

         // Slave DB configured
         } else {
            // Try to connect to slave if wanted
            if ($use_slave) {
               $res = DBConnection::switchToSlave();
            }
            // No connection to 'mandatory' server
            if (!$res && !$required) {
               //Try to establish the connection to the other mysql server
               if ($use_slave) {
                  $res = DBConnection::switchToMaster();
               } else {
                  $res = DBConnection::switchToSlave();
               }
               if ($res) {
                  $DB->first_connection=false;
               }
            }
         }
      }
      // Display error if needed
      if (!$res && $display) {
         DBConnection::displayMySQLError();
      }
      return $res;
   }


   /**
    *  Get delay between slave and master
    */
   static function getReplicateDelay() {

      include_once (GLPI_CONFIG_DIR . "/config_db_slave.php");
      return (int) (DBConnection::getHistoryMaxDate(new DB) - DBConnection::getHistoryMaxDate(new DBSlave));
   }


   /**
    *  Get history max date of a GLPI DB
    *
    * @param $DBconnection DB conneciton used
    */
   static function getHistoryMaxDate($DBconnection) {

      if ($DBconnection->connected) {
         $result = $DBconnection->query("SELECT UNIX_TIMESTAMP(MAX(date_mod)) AS max_date
                                         FROM glpi_logs");
         if ($DBconnection->numrows($result) > 0) {
            return $DBconnection->result($result, 0, "max_date");
         }
      }
      return 0;
   }


   /**
    *  Display a common mysql connection error
    */
   static function displayMySQLError() {

      nullHeader("Mysql Error", '');

      if (!isCommandLine()) {
         echo "<div class='center'><p><strong>
                A link to the Mysql server could not be established. Please check your configuration.
                </strong></p><p><strong>
                Le serveur Mysql est inaccessible. V&eacute;rifiez votre configuration</strong></p>
               </div>";
      } else {
         echo "A link to the Mysql server could not be established. Please check your configuration.\n";
         echo "Le serveur Mysql est inaccessible. V&eacute;rifiez votre configuration\n";
      }

      nullFooter();
      die();
   }


   static function cronInfo($name) {
      global $LANG;

      return array('description' => $LANG['crontask'][10],
                   'parameter'   => $LANG['setup'][806]);
   }


   /**
    *  Cron process to check DB replicate state
    *
    * @param $task to log and get param
    */
   static function cronCheckDBreplicate($task) {
      global $DB;

      //Lauch cron only is :
      // 1 the master database is avalaible
      // 2 the slave database is configurated
      if (!$DB->isSlave() && DBConnection::isDBSlaveActive()) {
         $diff = DBConnection::getReplicateDelay();

         // Quite strange, but allow simple stat
         $task->setVolume($diff);

         if ($diff > ($task->fields['param']*60)) {
            //Raise event if replicate is not synchronized
            NotificationEvent::raiseEvent('desynchronization', new self());
         }
         return 1;
      }
      return 0;
   }


   function showSystemInformations($width) {
      global $LANG;

      echo "\n</pre></td>";
      echo "</tr><tr class='tab_bg_2'><th>" . $LANG['setup'][800] . "</th></tr>";
      echo "<tr class='tab_bg_1'><td><pre>\n&nbsp;\n";
      if (DBConnection::isDBSlaveActive()) {
         echo $LANG['common'][60]."&nbsp;: ".$LANG['choice'][1]."\n";

         $task = new CronTask;
         $task->getFromDBbyName('DBConnection','CheckDBreplicate');
         $diff = DBConnection::getReplicateDelay();
         echo $LANG['setup'][803]."&nbsp;: ".timestampToString($diff)."\n";

      } else {
         echo $LANG['common'][60]."&nbsp;: ".$LANG['choice'][0]."\n";
      }
      echo "\n</pre></td></tr>";
   }


   /**
    * Enable or disable db replication check cron task
    *
    * @param enable of disable cron task
    */
   static function changeCronTaskStatus($enable=true) {

      $cron = new CronTask;
      $cron->getFromDBbyName('DBConnection', 'CheckDBreplicate');
      $input['id']    = $cron->fields['id'];
      $input['state'] = ($enable?1:0);
      $cron->update($input);
   }
}
?>