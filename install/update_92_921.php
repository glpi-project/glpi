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

/**
 * Update from 9.2 to 9.2.1
 *
 * @return bool for success (will die for most error)
**/
function update92to921() {
   global $DB, $migration, $CFG_GLPI;

   $current_config   = Config::getConfigurationValues('core');
   $updateresult     = true;
   $ADDTODISPLAYPREF = [];

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), '9.2.1'));
   $migration->setVersion('9.2.1');

   //fix migration parts that may not been ran from 9.1.x update
   //see https://github.com/glpi-project/glpi/issues/2871
   // Slalevels changes => changes in 9.2 migration, impossible to fix here.

   // Ticket changes
   if ($DB->fieldExists('glpi_tickets', 'slas_id')) {
      $migration->changeField("glpi_tickets", "slas_id", "slts_ttr_id", "integer");
      $migration->migrationOneTable('glpi_tickets');
   }
   if (isIndex('glpi_tickets', 'slas_id')) {
      $migration->dropKey('glpi_tickets', 'slas_id');
   }
   if ($DB->fieldExists('glpi_tickets', 'slts_ttr_id')) {
      $migration->addKey('glpi_tickets', 'slts_ttr_id');
   }

   if (!$DB->fieldExists('glpi_tickets', 'time_to_own')) {
      $after = 'due_date';
      if (!$DB->fieldExists('glpi_tickets', 'due_date')) {
         $after = 'time_to_resolve';
      }
      $migration->addField("glpi_tickets", "time_to_own", "datetime", ['after' => $after]);
      $migration->addKey('glpi_tickets', 'time_to_own');
   }

   if ($DB->fieldExists('glpi_tickets', 'slalevels_id')) {
      $migration->changeField('glpi_tickets', 'slalevels_id', 'ttr_slalevels_id', 'integer');
      $migration->migrationOneTable('glpi_tickets');
      $migration->dropKey('glpi_tickets', 'slalevels_id');
   }
   if ($DB->fieldExists('glpi_tickets', 'ttr_slalevels_id')) {
      $migration->addKey('glpi_tickets', 'ttr_slalevels_id');
   }

   // Sla rules criterias migration
   $DB->updateOrDie("glpi_rulecriterias",
      ['criteria' => "slts_ttr_id"],
      ['criteria' => "slas_id"],
      "SLA rulecriterias migration"
   );

   // Sla rules actions migration
   $DB->updateOrDie("glpi_ruleactions",
      ['field' => "slts_ttr_id"],
      ['field' => "slas_id"],
      "SLA ruleactions migration"
   );
   // end fix 9.1.x migration

   //fix migration parts that may not been ran from previous update
   //see https://github.com/glpi-project/glpi/issues/2871
   if (!$DB->tableExists('glpi_olalevelactions')) {
      $query = "CREATE TABLE `glpi_olalevelactions` (
               `id` int(11) NOT NULL AUTO_INCREMENT,
               `olalevels_id` int(11) NOT NULL DEFAULT '0',
               `action_type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
               `field` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
               `value` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
               PRIMARY KEY (`id`),
               KEY `olalevels_id` (`olalevels_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "9.2 add table glpi_olalevelactions");
   }

   if (!$DB->tableExists('glpi_olalevelcriterias')) {
      $query = "CREATE TABLE `glpi_olalevelcriterias` (
               `id` int(11) NOT NULL AUTO_INCREMENT,
               `olalevels_id` int(11) NOT NULL DEFAULT '0',
               `criteria` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
               `condition` int(11) NOT NULL DEFAULT '0' COMMENT 'see define.php PATTERN_* and REGEX_* constant',
               `pattern` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
               PRIMARY KEY (`id`),
               KEY `olalevels_id` (`olalevels_id`),
               KEY `condition` (`condition`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "9.2 add table glpi_olalevelcriterias");
   }

   if (!$DB->tableExists('glpi_olalevels')) {
      $query = "CREATE TABLE `glpi_olalevels` (
               `id` int(11) NOT NULL AUTO_INCREMENT,
               `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
               `olas_id` int(11) NOT NULL DEFAULT '0',
               `execution_time` int(11) NOT NULL,
               `is_active` tinyint(1) NOT NULL DEFAULT '1',
               `entities_id` int(11) NOT NULL DEFAULT '0',
               `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
               `match` char(10) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'see define.php *_MATCHING constant',
               `uuid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
               PRIMARY KEY (`id`),
               KEY `name` (`name`),
               KEY `is_active` (`is_active`),
               KEY `olas_id` (`olas_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "9.2 add table glpi_olalevels");
   }

   if (!$DB->tableExists('glpi_olalevels_tickets')) {
      $query = "CREATE TABLE `glpi_olalevels_tickets` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `tickets_id` int(11) NOT NULL DEFAULT '0',
                  `olalevels_id` int(11) NOT NULL DEFAULT '0',
                  `date` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `tickets_id` (`tickets_id`),
                  KEY `olalevels_id` (`olalevels_id`),
                  KEY `unicity` (`tickets_id`,`olalevels_id`)
               ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "9.2 add table glpi_olalevels_tickets");

      $DB->insertOrDie("glpi_crontasks", [
         'itemtype'        => "OlaLevel_Ticket",
         'name'            => "olaticket",
         'frequency'       => "604800",
         'param'           => null,
         'state'           => "0",
         'mode'            => "1",
         'allowmode'       => "3",
         'hourmin'         => "0",
         'hourmax'         => "24",
         'logs_lifetime'   => "10",
         'lastrun'         => null,
         'lastcode'        => null,
         'comment'         => null,
      ], "9.2 populate glpi_crontasks for olaticket");
   }

   if (!$DB->tableExists('glpi_slms')) {
      // Changing the structure of the table 'glpi_slas'
      $migration->renameTable('glpi_slas', 'glpi_slms');
      $migration->migrationOneTable('glpi_slas');
   }

   // Changing the structure of the table 'glpi_slts'
   if ($DB->tableExists('glpi_slts')) {
      $migration->renameTable('glpi_slts', 'glpi_slas');
      $migration->migrationOneTable('glpi_slts');
      $migration->changeField('glpi_slas', 'slas_id', 'slms_id', 'integer');
      $migration->dropKey('glpi_slas', 'slas_id');
      $migration->addKey('glpi_slas', 'slms_id');
   }

   // Slalevels changes
   if ($DB->fieldExists("glpi_slalevels", "slts_id")) {
      $migration->changeField('glpi_slalevels', 'slts_id', 'slas_id', 'integer');
      $migration->migrationOneTable('glpi_slalevels');
      $migration->dropKey('glpi_slalevels', 'slts_id');
      $migration->addKey('glpi_slalevels', 'slas_id');
   }

   // Ticket changes
   if (!$DB->fieldExists("glpi_tickets", "ola_waiting_duration", false)) {
      $migration->addField("glpi_tickets", "ola_waiting_duration", "integer",
                           ['after' => 'sla_waiting_duration']);
      $migration->migrationOneTable('glpi_tickets');
   }
   //this one was missing
   $migration->addKey('glpi_tickets', 'ola_waiting_duration');

   if (!$DB->fieldExists("glpi_tickets", "olas_tto_id", false)) {
      $migration->addField("glpi_tickets", "olas_tto_id", "integer", ['after' => 'ola_waiting_duration']);
      $migration->migrationOneTable('glpi_tickets');
      $migration->addKey('glpi_tickets', 'olas_tto_id');
   }

   if (!$DB->fieldExists("glpi_tickets", "olas_ttr_id", false)) {
      $migration->addField("glpi_tickets", "olas_ttr_id", "integer", ['after' => 'olas_tto_id']);
      $migration->migrationOneTable('glpi_tickets');
      $migration->addKey('glpi_tickets', 'olas_ttr_id');
   }

   if (!$DB->fieldExists("glpi_tickets", "ttr_olalevels_id", false)) {
      $migration->addField("glpi_tickets", "ttr_olalevels_id", "integer", ['after' => 'olas_ttr_id']);
      $migration->migrationOneTable('glpi_tickets');
   }

   if (!$DB->fieldExists("glpi_tickets", "internal_time_to_resolve", false)) {
      $migration->addField("glpi_tickets", "internal_time_to_resolve", "datetime",
                           ['after' => 'ttr_olalevels_id']);
      $migration->migrationOneTable('glpi_tickets');
      $migration->addKey('glpi_tickets', 'internal_time_to_resolve');
   }

   if (!$DB->fieldExists("glpi_tickets", "internal_time_to_own", false)) {
      $migration->addField("glpi_tickets", "internal_time_to_own", "datetime",
                           ['after' => 'internal_time_to_resolve']);
      $migration->migrationOneTable('glpi_tickets');
      $migration->addKey('glpi_tickets', 'internal_time_to_own');
   }

   if ($DB->fieldExists("glpi_tickets", "slts_tto_id")) {
      $migration->changeField("glpi_tickets", "slts_tto_id", "slas_tto_id", "integer");
      $migration->migrationOneTable('glpi_tickets');
      $migration->addKey('glpi_tickets', 'slas_tto_id');
      $migration->dropKey('glpi_tickets', 'slts_tto_id');
   }

   if ($DB->fieldExists("glpi_tickets", "slts_ttr_id")) {
      $migration->changeField("glpi_tickets", "slts_ttr_id", "slas_ttr_id", "integer");
      $migration->migrationOneTable('glpi_tickets');
      $migration->addKey('glpi_tickets', 'slas_ttr_id');
      $migration->dropKey('glpi_tickets', 'slts_ttr_id');
   }
   if ($DB->fieldExists("glpi_tickets", "due_date")) {
      $migration->changeField('glpi_tickets', 'due_date', 'time_to_resolve', 'datetime');
      $migration->migrationOneTable('glpi_tickets');
      $migration->dropKey('glpi_tickets', 'due_date');
      $migration->addKey('glpi_tickets', 'time_to_resolve');
   }

   //Change changes
   if ($DB->fieldExists("glpi_changes", "due_date")) {
      $migration->changeField('glpi_changes', 'due_date', 'time_to_resolve', 'datetime');
      $migration->migrationOneTable('glpi_changes');
      $migration->dropKey('glpi_changes', 'due_date');
      $migration->addKey('glpi_changes', 'time_to_resolve');
   }

   //Problem changes
   if ($DB->fieldExists("glpi_problems", "due_date")) {
      $migration->changeField('glpi_problems', 'due_date', 'time_to_resolve', 'datetime');
      $migration->migrationOneTable('glpi_problems');
      $migration->dropKey('glpi_problems', 'due_date');
      $migration->addKey('glpi_problems', 'time_to_resolve');
   }

   // ProfileRights changes
   $DB->updateOrDie("glpi_profilerights",
      ['name' => "slm"],
      ['name' => "sla"],
      "SLM profilerights migration"
   );

   //Sla rules criterias migration
   $DB->updateOrDie("glpi_rulecriterias",
      ['criteria' => "slas_ttr_id"],
      ['criteria' => "slts_ttr_id"],
      "SLA rulecriterias migration"
   );

   $DB->updateOrDie("glpi_rulecriterias",
      ['criteria' => "slas_tto_id"],
      ['criteria' => "slts_tto_id"],
      "SLA rulecriterias migration"
   );

   // Sla rules actions migration
   $DB->updateOrDie("glpi_ruleactions",
      ['field' => "slas_ttr_id"],
      ['field' => "slts_ttr_id"],
      "SLA ruleactions migration"
   );

   $DB->updateOrDie("glpi_ruleactions",
      ['field' => "slas_tto_id"],
      ['field' => "slts_tto_id"],
      "SLA ruleactions migration"
   );

   //see https://github.com/glpi-project/glpi/issues/3037
   $migration->addPreQuery(
      $DB->buildUpdate("glpi_crontasks",
         ['itemtype' => "QueuedNotification"],
         ['itemtype' => "QueuedMail"]
      )
   );

   $migration->addPreQuery(
      $DB->buildUpdate("glpi_crontasks",
         ['name' => "queuednotification"],
         ['name' => "queuedmail"]
      )
   );

   $migration->addPreQuery(
      $DB->buildUpdate("glpi_crontasks",
         ['name' => "queuednotificationclean"],
         ['name' => "queuedmailclean"]
      )
   );

   // TODO: can be done when DB::delete() supports JOINs
   $migration->addPreQuery("DELETE `duplicated` FROM `glpi_profilerights` AS `duplicated`
                            INNER JOIN `glpi_profilerights` AS `original`
                            WHERE `duplicated`.`profiles_id` = `original`.`profiles_id`
                            AND `original`.`name` = 'queuednotification'
                            AND `duplicated`.`name` = 'queuedmail'");

   $migration->addPreQuery(
      $DB->buildUpdate("glpi_profilerights",
         ['name' => "queuednotification"],
         ['name' => "queuedmail"]
      )
   );

   //ensure do_count is set to AUTO
   //do_count update query may have been affected, but we cannot run it here
   $migration->addPreQuery(
      $DB->buildUpdate("glpi_savedsearches",
         ['entities_id' => "0"],
         ['entities_id' => "-1"]
      )
   );

   if ($DB->fieldExists("glpi_notifications", "mode", false)) {
      $query = "REPLACE INTO `glpi_notifications_notificationtemplates`
                       (`notifications_id`, `mode`, `notificationtemplates_id`)
                       SELECT `id`, `mode`, `notificationtemplates_id`
                       FROM `glpi_notifications`";
      $DB->queryOrDie($query, "9.2 migrate notifications templates");

      //migrate any existing mode before removing the field
      $migration->dropField('glpi_notifications', 'mode');
      $migration->dropField('glpi_notifications', 'notificationtemplates_id');

      $migration->migrationOneTable("glpi_notifications");
   }

   // add missing fields for certificates working in allassets.php
   $migration->addField("glpi_certificates", "contact", "string", ['after' => 'manufacturers_id']);
   $migration->addField("glpi_certificates", "contact_num", "string", ['after' => 'contact']);
   $migration->migrationOneTable("glpi_certificates");

   // end fix 9.2 migration

   //add MSIN to simcard component
   $migration->addField('glpi_items_devicesimcards', 'msin', 'string', ['after' => 'puk2', 'value' => '']);
   $migration->addField('glpi_items_devicesimcards', 'is_recursive', 'bool', ['after' => 'entities_id', 'value' => '0']);
   $migration->addKey('glpi_items_devicesimcards', 'is_recursive');

   $migration->addField('glpi_items_operatingsystems', 'is_recursive', "tinyint(1) NOT NULL DEFAULT '0'",
                        ['after' => 'entities_id']);
   $migration->addKey('glpi_items_operatingsystems', 'is_recursive');
   $migration->migrationOneTable('glpi_items_operatingsystems');

   //fix OS entities_id and is_recursive
   $items = [
      'Computer'           => 'glpi_computers',
      'Monitor'            => 'glpi_monitors',
      'NetworkEquipment'   => 'glpi_networkequipments',
      'Peripheral'         => 'glpi_peripherals',
      'Phone'              => 'glpi_phones',
      'Printer'            => 'glpi_printers'
   ];
   foreach ($items as $itemtype => $table) {
      // TODO: can be done when DB::update() supports JOINs
      $migration->addPostQuery(
         "UPDATE glpi_items_operatingsystems AS ios
            INNER JOIN `$table` as item ON ios.items_id = item.id AND ios.itemtype = '$itemtype'
            SET ios.entities_id = item.entities_id, ios.is_recursive = item.is_recursive
         "
      );
   }

   //drop "empty" glpi_items_operatingsystems
   $migration->addPostQuery(
      $DB->buildDelete("glpi_items_operatingsystems", [
         'operatingsystems_id'               => "0",
         'operatingsystemversions_id'        => "0",
         'operatingsystemservicepacks_id'    => "0",
         'operatingsystemarchitectures_id'   => "0",
         'operatingsystemkernelversions_id'  => "0",
         'operatingsystemeditions_id'        => "0",
         'OR' => [
            ['license_number' => null],
            ['license_number' => ""]
         ],
         'OR' => [
            ['license_id' => null],
            ['license_id' => ""]
         ]
      ])
   );

   // ************ Keep it at the end **************
   $migration->executeMigration();

   return $updateresult;
}
