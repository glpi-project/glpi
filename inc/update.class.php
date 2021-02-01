<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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
 *  Update class
**/
class Update extends CommonGLPI {
   private $args = [];
   private $DB;
   private $migration;
   private $version;
   private $dbversion;
   private $language;

   /**
    * Constructor
    *
    * @param object $DB   Database instance
    * @param array  $args Command line arguments; default to empty array
    */
   public function __construct($DB, $args = []) {
      $this->DB = $DB;
      $this->args = $args;
   }

   /**
    * Initialize session for update
    *
    * @return void
    */
   public function initSession() {
      if (is_writable(GLPI_SESSION_DIR)) {
         Session::setPath();
      } else {
         if (isCommandLine()) {
            die("Can't write in ".GLPI_SESSION_DIR."\n");
         }
      }
      Session::start();

      if (isCommandLine()) {
         // Init debug variable
         $_SESSION = ['glpilanguage' => (isset($this->args['lang']) ? $this->args['lang'] : 'en_GB')];
         $_SESSION["glpi_currenttime"] = date("Y-m-d H:i:s");
      }

      // Init debug variable
      // Only show errors
      Toolbox::setDebugMode(Session::DEBUG_MODE, 0, 0, 1);
   }

   /**
    * Get current values (versions, lang, ...)
    *
    * @return array
    */
   public function getCurrents() {
      $currents = [];
      $DB = $this->DB;

      if (!$DB->tableExists('glpi_config') && !$DB->tableExists('glpi_configs')) {
         //very, very old version!
         $currents = [
            'version'   => '0.1',
            'dbversion' => '0.1',
            'language'  => 'en_GB'
         ];
      } else if (!$DB->tableExists("glpi_configs")) {
         // < 0.78
         // Get current version
         $result = $DB->request([
            'SELECT' => ['version', 'language'],
            'FROM'   => 'glpi_config'
         ])->next();

         $currents['version']    = trim($result['version']);
         $currents['dbversion']  = $currents['version'];
         $currents['language']   = trim($result['language']);
      } else if ($DB->fieldExists('glpi_configs', 'version')) {
         // < 0.85
         // Get current version and language
         $result = $DB->request([
            'SELECT' => ['version', 'language'],
            'FROM'   => 'glpi_configs'
         ])->next();

         $currents['version']    = trim($result['version']);
         $currents['dbversion']  = $currents['version'];
         $currents['language']   = trim($result['language']);
      } else {
         $currents = Config::getConfigurationValues(
            'core',
            ['version', 'dbversion', 'language']
         );

         if (!isset($currents['dbversion'])) {
            $currents['dbversion'] = $currents['version'];
         }
      }

      $this->version    = $currents['version'];
      $this->dbversion  = $currents['dbversion'];
      $this->language   = $currents['language'];

      return $currents;
   }


