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
 * Update from 9.4.x to 9.5.0
 *
 * @return bool for success (will die for most error)
**/
function update94to95() {
   global $DB, $migration;

   $updateresult     = true;
   $ADDTODISPLAYPREF = [];

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), '9.5.0'));
   $migration->setVersion('9.5.0');

   /** Encrypted FS support  */
   if (!$DB->fieldExists("glpi_items_disks", "encryption_status")) {
      $migration->addField("glpi_items_disks", "encryption_status", "integer", [
            'after'  => "is_dynamic",
            'value'  => 0
         ]
      );
   }

   if (!$DB->fieldExists("glpi_items_disks", "encryption_tool")) {
      $migration->addField("glpi_items_disks", "encryption_tool", "string", [
            'after'  => "encryption_status"
         ]
      );
   }

   if (!$DB->fieldExists("glpi_items_disks", "encryption_algorithm")) {
      $migration->addField("glpi_items_disks", "encryption_algorithm", "string", [
            'after'  => "encryption_tool"
         ]
      );
   }

   if (!$DB->fieldExists("glpi_items_disks", "encryption_type")) {
      $migration->addField("glpi_items_disks", "encryption_type", "string", [
            'after'  => "encryption_algorithm"
         ]
      );
   }
   /** /Encrypted FS support  */

   /** Suppliers restriction */
   if (!$DB->fieldExists('glpi_suppliers', 'is_active')) {
      $migration->addField(
         'glpi_suppliers',
         'is_active',
         'bool',
         ['value' => 0]
      );
      $migration->addKey('glpi_suppliers', 'is_active');
      $migration->addPostQuery(
         $DB->buildUpdate(
            'glpi_suppliers',
            ['is_active' => 1],
            [true]
         )
      );
   }
   /** /Suppliers restriction */

   /** Timezones */
   //User timezone
   if (!$DB->fieldExists('glpi_users', 'timezone')) {
      $migration->addField("glpi_users", "timezone", "varchar(50) DEFAULT NULL");
   }
   $migration->displayWarning("DATETIME fields must be converted to TIMESTAMP for timezones to work. Run bin/console glpi:migration:timestamps");

   // Add a config entry for app timezone setting
   $migration->addConfig(['timezone' => null]);
   /** /Timezones */

   // Fix search Softwares performance
   $migration->dropKey('glpi_softwarelicenses', 'softwares_id_expire');
   $migration->addKey('glpi_softwarelicenses', [
      'softwares_id',
      'expire',
      'number'
   ], 'softwares_id_expire_number');

   /** Private supplier followup in glpi_entities */
   if (!$DB->fieldExists('glpi_entities', 'suppliers_as_private')) {
      $migration->addField(
         "glpi_entities",
         "suppliers_as_private",
         "integer",
         [
            'value'     => -2,               // Inherit as default value
            'update'    => 0,                // Not enabled for root entity
            'condition' => 'WHERE `id` = 0'
         ]
      );
   }
   /** /Private supplier followup in glpi_entities */

   /** Entities Custom CSS configuration fields */
   // Add 'custom_css' entities configuration fields
   if (!$DB->fieldExists('glpi_entities', 'enable_custom_css')) {
      $migration->addField(
         'glpi_entities',
         'enable_custom_css',
         'integer',
         [
            'value'     => -2, // Inherit as default value
            'update'    => '0', // Not enabled for root entity
            'condition' => 'WHERE `id` = 0'
         ]
      );
   }
   if (!$DB->fieldExists('glpi_entities', 'custom_css_code')) {
      $migration->addField('glpi_entities', 'custom_css_code', 'text');
   }
   /** /Entities Custom CSS configuration fields */

   /** Clusters */
   if (!$DB->tableExists('glpi_clustertypes')) {
      $query = "CREATE TABLE `glpi_clustertypes` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `entities_id` int(11) NOT NULL DEFAULT '0',
         `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
         `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
         `comment` text COLLATE utf8_unicode_ci,
         `date_creation` timestamp NULL DEFAULT NULL,
         `date_mod` timestamp NULL DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `name` (`name`),
         KEY `entities_id` (`entities_id`),
         KEY `is_recursive` (`is_recursive`),
         KEY `date_creation` (`date_creation`),
         KEY `date_mod` (`date_mod`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "9.5 add table glpi_clustertypes");
   }

   if (!$DB->tableExists('glpi_clusters')) {
      $query = "CREATE TABLE `glpi_clusters` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `entities_id` int(11) NOT NULL DEFAULT '0',
         `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
         `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
         `uuid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
         `version` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
         `users_id_tech` int(11) NOT NULL DEFAULT '0',
         `groups_id_tech` int(11) NOT NULL DEFAULT '0',
         `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
         `states_id` int(11) NOT NULL DEFAULT '0' COMMENT 'RELATION to states (id)',
         `comment` text COLLATE utf8_unicode_ci,
         `clustertypes_id` int(11) NOT NULL DEFAULT '0',
         `autoupdatesystems_id` int(11) NOT NULL DEFAULT '0',
         `date_mod` timestamp NULL DEFAULT NULL,
         `date_creation` timestamp NULL DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `users_id_tech` (`users_id_tech`),
         KEY `group_id_tech` (`groups_id_tech`),
         KEY `is_deleted` (`is_deleted`),
         KEY `states_id` (`states_id`),
         KEY `clustertypes_id` (`clustertypes_id`),
         KEY `autoupdatesystems_id` (`autoupdatesystems_id`),
         KEY `entities_id` (`entities_id`),
         KEY `is_recursive` (`is_recursive`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "9.5 add table glpi_clusters");
   }

   if (!$DB->tableExists('glpi_items_clusters')) {
      $query = "CREATE TABLE `glpi_items_clusters` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `clusters_id` int(11) NOT NULL DEFAULT '0',
         `itemtype` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
         `items_id` int(11) NOT NULL DEFAULT '0',
         PRIMARY KEY (`id`),
         UNIQUE KEY `unicity` (`clusters_id`,`itemtype`,`items_id`),
         KEY `item` (`itemtype`,`items_id`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "9.5 add table glpi_items_clusters");
   }

   $migration->addField('glpi_states', 'is_visible_cluster', 'bool', [
      'value' => 1,
      'after' => 'is_visible_pdu'
   ]);
   $migration->addKey('glpi_states', 'is_visible_cluster');

   $migration->addRight('cluster', ALLSTANDARDRIGHT);

   $ADDTODISPLAYPREF['cluster'] = [31, 19];
   /** /Clusters */

   /** ITIL templates */
   //rename tables -- usefull only for 9.5 rolling release
   foreach ([
      'glpi_itiltemplates',
      'glpi_itiltemplatepredefinedfields',
      'glpi_itiltemplatemandatoryfields',
      'glpi_itiltemplatehiddenfields',
   ] as $table) {
      if ($DB->tableExists($table)) {
         $migration->renameTable($table, str_replace('itil', 'ticket', $table));
      }
   }
   //rename fkeys -- usefull only for 9.5 rolling release
   foreach ([
      'glpi_entities'                        => 'itiltemplates_id',
      'glpi_profiles'                        => 'itiltemplates_id',
      'glpi_ticketrecurrents'                => 'itiltemplates_id',
      'glpi_tickettemplatehiddenfields'      => 'itiltemplates_id',
      'glpi_tickettemplatemandatoryfields'   => 'itiltemplates_id',
      'glpi_tickettemplatepredefinedfields'  => 'itiltemplates_id'
   ] as $table => $field) {
      if ($DB->fieldExists($table, $field)) {
         $migration->changeField($table, $field, str_replace('itil', 'ticket', $field), 'integer');
      }
   }
   $migration->changeField('glpi_itilcategories', 'itiltemplates_id_incident', 'tickettemplates_id_incident', 'integer');
   $migration->changeField('glpi_itilcategories', 'itiltemplates_id_demand', 'tickettemplates_id_demand', 'integer');

   //rename profilerights values
   $migration->addPostQuery(
      $DB->buildUpdate(
         'glpi_profilerights',
         ['name' => 'itiltemplate'],
         ['name' => 'tickettemplate']
      )
   );

   //create template tables for other itil objects
   foreach (['change', 'problem'] as $itiltype) {
      if (!$DB->tableExists("glpi_{$itiltype}templates")) {
         $query = "CREATE TABLE `glpi_{$itiltype}templates` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
            `entities_id` int(11) NOT NULL DEFAULT '0',
            `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
            `comment` text COLLATE utf8_unicode_ci,
            PRIMARY KEY (`id`),
            KEY `name` (`name`),
            KEY `entities_id` (`entities_id`),
            KEY `is_recursive` (`is_recursive`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
         $DB->queryOrDie($query, "add table glpi_{$itiltype}templates");
         $migration->addPostQuery(
            $DB->buildInsert(
               "glpi_{$itiltype}templates",
               [
                  'id'           => 1,
                  'name'         => 'Default',
                  'is_recursive' => 1
               ]
            )
         );
      }

      if (!$DB->tableExists("glpi_{$itiltype}templatehiddenfields")) {
         $query = "CREATE TABLE `glpi_{$itiltype}templatehiddenfields` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `{$itiltype}templates_id` int(11) NOT NULL DEFAULT '0',
            `num` int(11) NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`),
            UNIQUE KEY `unicity` (`{$itiltype}templates_id`,`num`),
            KEY `{$itiltype}templates_id` (`{$itiltype}templates_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
         $DB->queryOrDie($query, "add table glpi_{$itiltype}templatehiddenfields");
      }

      if (!$DB->tableExists("glpi_{$itiltype}templatemandatoryfields")) {
         $query = "CREATE TABLE `glpi_{$itiltype}templatemandatoryfields` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `{$itiltype}templates_id` int(11) NOT NULL DEFAULT '0',
            `num` int(11) NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`),
            UNIQUE KEY `unicity` (`{$itiltype}templates_id`,`num`),
            KEY `{$itiltype}templates_id` (`{$itiltype}templates_id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
         $DB->queryOrDie($query, "add table glpi_{$itiltype}templatemandatoryfields");
         $migration->addPostQuery(
            $DB->buildInsert(
               "glpi_{$itiltype}templatemandatoryfields",
               [
                  'id'                       => 1,
                  $itiltype.'templates_id'   => 1,
                  'num'                      => 21
               ]
            )
         );
      }

      if (!$DB->tableExists("glpi_{$itiltype}templatepredefinedfields")) {
         $query = "CREATE TABLE `glpi_{$itiltype}templatepredefinedfields` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `{$itiltype}templates_id` int(11) NOT NULL DEFAULT '0',
            `num` int(11) NOT NULL DEFAULT '0',
            `value` text COLLATE utf8_unicode_ci,
            PRIMARY KEY (`id`),
            KEY `{$itiltype}templates_id` (`{$itiltype}templates_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
         $DB->queryOrDie($query, "add table glpi_{$itiltype}templatepredefinedfields");
      } else {
         //drop key -- usefull only for 9.5 rolling release
         $migration->dropKey("glpi_{$itiltype}templatepredefinedfields", 'unicity');
      }
   }
   /** /ITIL templates */

   /** add templates for followups */
   if (!$DB->tableExists('glpi_itilfollowuptemplates')) {
      $query = "CREATE TABLE `glpi_itilfollowuptemplates` (
         `id`              INT(11) NOT NULL AUTO_INCREMENT,
         `date_creation`   TIMESTAMP NULL DEFAULT NULL,
         `date_mod`        TIMESTAMP NULL DEFAULT NULL,
         `entities_id`     INT(11) NOT NULL DEFAULT '0',
         `is_recursive`    TINYINT(1) NOT NULL DEFAULT '0',
         `name`            VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
         `content`         TEXT NULL COLLATE 'utf8_unicode_ci',
         `requesttypes_id` INT(11) NOT NULL DEFAULT '0',
         `is_private`      TINYINT(1) NOT NULL DEFAULT '0',
         `comment`         TEXT NULL COLLATE 'utf8_unicode_ci',
         PRIMARY KEY (`id`),
         INDEX `name` (`name`),
         INDEX `is_recursive` (`is_recursive`),
         INDEX `requesttypes_id` (`requesttypes_id`),
         INDEX `entities_id` (`entities_id`),
         INDEX `date_mod` (`date_mod`),
         INDEX `date_creation` (`date_creation`),
         INDEX `is_private` (`is_private`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "add table glpi_itilfollowuptemplates");
   }
   /** /add templates for followups */

   /** Add "date_creation" field on document_items */
   if (!$DB->fieldExists('glpi_documents_items', 'date_creation')) {
      $migration->addField('glpi_documents_items', 'date_creation', 'timestamp', ['null' => true]);
      $migration->addPostQuery(
         $DB->buildUpdate(
            'glpi_documents_items',
            [
               'date_creation' => new \QueryExpression(
                  $DB->quoteName('date_mod')
               )
            ],
            [true]
         )
      );
      $migration->addKey('glpi_documents_items', 'date_creation');
   }
   /** /Add "date_creation" field on document_items */

   /** Make datacenter pictures path relative */
   $doc_send_url = '/front/document.send.php?file=_pictures/';

   $fix_picture_fct = function($path) use ($doc_send_url) {
      // Keep only part of URL corresponding to relative path inside GLPI_PICTURE_DIR
      return preg_replace('/^.*' . preg_quote($doc_send_url, '/') . '(.+)$/', '$1', $path);
   };

   $common_dc_model_tables = [
      'glpi_computermodels',
      'glpi_enclosuremodels',
      'glpi_monitormodels',
      'glpi_networkequipmentmodels',
      'glpi_pdumodels',
      'glpi_peripheralmodels',
   ];
   foreach ($common_dc_model_tables as $table) {
      $elements_to_fix = $DB->request(
         [
            'SELECT'    => ['id', 'picture_front', 'picture_rear'],
            'FROM'      => $table,
            'WHERE'     => [
               'OR' => [
                  'picture_front' => ['LIKE', '%' . $doc_send_url . '%'],
                  'picture_rear' => ['LIKE', '%' . $doc_send_url . '%'],
               ],
            ],
         ]
      );
      foreach ($elements_to_fix as $data) {
         $data['picture_front'] = $DB->escape($fix_picture_fct($data['picture_front']));
         $data['picture_rear']  = $DB->escape($fix_picture_fct($data['picture_rear']));
         $DB->update($table, $data, ['id' => $data['id']]);
      }
   }

   $elements_to_fix = $DB->request(
      [
         'SELECT'    => ['id', 'blueprint'],
         'FROM'      => 'glpi_dcrooms',
         'WHERE'     => [
            'blueprint' => ['LIKE', '%' . $doc_send_url . '%'],
         ],
      ]
   );
   foreach ($elements_to_fix as $data) {
      $data['blueprint'] = $DB->escape($fix_picture_fct($data['blueprint']));
      $DB->update('glpi_dcrooms', $data, ['id' => $data['id']]);
   }
   /** /Make datacenter pictures path relative */

   /** ITIL templates */
   if (!$DB->fieldExists('glpi_itilcategories', 'changetemplates_id')) {
      $migration->addField("glpi_itilcategories", "changetemplates_id", "integer", [
            'after'  => "tickettemplates_id_demand",
            'value'  => 0
         ]
      );
   }
   if (!$DB->fieldExists('glpi_itilcategories', 'problemtemplates_id')) {
      $migration->addField("glpi_itilcategories", "problemtemplates_id", "integer", [
            'after'  => "changetemplates_id",
            'value'  => 0
         ]
      );
   }

   $migration->addKey('glpi_itilcategories', 'changetemplates_id');
   $migration->addKey('glpi_itilcategories', 'problemtemplates_id');
   $migration->addKey('glpi_tickettemplatehiddenfields', 'tickettemplates_id');
   $migration->addKey('glpi_tickettemplatemandatoryfields', 'tickettemplates_id');
   $migration->addKey('glpi_tickettemplatepredefinedfields', 'tickettemplates_id');
   /** /ITiL templates */

   /** /Add Externals events for planning */
   if (!$DB->tableExists('glpi_planningexternalevents')) {
      $query = "CREATE TABLE `glpi_planningexternalevents` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `planningexternaleventtemplates_id` int(11) NOT NULL DEFAULT '0',
         `entities_id` int(11) NOT NULL DEFAULT '0',
         `date` timestamp NULL DEFAULT NULL,
         `users_id` int(11) NOT NULL DEFAULT '0',
         `groups_id` int(11) NOT NULL DEFAULT '0',
         `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
         `text` text COLLATE utf8_unicode_ci,
         `begin` timestamp NULL DEFAULT NULL,
         `end` timestamp NULL DEFAULT NULL,
         `rrule` text COLLATE utf8_unicode_ci,
         `state` int(11) NOT NULL DEFAULT '0',
         `planningeventcategories_id` int(11) NOT NULL DEFAULT '0',
         `background` tinyint(1) NOT NULL DEFAULT '0',
         `date_mod` timestamp NULL DEFAULT NULL,
         `date_creation` timestamp NULL DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `planningexternaleventtemplates_id` (`planningexternaleventtemplates_id`),
         KEY `entities_id` (`entities_id`),
         KEY `date` (`date`),
         KEY `begin` (`begin`),
         KEY `end` (`end`),
         KEY `users_id` (`users_id`),
         KEY `groups_id` (`groups_id`),
         KEY `state` (`state`),
         KEY `planningeventcategories_id` (`planningeventcategories_id`),
         KEY `date_mod` (`date_mod`),
         KEY `date_creation` (`date_creation`)
       ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "add table glpi_planningexternalevents");

      $new_rights = ALLSTANDARDRIGHT + PlanningExternalEvent::MANAGE_BG_EVENTS;
      $migration->addRight('externalevent', $new_rights, [
         'planning' => Planning::READMY
      ]);
   }

   // partial update (for developers)
   if (!$DB->fieldExists('glpi_planningexternalevents', 'planningexternaleventtemplates_id')) {
      $migration->addField('glpi_planningexternalevents', 'planningexternaleventtemplates_id', 'int', [
         'after' => 'id'
      ]);
      $migration->addKey('glpi_planningexternalevents', 'planningexternaleventtemplates_id');
   }

   if (!$DB->tableExists('glpi_planningeventcategories')) {
      $query = "CREATE TABLE `glpi_planningeventcategories` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
         `color` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
         `comment` text COLLATE utf8_unicode_ci,
         `date_mod` timestamp NULL DEFAULT NULL,
         `date_creation` timestamp NULL DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `name` (`name`),
         KEY `date_mod` (`date_mod`),
         KEY `date_creation` (`date_creation`)
       ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "add table glpi_planningeventcategories");
   }

   // partial update (for developers)
   if (!$DB->fieldExists('glpi_planningeventcategories', 'color')) {
      $migration->addField("glpi_planningeventcategories", "color", "string", [
            'after'  => "name"
         ]
      );
   }

   if (!$DB->tableExists('glpi_planningexternaleventtemplates')) {
      $query = "CREATE TABLE `glpi_planningexternaleventtemplates` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `entities_id` int(11) NOT NULL DEFAULT '0',
         `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
         `text` text COLLATE utf8_unicode_ci,
         `comment` text COLLATE utf8_unicode_ci,
         `duration` int(11) NOT NULL DEFAULT '0',
         `before_time` int(11) NOT NULL DEFAULT '0',
         `rrule` text COLLATE utf8_unicode_ci,
         `state` int(11) NOT NULL DEFAULT '0',
         `planningeventcategories_id` int(11) NOT NULL DEFAULT '0',
         `background` tinyint(1) NOT NULL DEFAULT '0',
         `date_mod` timestamp NULL DEFAULT NULL,
         `date_creation` timestamp NULL DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `entities_id` (`entities_id`),
         KEY `state` (`state`),
         KEY `planningeventcategories_id` (`planningeventcategories_id`),
         KEY `date_mod` (`date_mod`),
         KEY `date_creation` (`date_creation`)
       ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "add table glpi_planningexternaleventtemplates");
   }
   /** /Add Externals events for planning */

   if (!$DB->fieldExists('glpi_entities', 'autopurge_delay')) {
      $migration->addField("glpi_entities", "autopurge_delay", "integer", [
            'after'  => "autoclose_delay",
            'value'  => Entity::CONFIG_NEVER
         ]
      );
   }

   CronTask::Register(
      'Ticket',
      'purgeticket',
      7 * DAY_TIMESTAMP,
      [
         'mode'  => CronTask::MODE_EXTERNAL,
         'state' => CronTask::STATE_DISABLE
      ]
   );
   /** /Add purge delay per entity */

   /** Clean oprhans documents crontask */
   CronTask::Register(
      'Document',
      'cleanorphans',
      7 * DAY_TIMESTAMP,
      [
         'mode'  => CronTask::MODE_EXTERNAL,
         'state' => CronTask::STATE_DISABLE
      ]
   );
   /** /Clean oprhans documents crontask */

   /** Item devices menu config */
   $migration->addConfig(['devices_in_menu' => exportArrayToDB(['Item_DeviceSimcard'])]);
   /** /Item devices menu config */

   if (!$DB->fieldExists("glpi_projects", "auto_percent_done")) {
      $migration->addField("glpi_projects", "auto_percent_done", "bool", [
            'after'  => "percent_done"
         ]
      );
   }
   if (!$DB->fieldExists("glpi_projecttasks", "auto_percent_done")) {
      $migration->addField("glpi_projecttasks", "auto_percent_done", "bool", [
            'after'  => "percent_done"
         ]
      );
   }
   /** /Add "code" field on glpi_itilcategories */
   if (!$DB->fieldExists("glpi_itilcategories", "code")) {
      $migration->addField("glpi_itilcategories", "code", "string", [
            'after'  => "groups_id"
         ]
      );
   }
   /** /Add "code" field on glpi_itilcategories */

   //Add over-quota option to software licenses to allow assignment after all alloted licenses are used
   if (!$DB->fieldExists('glpi_softwarelicenses', 'allow_overquota')) {
      if ($migration->addField('glpi_softwarelicenses', 'allow_overquota', 'bool')) {
         $migration->addKey('glpi_softwarelicenses', 'allow_overquota');
      }
   }

   /** Make software linkable to other itemtypes besides Computers */
   $migration->displayWarning('Updating software tables. This may take several minutes.');
   if (!$DB->tableExists('glpi_items_softwareversions')) {
      $migration->renameTable('glpi_computers_softwareversions', 'glpi_items_softwareversions');
      $migration->changeField(
         'glpi_items_softwareversions',
         'computers_id',
         'items_id',
         "int(11) NOT NULL DEFAULT '0'"
      );
      $migration->addField(
         'glpi_items_softwareversions',
         'itemtype',
         "varchar(100) COLLATE utf8_unicode_ci NOT NULL",
         [
            'after' => 'items_id',
            'update' => "'Computer'", // Defines value for all existing elements
         ]
      );
      $migration->changeField('glpi_items_softwareversions', 'is_deleted_computer', 'is_deleted_item', 'bool');
      $migration->changeField('glpi_items_softwareversions', 'is_template_computer', 'is_template_item', 'bool');
      $migration->addKey('glpi_items_softwareversions', 'itemtype');
      $migration->dropKey('glpi_items_softwareversions', 'computers_id');
      $migration->addKey('glpi_items_softwareversions', 'items_id', 'items_id');
      $migration->addKey('glpi_items_softwareversions', [
         'itemtype',
         'items_id'
      ], 'item');
      $migration->dropKey('glpi_items_softwareversions', 'unicity');
      $migration->migrationOneTable('glpi_items_softwareversions');
      $migration->addKey('glpi_items_softwareversions', [
         'itemtype',
         'items_id',
         'softwareversions_id'
      ], 'unicity', 'UNIQUE');
   }

   if (!$DB->tableExists('glpi_items_softwarelicenses')) {
      $migration->renameTable('glpi_computers_softwarelicenses', 'glpi_items_softwarelicenses');
      $migration->changeField(
         'glpi_items_softwarelicenses',
         'computers_id',
         'items_id',
         "int(11) NOT NULL DEFAULT '0'"
      );
      $migration->addField(
         'glpi_items_softwarelicenses',
         'itemtype',
         "varchar(100) COLLATE utf8_unicode_ci NOT NULL",
         [
            'after' => 'items_id',
            'update' => "'Computer'", // Defines value for all existing elements
         ]
      );
      $migration->addKey('glpi_items_softwarelicenses', 'itemtype');
      $migration->dropKey('glpi_items_softwarelicenses', 'computers_id');
      $migration->addKey('glpi_items_softwarelicenses', 'items_id', 'items_id');
      $migration->addKey('glpi_items_softwarelicenses', [
         'itemtype',
         'items_id'
      ], 'item');
   }

   $migration->addPostQuery(
      $DB->buildUpdate(
         'glpi_configs',
         ['name' => 'purge_item_software_install'],
         ['name' => 'purge_computer_software_install', 'context' => 'core']
      )
   );
   $migration->addPostQuery(
      $DB->buildUpdate(
         'glpi_configs',
         ['name' => 'purge_software_item_install'],
         ['name' => 'purge_software_computer_install', 'context' => 'core']
      )
   );
   /** /Make software linkable to other itemtypes besides Computers */

   /** Add source item id to TicketTask. Used by tasks created by merging tickets */
   if (!$DB->fieldExists('glpi_tickettasks', 'sourceitems_id')) {
      if ($migration->addField('glpi_tickettasks', 'sourceitems_id', "int(11) NOT NULL DEFAULT '0'")) {
         $migration->addKey('glpi_tickettasks', 'sourceitems_id');
      }
   }
   /** /Add source item id to TicketTask. Used by tasks created by merging tickets */

   /** Impact analysis */
   // Impact config
   $migration->addConfig(['impact_assets_list' => '[]']);

   // Impact dependencies
   if (!$DB->tableExists('glpi_impactrelations')) {
      $query = "CREATE TABLE `glpi_impactrelations` (
         `id` INT(11) NOT NULL AUTO_INCREMENT,
         `itemtype_source` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
         `items_id_source` INT(11) NOT NULL DEFAULT '0',
         `itemtype_impacted` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
         `items_id_impacted` INT(11) NOT NULL DEFAULT '0',
         PRIMARY KEY (`id`),
         UNIQUE KEY `unicity` (
            `itemtype_source`,
            `items_id_source`,
            `itemtype_impacted`,
            `items_id_impacted`
         ),
         KEY `source_asset` (`itemtype_source`, `items_id_source`),
         KEY `impacted_asset` (`itemtype_impacted`, `items_id_impacted`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "add table glpi_impacts");
   }

   // Impact compounds
   if (!$DB->tableExists('glpi_impactcompounds')) {
      $query = "CREATE TABLE `glpi_impactcompounds` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(255) NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
            `color` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
            PRIMARY KEY (`id`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "add table glpi_impacts_compounds");
   }

   // Impact parents
   if (!$DB->tableExists('glpi_impactitems')) {
      $query = "CREATE TABLE `glpi_impactitems` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `itemtype` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
            `items_id` INT(11) NOT NULL DEFAULT '0',
            `parent_id` INT(11) NOT NULL DEFAULT '0',
            `zoom` FLOAT NOT NULL DEFAULT '0',
            `pan_x` FLOAT NOT NULL DEFAULT '0',
            `pan_y` FLOAT NOT NULL DEFAULT '0',
            `impact_color` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
            `depends_color` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
            `impact_and_depends_color` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
            `position_x` FLOAT NOT NULL DEFAULT '0',
            `position_y` FLOAT NOT NULL DEFAULT '0',
            `show_depends` TINYINT NOT NULL DEFAULT '1',
            `show_impact` TINYINT NOT NULL DEFAULT '1',
            `max_depth` INT(11) NOT NULL DEFAULT '5',
            PRIMARY KEY (`id`),
            UNIQUE KEY `unicity` (
               `itemtype`,
               `items_id`
            ),
            KEY `source` (`itemtype`, `items_id`),
            KEY `parent_id` (`parent_id`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "add table glpi_impacts_parent");
   }
   /** /Impact analysis */

   /** Default template configuration for changes and problems */
   $migration->addKey('glpi_entities', 'tickettemplates_id');
   if (!$DB->fieldExists('glpi_entities', 'changetemplates_id')) {
      $migration->addField(
         'glpi_entities',
         'changetemplates_id',
         'integer',
         [
            'value'     => -2, // Inherit as default value
            'after'     => 'tickettemplates_id'
         ]
      );
   }
   $migration->addKey('glpi_entities', 'changetemplates_id');
   if (!$DB->fieldExists('glpi_entities', 'problemtemplates_id')) {
      $migration->addField(
         'glpi_entities',
         'problemtemplates_id',
         'integer',
         [
            'value'     => -2, // Inherit as default value
            'after'     => 'changetemplates_id'
         ]
      );
   }
   $migration->addKey('glpi_entities', 'problemtemplates_id');

   $migration->addKey('glpi_profiles', 'tickettemplates_id');
   if (!$DB->fieldExists('glpi_profiles', 'changetemplates_id')) {
      $migration->addField(
         'glpi_profiles',
         'changetemplates_id',
         'integer',
         [
            'value'     => 0, // Inherit as default value
            'after'     => 'tickettemplates_id'
         ]
      );
   }
   $migration->addKey('glpi_profiles', 'changetemplates_id');
   if (!$DB->fieldExists('glpi_profiles', 'problemtemplates_id')) {
      $migration->addField(
         'glpi_profiles',
         'problemtemplates_id',
         'integer',
         [
            'value'     => 0, // Inherit as default value
            'after'     => 'changetemplates_id'
         ]
      );
   }
   $migration->addKey('glpi_profiles', 'problemtemplates_id');
   /** /Default template configuration for changes and problems */

   /** Add Apple File System (All Apple devices since 2017) */
   if (countElementsInTable('glpi_filesystems', ['name' => 'APFS']) === 0) {
      $DB->insertOrDie('glpi_filesystems', [
         'name'   => 'APFS'
      ]);
   }
   /** /Add Apple File System (All Apple devices since 2017) */

   /** Fix indexes */
   $migration->dropKey('glpi_tickettemplatepredefinedfields', 'tickettemplates_id_id_num');
   /** /Fix indexes */

   /** Kanban */
   if (!$DB->tableExists('glpi_items_kanbans')) {
      $query = "CREATE TABLE `glpi_items_kanbans` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
         `items_id` int(11) DEFAULT NULL,
         `users_id` int(11) NOT NULL,
         `state` text COLLATE utf8_unicode_ci,
         `date_mod` timestamp NULL DEFAULT NULL,
         `date_creation` timestamp NULL DEFAULT NULL,
         PRIMARY KEY (`id`),
         UNIQUE KEY `unicity` (`itemtype`,`items_id`,`users_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "add table glpi_kanbans");
   }
   if (!$DB->fieldExists('glpi_users', 'refresh_views')) {
      $migration->changeField('glpi_users', 'refresh_ticket_list', 'refresh_views', 'int(11) DEFAULT NULL');
   }
   $migration->addPostQuery(
      $DB->buildUpdate(
         'glpi_configs',
         ['name' => 'refresh_views'],
         ['name' => 'refresh_ticket_list', 'context' => 'core']
      )
   );
   /** /Kanban */

   /** Add uuid on planning items */
   $planning_items_tables = [
      'glpi_planningexternalevents',
      'glpi_reminders',
      'glpi_projecttasks',
      'glpi_changetasks',
      'glpi_problemtasks',
      'glpi_tickettasks',
   ];
   foreach ($planning_items_tables as $table) {
      if (!$DB->fieldExists($table, 'uuid')) {
         $migration->addField(
            $table,
            'uuid',
            'string',
            [
               'after'  => 'id'
            ]
         );
         $migration->addKey($table, 'uuid', '', 'UNIQUE');
      }
      $migration->addPostQuery(
         $DB->buildUpdate(
            $table,
            [
               'uuid' => new \QueryExpression('UUID()'),
            ],
            [
               'uuid' => null,
            ]
         )
      );
   }
   /** /Add uuid on planning items */

   /** Add glpi_vobjects table for CalDAV server */
   if (!$DB->tableExists('glpi_vobjects')) {
      $query = "CREATE TABLE `glpi_vobjects` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `itemtype` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
            `items_id` int(11) NOT NULL DEFAULT '0',
            `data` text COLLATE utf8_unicode_ci,
            `date_mod` timestamp NULL DEFAULT NULL,
            `date_creation` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unicity` (`itemtype`, `items_id`),
            KEY `item` (`itemtype`,`items_id`),
            KEY `date_mod` (`date_mod`),
            KEY `date_creation` (`date_creation`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "add table glpi_vobjects");
   }
   /** /Add glpi_vobjects table for CalDAV server */

   /** Fix mixed classes case in DB */
   $mixed_case_classes = [
      'AuthLdap' => 'AuthLDAP',
      'Crontask' => 'CronTask',
      'InfoCom'  => 'Infocom',
   ];
   foreach ($mixed_case_classes as $bad_case_classname => $classname) {
      $migration->renameItemtype($bad_case_classname, $classname, false);
   }
   /** /Fix mixed classes case in DB */

   // ************ Keep it at the end **************
   foreach ($ADDTODISPLAYPREF as $type => $tab) {
      $rank = 1;
      foreach ($tab as $newval) {
         $DB->updateOrInsert("glpi_displaypreferences", [
            'rank'      => $rank++
         ], [
            'users_id'  => "0",
            'itemtype'  => $type,
            'num'       => $newval,
         ]);
      }
   }

     /** Add new option to mailcollector */
     $migration->addField("glpi_mailcollectors", "add_cc_to_observer", "tinyint(1) NOT NULL DEFAULT '0'");
     /** /add new option to mailcollector */

   $migration->executeMigration();

   return $updateresult;
}
