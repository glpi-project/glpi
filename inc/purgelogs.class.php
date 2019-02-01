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

class PurgeLogs extends CommonDBTM {

   static function getTypeName($nb = 0) {
      return __('Logs purge');
   }

   static function cronPurgeLogs($task) {
      $logs_before = self::getLogsCount();
      if ($logs_before) {
         self::purgeSoftware();
         self::purgeInfocom();
         self::purgeUserInfos();
         self::purgeDevices();
         self::purgeRelations();
         self::purgeItems();
         self::purgeOthers();
         self::purgePlugins();
         self::purgeAll();
         $logs_after = self::getLogsCount();
         Log::history(0, __CLASS__, [0, $logs_before, $logs_after], '', Log::HISTORY_LOG_SIMPLE_MESSAGE);
         $task->addVolume($logs_before - $logs_after);
      } else {
         $task->addVolume(0);
      }
      return true;
   }

   static function cronInfo($name) {
      return ['description' => __("Purge history")];
   }

   /**
    * Purge softwares logs
    *
    * @return void
    */
   static function purgeSoftware() {
      global $DB, $CFG_GLPI;

      $month = self::getDateModRestriction($CFG_GLPI['purge_computer_software_install']);
      if ($month) {
         $DB->delete(
            'glpi_logs', [
               'itemtype'        => 'Computer',
               'linked_action'   => [
                  Log::HISTORY_INSTALL_SOFTWARE,
                  Log::HISTORY_UNINSTALL_SOFTWARE
               ]
            ] + $month
         );
      }

      $month = self::getDateModRestriction($CFG_GLPI['purge_software_computer_install']);
      if ($month) {
         $DB->delete(
            'glpi_logs', [
               'itemtype'        => 'SoftwareVersion',
               'linked_action'   => [
                  Log::HISTORY_INSTALL_SOFTWARE,
                  Log::HISTORY_UNINSTALL_SOFTWARE
               ]
            ] + $month
         );
      }

      $month = self::getDateModRestriction($CFG_GLPI['purge_software_version_install']);
      if ($month) {
         //Delete software version association
         $DB->delete(
            'glpi_logs', [
               'itemtype'        => 'Software',
               'itemtype_link'   => 'SoftwareVersion',
               'linked_action'   => [
                  Log::HISTORY_ADD_SUBITEM,
                  Log::HISTORY_UPDATE_SUBITEM,
                  Log::HISTORY_DELETE_SUBITEM
               ]
            ] + $month
         );
      }
   }

   /**
    * Purge infocom logs
    *
    * @return void
    */
   static function purgeInfocom() {
      global $DB, $CFG_GLPI;

      $month = self::getDateModRestriction($CFG_GLPI['purge_infocom_creation']);
      if ($month) {
         //Delete add infocom
         $DB->delete(
            'glpi_logs', [
               'itemtype'        => 'Software',
               'itemtype_link'   => 'Infocom',
               'linked_action'   => Log::HISTORY_ADD_SUBITEM
            ] + $month
         );

         $DB->delete(
            'glpi_logs', [
               'itemtype'        => 'Infocom',
               'linked_action'   => Log::HISTORY_CREATE_ITEM
            ] + $month
         );
      }
   }

   /**
    * Purge users logs
    *
    * @return void
    */
   static function purgeUserinfos() {
      global $DB, $CFG_GLPI;

      $month = self::getDateModRestriction($CFG_GLPI['purge_profile_user']);
      if ($month) {
         //Delete software version association
         $DB->delete(
            'glpi_logs', [
               'itemtype'        => 'User',
               'itemtype_link'   => 'Profile_User',
               'linked_action'   => [
                  Log::HISTORY_ADD_SUBITEM,
                  Log::HISTORY_UPDATE_SUBITEM,
                  Log::HISTORY_DELETE_SUBITEM
               ]
            ] + $month
         );
      }

      $month = self::getDateModRestriction($CFG_GLPI['purge_group_user']);
      if ($month) {
         //Delete software version association
         $DB->delete(
            'glpi_logs', [
               'itemtype'        => 'User',
               'itemtype_link'   => 'Group_User',
               'linked_action'   => [
                  Log::HISTORY_ADD_SUBITEM,
                  Log::HISTORY_UPDATE_SUBITEM,
                  Log::HISTORY_DELETE_SUBITEM
               ]
            ] + $month
         );
      }

      $month = self::getDateModRestriction($CFG_GLPI['purge_userdeletedfromldap']);
      if ($month) {
         //Delete software version association
         $DB->delete(
            'glpi_logs', [
               'itemtype'        => 'User',
               'linked_action'   => Log::HISTORY_LOG_SIMPLE_MESSAGE
            ] + $month
         );
      }

      $month = self::getDateModRestriction($CFG_GLPI['purge_user_auth_changes']);
      if ($month) {
         //Delete software version association
         $DB->delete(
            'glpi_logs', [
               'itemtype'        => 'User',
               'linked_action'   => Log::HISTORY_ADD_RELATION
            ] + $month
         );
      }

   }


