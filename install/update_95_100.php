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

use Glpi\Application\LocalConfigurationManager;
use Glpi\ConfigParams;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Yaml\Yaml;

/**
 * Update from 9.5.x to 10.0.0
 *
 * @return bool for success (will die for most error)
**/
function update95to100() {
   global $DB, $migration, $CFG_GLPI;
   $dbutils = new DbUtils();

   $current_config   = Config::getConfigurationValues('core');
   $updateresult     = true;
   $ADDTODISPLAYPREF = [];
   $config_to_drop = [];

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), '10.0.0'));
   $migration->setVersion('10.0.0');

   /** Add main column on displaypreferences */
   if ($migration->addField(
         'glpi_displaypreferences',
         'is_main',
         'bool',
         ['value' => 1]
      )) {
      $migration->addKey('glpi_displaypreferences', 'is_main');
      $migration->dropKey('glpi_displaypreferences', 'unicity');
      $migration->migrationOneTable('glpi_displaypreferences');
      $migration->addKey(
         'glpi_displaypreferences',
         ['users_id', 'itemtype', 'num', 'is_main'],
         'unicity',
         'UNIQUE'
      );
   }
   /** /Add main column on displaypreferences */

   /** add display preferences for sub items */
   $ADDTODISPLAYPREF['Contract'] = [3, 4, 29, 5];
   $ADDTODISPLAYPREF['Item_Disk'] = [2, 3, 4, 5, 6, 7, 8];
   $ADDTODISPLAYPREF['Certificate'] = [7, 4, 8, 121, 10, 31];
   $ADDTODISPLAYPREF['Notepad'] = [200, 201, 202, 203, 204];
   $ADDTODISPLAYPREF['SoftwareVersion'] = [3, 31, 2, 122, 123, 124];
   foreach ($ADDTODISPLAYPREF as $type => $tab) {
      $rank = 1;
      foreach ($tab as $newval) {
         $query = "REPLACE INTO `glpi_displaypreferences`
                           (`itemtype` ,`num` ,`rank` ,`users_id`, `is_main`)
                     VALUES ('$type', '$newval', '".$rank++."', '0', '0')";
         $DB->query($query);
      }
   }
   /** /add display preferences for sub items */

   //Add over-quota option to software licenses to allow assignment after all alloted licenses are used
   if (!$DB->fieldExists('glpi_softwarelicenses', 'allow_overquota')) {
      if ($migration->addField('glpi_softwarelicenses', 'allow_overquota', 'bool')) {
         $migration->addKey('glpi_softwarelicenses', 'allow_overquota');
      }
   }

   /** move cache configuration into local configuration file */
   try {
      $localConfigManager = new LocalConfigurationManager(
         GLPI_CONFIG_DIR,
         new PropertyAccessor(),
         new Yaml()
      );
      $localConfigManager->setCacheValuesFromLegacyConfig(new ConfigParams($CFG_GLPI), GLPI_CACHE_DIR);
      $localConfigManager->setParameterValue('[cache_uniq_id]', uniqid(), false);
      $config_to_drop[] = 'cache_db';
      $config_to_drop[] = 'cache_trans';
   } catch (\Exception $exception) {
      $migration->displayWarning(
          sprintf(
              __('Unable to write cache configuration into local configuration file. Message was: "%s".'),
              $exception->getMessage()
          )
      );
   }
   /** /move cache configuration into local configuration file */

   /** Timezones */
   //User timezone
   if (!$DB->fieldExists('glpi_users', 'timezone')) {
      $migration->addField("glpi_users", "timezone", "varchar(50) DEFAULT NULL");
   }
   $migration->displayWarning("DATETIME fields must be converted to TIMESTAMP for timezones to work. Run bin/console db:migration:timestamps");

   // Add a config entry for app timezone setting
   $migration->addConfig(['timezone' => null]);

   /** /Timezones */

   /** Event Management */
   if (!$DB->tableExists('glpi_itilevents')) {
      $query = "CREATE TABLE `glpi_itilevents` (
         `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
         `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
         `status` tinyint(4) NOT NULL DEFAULT '0',
         `date` datetime DEFAULT NULL,
         `content` longtext COLLATE utf8_unicode_ci,
         `date_creation` datetime DEFAULT NULL,
         `significance` tinyint(4) NOT NULL,
         `correlation_id` VARCHAR(23) DEFAULT NULL,
         `date_mod` datetime DEFAULT NULL,
         `itileventservices_id` int(11) NOT NULL,
         PRIMARY KEY (`id`),
         KEY `name` (`name`),
         KEY `status` (`status`),
         KEY `date` (`date`),
         KEY `date_creation` (`date_creation`),
         KEY `significance` (`significance`),
         KEY `correlation_id` (`correlation_id`),
         KEY `itileventservices_id` (`itileventservices_id`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "10.0.0 add table glpi_itilevents");
   }

   if (!$DB->tableExists('glpi_itils_itilevents')) {
      $query = "CREATE TABLE `glpi_itils_itilevents` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
         `items_id` int(11) NOT NULL DEFAULT '0',
         `itilevents_id` int(11) unsigned NOT NULL DEFAULT '0',
         PRIMARY KEY (`id`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "10.0.0 add table glpi_itils_itilevents");
   }

   if (!$DB->tableExists('glpi_itileventhosts')) {
      $query = "CREATE TABLE `glpi_itileventhosts` (
      `id` int(11) NOT NULL,
      `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
      `items_id` int(11) NOT NULL,
      `itileventservices_id_availability` int(11) DEFAULT NULL,
      `date_mod` timestamp NULL DEFAULT NULL,
      `date_creation` timestamp NULL DEFAULT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `unicity` (`items_id`,`itemtype`),
      KEY `is_flapping` (`is_flapping`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "10.0.0 add table glpi_itileventhosts");
   }

   if (!$DB->tableExists('glpi_itileventservices')) {
      $query = "CREATE TABLE `glpi_itileventservices` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `hosts_id` int(11) NOT NULL DEFAULT -1,
      `itileventservicetemplates_id` int(11) NOT NULL,
      `last_check` timestamp NULL DEFAULT NULL,
      `status` tinyint(3) NOT NULL DEFAULT '2',
      `is_hard_status` tinyint(1) NOT NULL DEFAULT '1',
      `status_since` timestamp NULL DEFAULT NULL,
      `is_flapping` tinyint(1) NOT NULL DEFAULT '0',
      `is_active` tinyint(1) NOT NULL DEFAULT '1',
      `is_acknowledged` tinyint(1) NOT NULL DEFAULT '0',
      `acknowledged_status` tinyint(3) NOT NULL DEFAULT '2',
      `flap_state_cache` longtext COLLATE utf8_unicode_ci,
      `date_mod` timestamp NULL DEFAULT NULL,
      `date_creation` timestamp NULL DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `itileventservicetemplates_id` (`itileventservicetemplates_id`),
      KEY `hosts_id` (`hosts_id`),
      KEY `is_flapping` (`is_flapping`),
      KEY `is_acknowledged` (`is_acknowledged`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
   }

   if (!$DB->tableExists('glpi_itileventservicetemplates')) {
      $query = "CREATE TABLE `glpi_itileventservicetemplates` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
      `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
      `links_id` int(11) DEFAULT NULL,
      `priority` tinyint(3) NOT NULL DEFAULT 3,
      `calendars_id` int(11) DEFAULT NULL,
      `notificationinterval` int(11) DEFAULT NULL,
      `check_interval` int(11) DEFAULT NULL COMMENT 'Ignored when check_mode is passive',
      `use_flap_detection` tinyint(1) NOT NULL DEFAULT '0',
      `check_mode` tinyint(3) NOT NULL DEFAULT '0',
      `logger` varchar(255)  COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Indicates which plugin (or the core) logged this event. Used to delegate translations and other functions',
      `is_volatile` tinyint(1) NOT NULL DEFAULT '0',
      `flap_threshold_low` tinyint(3) NOT NULL DEFAULT '15',
      `flap_threshold_high` tinyint(3) NOT NULL DEFAULT '30',
      `max_checks` tinyint(3) NOT NULL DEFAULT '1',
      `date_mod` timestamp NULL DEFAULT NULL,
      `date_creation` timestamp NULL DEFAULT NULL,
      PRIMARY KEY (`id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "10.0.0 add table glpi_itileventservicetemplates");
   }

   if (!$DB->tableExists('glpi_itils_scheduleddowntimes')) {
      $query = "CREATE TABLE `glpi_itils_scheduleddowntimes` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `scheduleddowntimes_id` int(11) NOT NULL,
      `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
      `items_id` int(11) NOT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `unicity` (`items_id`,`itemtype`,`scheduleddowntimes_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "10.0.0 add table glpi_itils_scheduleddowntimes");
   }

   if (!$DB->tableExists('glpi_scheduleddowntimes')) {
      $query = "CREATE TABLE `glpi_scheduleddowntime` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `comment` text COLLATE utf8_unicode_ci DEFAULT NULL,
      `is_service` tinyint(1) NOT NULL DEFAULT 0,
      `items_id_target` int(11) NOT NULL,
      `is_fixed` tinyint(1) NOT NULL DEFAULT 1,
      `begin_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      `end_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
      `date_mod` timestamp NULL DEFAULT NULL,
      `date_creation` timestamp NULL DEFAULT NULL,
      PRIMARY KEY (`id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "10.0.0 add table glpi_scheduleddowntime");
   }

   if (!$DB->fieldExists('glpi_entities', 'default_event_correlation_time')) {
      $migration->addField('glpi_entities', 'default_event_correlation_time', 'integer', ['value' => '0']);
   }

   if (!$DB->fieldExists('glpi_entities', 'default_event_correlation_count')) {
      $migration->addField('glpi_entities', 'default_event_correlation_count', 'integer', ['value' => '1']);
   }

   if (!$DB->fieldExists('glpi_entities', 'default_event_correlation_window')) {
      $migration->addField('glpi_entities', 'default_event_correlation_window', 'integer', ['value' => '0']);
   }

   if (!$DB->fieldExists('glpi_entities', 'default_event_filter_action')) {
      $migration->addField('glpi_entities', 'default_event_filter_action', 'bool', ['value' => '0']);
   }

   if (!$DB->fieldExists('glpi_requesttypes', 'is_event_default')) {
      if ($migration->addField('glpi_requesttypes', 'is_event_default', 'bool')) {
         $migration->addKey('glpi_requesttypes', 'is_event_default');
         $migration->migrationOneTable('glpi_requesttypes');
         $otherRequestType = $DB->request([
            'SELECT' => ['id'],
            'FROM' => 'glpi_requesttypes',
            'WHERE' => ['name' => 'Other']
         ]);
         if ($otherRequestType->count()) {
            $DB->updateOrDie('glpi_requesttypes', ['is_event_default' => '1'], [
               'id' => $otherRequestType->next()['id']
            ]);
         } else {
            $migration->displayWarning(
               'There is no request source set for events. Please review your configuration.'
            );
         }
      }
   }

   // Warn about migrating old events
   $eventiterator = $DB->request([
      'COUNT'  => 'cpt',
      'FROM'   => Glpi\Event::getTable()
   ]);
   $event_count = $eventiterator->next()['cpt'];
   $migration->displayWarning("You must run event migration command to migrate {$event_count} events.");

   /** End of Event Management */

   // ************ Keep it at the end **************
   Config::deleteConfigurationValues('core', $config_to_drop);

   $migration->executeMigration();

   return $updateresult;
}
