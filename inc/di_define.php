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

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Interop\Container\ContainerInterface;
use Monolog\Formatter\LineFormatter;

return [
   'DB'           => Di\Factory(function() {
      $dbconn = new DBConnection();
      $dbconn->establishDBConnection(
         (isset($USEDBREPLICATE) ? $USEDBREPLICATE : 0),
         (isset($DBCONNECTION_REQUIRED) ? $DBCONNECTION_REQUIRED : 0)
      );
      return $dbconn->getDb();
   }),
   'GLPIConfig'   => DI\Factory(function(ContainerInterface $c) {
      //required since there are declarations hardcoded, as example in inc/define.php
      global $CFG_GLPI;
      $config_object = $c->get('Config');
      $current_config = [];

      if (!isset($_GET['donotcheckversion'])  // use normal config table on restore process
         && (isset($TRY_OLD_CONFIG_FIRST) // index case
            || (isset($_SESSION['TRY_OLD_CONFIG_FIRST']) && $_SESSION['TRY_OLD_CONFIG_FIRST']))) { // backup case

         if (isset($_SESSION['TRY_OLD_CONFIG_FIRST'])) {
            unset($_SESSION['TRY_OLD_CONFIG_FIRST']);
         }

         // First try old config table : for update process management from < 0.80 to >= 0.80
         $config_object->forceTable('glpi_config');

         if ($config_object->getFromDB(1)) {
            $current_config = $config_object->fields;
         } else {
            $config_object->forceTable('glpi_configs');
            if ($config_object->getFromDB(1)) {
               if (isset($config_object->fields['context'])) {
                  $current_config = $config_object->getValues('core');
               } else {
                  $current_config = $config_object->fields;
               }
               $config_ok = true;
            }
         }

      } else { // Normal load process : use normal config table. If problem try old one
         if ($config_object->getFromDB(1)) {
            if (isset($config_object->fields['context'])) {
               $current_config = $config_object->getValues('core');
            } else {
               $current_config = $config_object->fields;
            }
         } else {
            // Manage glpi_config table before 0.80
            $config_object->forceTable('glpi_config');
            if ($config_object->getFromDB(1)) {
               $current_config = $config_object->fields;
            }
         }
      }

      if (count($current_config) > 0) {
         $CFG_GLPI = array_merge($CFG_GLPI, $current_config);

         if (isset($CFG_GLPI['priority_matrix'])) {
            $CFG_GLPI['priority_matrix'] = importArrayFromDB(
               $CFG_GLPI['priority_matrix'],
               true
            );
         }
         if (isset($CFG_GLPI['lock_item_list'])) {
            $CFG_GLPI['lock_item_list'] = importArrayFromDB($CFG_GLPI['lock_item_list']);
         }
         if (isset($CFG_GLPI['lock_lockprofile_id'])
            && $CFG_GLPI["lock_use_lock_item"]
            && ($CFG_GLPI["lock_lockprofile_id"] > 0)
            && !isset($CFG_GLPI['lock_lockprofile']) ) {
               $prof = new Profile();
               $prof->getFromDB($CFG_GLPI["lock_lockprofile_id"]);
               $prof->cleanProfile();
               $CFG_GLPI['lock_lockprofile'] = $prof->fields;
         }

         // Path for icon of document type (web mode only)
         if (isset($CFG_GLPI["root_doc"])) {
            $CFG_GLPI["typedoc_icon_dir"] = $CFG_GLPI["root_doc"]."/pics/icones";
         }

      } else {
         throw new \RuntimeException('Error reading configuration from database.');
      }
      return $CFG_GLPI;
   }),
   'GLPI_DB_CACHE'   => Di\factory(function(ContainerInterface $c) {
      return $c->get('Config')->getCache('cache_db');
   }),
   'GLPI_TR_CACHE'   => DI\factory(function (ContainerInterface $c) {
      return $c->get('Config')->getCache('cache_trans');
   }),
   'log.level'       => DI\Factory(function() {
      if (($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE)) {
         return Logger::DEBUG;
      } else {
         return Logger::WARNING;
      }
   }),
   'GLPIPHPLog'      => DI\factory(function (ContainerInterface $c) {
      $logger = new Logger('glpiphplog');

      /*$CFG_GLPI = $c->get('GLPIConfig');
      if ((isset($CFG_GLPI["use_log_in_files"]) && $CFG_GLPI["use_log_in_files"])) {*/
         $fileHandler = new StreamHandler(
            GLPI_LOG_DIR . "/php-errors.log",
            $c->get('log.level')
         );
         $formatter = new LineFormatter(null, null, true);
         $fileHandler->setFormatter($formatter);
      /*} else {
         $fileHandler = new NullHandler();
      }*/

      $logger->pushHandler($fileHandler);
      return $logger;
   })
];