   /**
    * Purge devices logs
    *
    * @return void
    */
   static function purgeDevices() {
      global $DB, $CFG_GLPI;

      $actions = [
         Log::HISTORY_ADD_DEVICE          => "adddevice",
         Log::HISTORY_UPDATE_DEVICE       => "updatedevice",
         Log::HISTORY_DELETE_DEVICE       => "deletedevice",
         Log::HISTORY_CONNECT_DEVICE      => "connectdevice",
         Log::HISTORY_DISCONNECT_DEVICE   => "disconnectdevice"
      ];
      foreach ($actions as $key => $value) {
         $month = self::getDateModRestriction($CFG_GLPI['purge_'.$value]);
         if ($month) {
            //Delete software version association
            $DB->delete(
               'glpi_logs', [
                  'linked_action' => $key
               ] + $month
            );
         }

      }
   }

   /**
    * Purge relations logs
    *
    * @return void
    */
   static function purgeRelations() {
      global $DB, $CFG_GLPI;

      $actions = [
         Log::HISTORY_ADD_RELATION     => "addrelation",
         Log::HISTORY_UPDATE_RELATION  => "addrelation",
         Log::HISTORY_DEL_RELATION     => "deleterelation"
      ];
      foreach ($actions as $key => $value) {
         $month = self::getDateModRestriction($CFG_GLPI['purge_'.$value]);
         if ($month) {
            //Delete software version association
            $DB->delete(
               'glpi_logs', [
                  'linked_action' => $key
               ] + $month
            );
         }
      }
   }

   /**
    * Purge items logs
    *
    * @return void
    */
   static function purgeItems() {
      global $DB, $CFG_GLPI;

      $actions = [
         Log::HISTORY_CREATE_ITEM      => "createitem",
         Log::HISTORY_ADD_SUBITEM      => "createitem",
         Log::HISTORY_DELETE_ITEM      => "deleteitem",
         Log::HISTORY_DELETE_SUBITEM   => "deleteitem",
         Log::HISTORY_UPDATE_SUBITEM   => "updateitem",
         Log::HISTORY_RESTORE_ITEM     => "restoreitem"
      ];
      foreach ($actions as $key => $value) {
         $month = self::getDateModRestriction($CFG_GLPI['purge_'.$value]);
         if ($month) {
            //Delete software version association
            $DB->delete(
               'glpi_logs', [
                  'linked_action' => $key
               ] + $month
            );
         }
      }

   }

   /**
    * Purge othr logs
    *
    * @return void
    */
   static function purgeOthers() {
      global $DB, $CFG_GLPI;

      $actions = [
         16 => 'comments',
         19 => 'datemod'
      ];
      foreach ($actions as $key => $value) {
         $month = self::getDateModRestriction($CFG_GLPI['purge_'.$value]);
         if ($month) {
            $DB->delete(
               'glpi_logs', [
                  'id_search_option' => $key
               ] + $month
            );
         }
      }
   }


   /**
    * Purge plugins logs
    *
    * @return void
    */
   static function purgePlugins() {
      global $DB, $CFG_GLPI;

      $month = self::getDateModRestriction($CFG_GLPI['purge_plugins']);
      if ($month) {
         $DB->delete(
            'glpi_logs', [
               'itemtype' => ['LIKE', 'Plugin%']
            ] + $month
         );
      }
   }


   /**
    * Purge all logs
    *
    * @return void
    */
   static function purgeAll() {
      global $DB, $CFG_GLPI;

      $month = self::getDateModRestriction($CFG_GLPI['purge_all']);
      if ($month) {
         $DB->delete(
            'glpi_logs',
            $month
         );
      }
   }

   /**
    * Get modification date restriction clause
    *
    * @param integer $month Number of months
    *
    * @return array|false
    */
   static function getDateModRestriction($month) {
      if ($month > 0) {
         return ['date_mod' => ['<=', new QueryExpression("DATE_ADD(NOW(), INTERVAL -$month MONTH)")]];
      } else if ($month == Config::DELETE_ALL) {
         return [1 => 1];
      } else if ($month == Config::KEEP_ALL) {
         return false;
      }
   }

   /**
    * Count logs
    *
    * @return integer
    */
   static function getLogsCount() {
      return countElementsInTable('glpi_logs');
   }
}