   /**
    * Run updates
    *
    * @param string $current_version Current version
    *
    * @return void
    */
   public function doUpdates($current_version = null) {
      if ($current_version === null) {
         if ($this->version === null) {
            throw new \RuntimeException('Cannot process updates without any version specified!');
         }
         $current_version = $this->version;
      }

      $DB = $this->DB;

      // To prevent problem of execution time
      ini_set("max_execution_time", "0");

      $updir = __DIR__ . "/../install/";

      if (version_compare($current_version, '0.80', 'lt')) {
         die('Upgrade is not supported before 0.80!');
         die(1);
      }

      // Update process desactivate all plugins
      $plugin = new Plugin();
      $plugin->unactivateAll();

      switch ($current_version) {
         case "0.80" :
            include_once("{$updir}update_080_0801.php");
            update080to0801();

         case "0.80.1" :
         case "0.80.2" :
            include_once("{$updir}update_0801_0803.php");
            update0801to0803();

         case "0.80.3" :
         case "0.80.4" :
         case "0.80.5" :
         case "0.80.6" :
         case "0.80.61" :
         case "0.80.7" :
            include_once("{$updir}update_0803_083.php");
            update0803to083();

         case "0.83" :
            include_once("{$updir}update_083_0831.php");
            update083to0831();

         case "0.83.1" :
         case "0.83.2" :
            include_once("{$updir}update_0831_0833.php");
            update0831to0833();

         case "0.83.3" :
         case "0.83.31" :
         case "0.83.4" :
         case "0.83.5" :
         case "0.83.6" :
         case "0.83.7" :
         case "0.83.8" :
         case "0.83.9" :
         case "0.83.91" :
            include_once("{$updir}update_0831_084.php");
            update0831to084();

         case "0.84" :
            include_once("{$updir}update_084_0841.php");
            update084to0841();

         case "0.84.1" :
         case "0.84.2" :
            include_once("{$updir}update_0841_0843.php");
            update0841to0843();

         case "0.84.3" :
            include_once("{$updir}update_0843_0844.php");
            update0843to0844();

         case "0.84.4" :
         case "0.84.5" :
            include_once("{$updir}update_0845_0846.php");
            update0845to0846();

         case "0.84.6" :
         case "0.84.7" :
         case "0.84.8" :
         case "0.84.9" :
            include_once("{$updir}update_084_085.php");
            update084to085();

         case "0.85" :
         case "0.85.1" :
         case "0.85.2" :
            include_once("{$updir}update_085_0853.php");
            update085to0853();

         case "0.85.3" :
         case "0.85.4" :
            include_once("{$updir}update_0853_0855.php");
            update0853to0855();

         case "0.85.5" :
            include_once("{$updir}update_0855_090.php");
            update0855to090();

         case "0.90" :
            include_once("{$updir}update_090_0901.php");
            update090to0901();

         case "0.90.1" :
         case "0.90.2" :
         case "0.90.3" :
         case "0.90.4" :
            include_once("{$updir}update_0901_0905.php");
            update0901to0905();

         case "0.90.5" :
            include_once("{$updir}update_0905_91.php");
            update0905to91();

         case "9.1" :
         case "0.91":
            include_once("{$updir}update_91_911.php");
            update91to911();

         case "9.1.1":
         case "9.1.2":
            include_once("{$updir}update_911_913.php");
            update911to913();

         case "9.1.3":
         case "9.1.4":
         case "9.1.5":
         case "9.1.6":
         case "9.1.7":
         case "9.1.7.1":
         case "9.1.8":
         case "9.2-dev":
            include_once("{$updir}update_91_92.php");
            update91to92();

         case "9.2":
            include_once("{$updir}update_92_921.php");
            update92to921();

         case "9.2.1":
            include_once("{$updir}update_921_922.php");
            update921to922();

         case "9.2.2":
            //9.2.2 upgrade script was not run from the release, see https://github.com/glpi-project/glpi/issues/3659
            //see https://github.com/glpi-project/glpi/issues/3659
            include_once("{$updir}update_921_922.php");
            update921to922();
            include_once("{$updir}update_922_923.php");
            update922to923();

         case "9.2.3":
         case "9.2.4":
         case "9.3-dev":
            include_once("{$updir}update_92_93.php");
            update92to93();

         case "9.3":
         case "9.3.0":
            include_once "{$updir}update_930_931.php";
            update930to931();

         case "9.3.1":
            include_once "{$updir}update_931_932.php";
            update931to932();

         case "9.3.2":
         case "9.3.3":
         case "9.3.4":
         case "9.3.5":
         case "9.4.0-dev":
            include_once("{$updir}update_93_94.php");
            update93to94();

         case "9.4.0":
            include_once "{$updir}update_940_941.php";
            update940to941();

         case "9.4.1":
         case "9.4.1.1":
            include_once "{$updir}update_941_942.php";
            update941to942();

         case "9.4.2":
            include_once "{$updir}update_942_943.php";
            update942to943();

         case "9.4.3":
         case "9.4.4":
            include_once "{$updir}update_943_945.php";
            update943to945();

         case "9.4.5":
            include_once "{$updir}update_945_946.php";
            update945to946();

         case "9.4.6":
            include_once "{$updir}update_946_947.php";
            update946to947();

         case "9.4.7":
         case "9.4.8":
         case "9.4.9":
         case "9.5.0-dev":
            include_once "{$updir}update_94_95.php";
            update94to95();

         case "9.5.0":
         case "9.5.1":
            include_once "{$updir}update_951_952.php";
            update951to952();

         case "9.5.2":
            include_once "{$updir}update_952_953.php";
            update952to953();

         case "9.5.3":
            include_once "{$updir}update_953_954.php";
            update953to954();

         case "9.5.4":
         case "9.5.5":
         case "9.5.6":
         case "9.5.7":
         case "9.5.8":
         case "9.5.9":
         case "x.x.x-dev":
            include_once "{$updir}update_95_xx.php";
            update95toXX();
            break;

         case GLPI_VERSION:
         case GLPI_SCHEMA_VERSION:
            break;

         default :
            $message = sprintf(
               __('Unsupported version (%1$s)'),
               $current_version
            );
            if (isCommandLine()) {
               echo "$message\n";
               die(1);
            } else {
               $this->migration->displayWarning($message, true);
               die(1);
            }
      }

      // Update version number and default langage and new version_founded ---- LEAVE AT THE END
      Config::setConfigurationValues('core', ['version'             => GLPI_VERSION,
                                              'dbversion'           => GLPI_SCHEMA_VERSION,
                                              'language'            => $this->language,
                                              'founded_new_version' => '']);

      if (defined('GLPI_SYSTEM_CRON')) {
         // Downstream packages may provide a good system cron
         $DB->updateOrDie(
            'glpi_crontasks', [
               'mode'   => 2
            ], [
               'name'      => ['!=', 'watcher'],
               'allowmode' => ['&', 2]
            ]
         );
      }

      // reset telemetry
      $crontask_telemetry = new CronTask;
      $crontask_telemetry->getFromDBbyName("Telemetry", "telemetry");
      $crontask_telemetry->resetDate();
      $crontask_telemetry->resetState();

      //generate security key if missing, and update db
      $glpikey = new GLPIKey();
      if (!$glpikey->keyExists() && !$glpikey->generate()) {
         $this->migration->displayWarning(__('Unable to create security key file! You have to run "php bin/console glpi:security:change_key" command to manually create this file.'), true);
      }
   }

   /**
    * Set migration
    *
    * @param Migration $migration Migration instance
    *
    * @return Update
    */
   public function setMigration(Migration $migration) {
      $this->migration = $migration;
      return $this;
   }

   /**
    * Check if expected security key file is missing.
    *
    * @return bool
    */
   public function isExpectedSecurityKeyFileMissing(): bool {
      $expected_key_path = $this->getExpectedSecurityKeyFilePath();

      if ($expected_key_path === null) {
         return false;
      }

      return !file_exists($expected_key_path);
   }

   /**
    * Returns expected security key file path.
    * Will return null for GLPI versions that was not yet handling a custom security key.
    *
    * @return string|null
    */
   public function getExpectedSecurityKeyFilePath(): ?string {
      $glpikey = new GLPIKey();
      return $glpikey->getExpectedKeyPath($this->getCurrents()['version']);
   }
}
