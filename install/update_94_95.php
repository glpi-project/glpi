<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
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
         `is_recursive` TINYINT(1) NOT NULL DEFAULT '1',
         `date` timestamp NULL DEFAULT NULL,
         `users_id` int(11) NOT NULL DEFAULT '0',
         `users_id_guests` text COLLATE utf8_unicode_ci,
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
         KEY `is_recursive` (`is_recursive`),
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

   // partial updates (for developers)
   if (!$DB->fieldExists('glpi_planningexternalevents', 'planningexternaleventtemplates_id')) {
      $migration->addField('glpi_planningexternalevents', 'planningexternaleventtemplates_id', 'int', [
         'after' => 'id'
      ]);
      $migration->addKey('glpi_planningexternalevents', 'planningexternaleventtemplates_id');
   }
   if (!$DB->fieldExists('glpi_planningexternalevents', 'is_recursive')) {
      $migration->addField('glpi_planningexternalevents', 'is_recursive', 'bool', [
         'after' => 'entities_id',
         'value' => 1
      ]);
      $migration->addKey('glpi_planningexternalevents', 'is_recursive');
   }
   if (!$DB->fieldExists('glpi_planningexternalevents', 'users_id_guests')) {
      $migration->addField('glpi_planningexternalevents', 'users_id_guests', 'text', [
         'after' => 'users_id',
      ]);
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

   /** Add geolocation to entity */
   $migration->addField("glpi_entities", "latitude", "string");
   $migration->addField("glpi_entities", "longitude", "string");
   $migration->addField("glpi_entities", "altitude", "string");
   /** Add geolocation to entity */

   /** Dashboards */
   $migration->addRight('dashboard', READ | UPDATE | CREATE | PURGE, [
      'config' => UPDATE
   ]);
   if (!$DB->tableExists('glpi_dashboards_dashboards')) {
      $query = "CREATE TABLE `glpi_dashboards_dashboards` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `key` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
         `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
         `context` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'core',
         PRIMARY KEY (`id`),
         UNIQUE KEY `key` (`key`)
      ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "add table glpi_dashboards_dashboards");
   }
   if (!$DB->tableExists('glpi_dashboards_items')) {
      $query = "CREATE TABLE `glpi_dashboards_items` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `dashboards_dashboards_id` int(11) NOT NULL,
        `gridstack_id` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
        `card_id` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
        `x` int(11) DEFAULT NULL,
        `y` int(11) DEFAULT NULL,
        `width` int(11) DEFAULT NULL,
        `height` int(11) DEFAULT NULL,
        `card_options` text COLLATE utf8_unicode_ci,
        PRIMARY KEY (`id`),
        KEY `dashboards_dashboards_id` (`dashboards_dashboards_id`)
      ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "add table glpi_dashboards_items");
   }
   if (!$DB->tableExists('glpi_dashboards_rights')) {
      $query = "CREATE TABLE `glpi_dashboards_rights` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `dashboards_dashboards_id` int(11) NOT NULL,
         `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
         `items_id` int(11) NOT NULL,
         PRIMARY KEY (`id`),
         KEY `dashboards_dashboards_id` (`dashboards_dashboards_id`),
         UNIQUE KEY `unicity` (`dashboards_dashboards_id`, `itemtype`,`items_id`)
       ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "add table glpi_dashboards_rights");
   }

   // migration from previous development versions
   $dashboards = Config::getConfigurationValues('core', ['dashboards']);
   if (count($dashboards)) {
      $dashboards = $dashboards['dashboards'];
      \Glpi\Dashboard\Dashboard::importFromJson($dashboards);
      Config::deleteConfigurationValues('core', ['dashboards']);
   }
   // add default dashboards
   $migration->addConfig([
      'default_dashboard_central'     => 'central',
      'default_dashboard_assets'      => 'assets',
      'default_dashboard_helpdesk'    => 'helpdesk',
      'default_dashboard_mini_ticket' => 'mini-tickets',
   ]);
   if (!$DB->fieldExists('glpi_users', 'default_dashboard_central')) {
      $migration->addField("glpi_users", "default_dashboard_central", "varchar(100) DEFAULT NULL");
   }
   if (!$DB->fieldExists('glpi_users', 'default_dashboard_assets')) {
      $migration->addField("glpi_users", "default_dashboard_assets", "varchar(100) DEFAULT NULL");
   }
   if (!$DB->fieldExists('glpi_users', 'default_dashboard_helpdesk')) {
      $migration->addField("glpi_users", "default_dashboard_helpdesk", "varchar(100) DEFAULT NULL");
   }
   if (!$DB->fieldExists('glpi_users', 'default_dashboard_mini_ticket')) {
      $migration->addField("glpi_users", "default_dashboard_mini_ticket", "varchar(100) DEFAULT NULL");
   }

   // default dasboards
   if (countElementsInTable("glpi_dashboards_dashboards") === 0) {
      $dashboard_obj  = new \Glpi\Dashboard\Dashboard();
      foreach ([
         [
            'key'     => 'central',
            'name'    => __("Central"),
            'context' => 'core',
            '_items'  => [
               [
                  "x" => 0, "y" => 0, "width" => 3, "height" => 2,
                  "gridstack_id" => "bn_count_Computer_4a315743-151c-40cb-a20b-762250668dac",
                  "card_id"      => "bn_count_Computer",
                  "card_options" => "{\"color\":\"#e69393\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 0, "y" => 4, "width" => 3, "height" => 2,
                  "gridstack_id" => "bn_count_NetworkEquipment_099fbc13-b1a7-4178-98a0-32150ebda140",
                  "card_id"      => "bn_count_NetworkEquipment",
                  "card_options" => "{\"color\":\"#91d6db\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 0, "y" => 2, "width" => 3, "height" => 2,
                  "gridstack_id" => "bn_count_Software_0690f524-e826-47a9-b50a-906451196b83",
                  "card_id"      => "bn_count_Software",
                  "card_options" => "{\"color\":\"#aaddac\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 3, "y" => 4, "width" => 3, "height" => 2,
                  "gridstack_id" => "bn_count_Rack_c6502e0a-5991-46b4-a771-7f355137306b",
                  "card_id"      => "bn_count_Rack",
                  "card_options" => "{\"color\":\"#0e87a0\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 3, "y" => 2, "width" => 3, "height" => 2,
                  "gridstack_id" => "bn_count_SoftwareLicense_e755fd06-283e-4479-ba35-2d548f8f8a90",
                  "card_id"      => "bn_count_SoftwareLicense",
                  "card_options" => "{\"color\":\"#27ab3c\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 3, "y" => 0, "width" => 3, "height" => 2,
                  "gridstack_id" => "bn_count_Monitor_7059b94c-583c-4ba7-b100-d40461165318",
                  "card_id"      => "bn_count_Monitor",
                  "card_options" => "{\"color\":\"#b52d30\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 0, "y" => 8, "width" => 4, "height" => 2,
                  "gridstack_id" => "bn_count_User_42be4c38-f4d4-404c-84ae-3ef78b181bd2",
                  "card_id"      => "bn_count_User",
                  "card_options" => "{\"color\":\"#fafafa\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 11, "y" => 0, "width" => 3, "height" => 2,
                  "gridstack_id" => "bn_count_Ticket_a74c0903-3387-4a07-9111-b0938af8f1e7",
                  "card_id"      => "bn_count_Ticket",
                  "card_options" => "{\"color\":\"#ffdc64\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 11, "y" => 4, "width" => 3, "height" => 2,
                  "gridstack_id" => "bn_count_Problem_c1cf5cfb-f626-472e-82a1-49c3e200e746",
                  "card_id"      => "bn_count_Problem",
                  "card_options" => "{\"color\":\"#f08d7b\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 6, "y" => 4, "width" => 5, "height" => 4,
                  "gridstack_id" => "count_Computer_ComputerModel_355a0854-87c4-4bb1-a348-eeb15bf674bf",
                  "card_id"      => "count_Computer_ComputerModel",
                  "card_options" => "{\"color\":\"#edf0f1\",\"widgettype\":\"donut\",\"use_gradient\":\"1\",\"limit\":\"5\"}",
               ], [
                  "x" => 6, "y" => 0, "width" => 5, "height" => 4,
                  "gridstack_id" => "count_Computer_Manufacturer_6129c451-42b5-489d-b693-c362adf32d49",
                  "card_id"      => "count_Computer_Manufacturer",
                  "card_options" => "{\"color\":\"#f8faf9\",\"widgettype\":\"donut\",\"use_gradient\":\"1\",\"limit\":\"5\"}",
               ], [
                  "x" => 14, "y" => 7, "width" => 6, "height" => 5,
                  "gridstack_id" => "top_ticket_user_requester_c74f52a8-046a-4077-b1a6-c9f840d34b82",
                  "card_id"      => "top_ticket_user_requester",
                  "card_options" => "{\"color\":\"#f9fafb\",\"widgettype\":\"hbar\",\"use_gradient\":\"1\",\"limit\":\"5\"}",
               ], [
                  "x" => 11, "y" => 2, "width" => 3, "height" => 2,
                  "gridstack_id" => "bn_count_tickets_late_04c47208-d7e5-4aca-9566-d46e68c45c67",
                  "card_id"      => "bn_count_tickets_late",
                  "card_options" => "{\"color\":\"#f8911f\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 14, "y" => 0, "width" => 12, "height" => 7,
                  "gridstack_id" => "ticket_status_2e4e968b-d4e6-4e33-9ce9-a1aaff53dfde",
                  "card_id"      => "ticket_status",
                  "card_options" => "{\"color\":\"#fafafa\",\"widgettype\":\"stackedbars\",\"use_gradient\":\"0\",\"limit\":\"12\"}",
               ], [
                  "x" => 20, "y" => 7, "width" => 6, "height" => 5,
                  "gridstack_id" => "top_ticket_ITILCategory_37736ba9-d429-4cb3-9058-ef4d111d9269",
                  "card_id"      => "top_ticket_ITILCategory",
                  "card_options" => "{\"color\":\"#fbf9f9\",\"widgettype\":\"hbar\",\"use_gradient\":\"1\",\"limit\":\"5\"}",
               ], [
                  "x" => 3, "y" => 6, "width" => 3, "height" => 2,
                  "gridstack_id" => "bn_count_Printer_517684b0-b064-49dd-943e-fcb6f915e453",
                  "card_id"      => "bn_count_Printer",
                  "card_options" => "{\"color\":\"#365a8f\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 0, "y" => 6, "width" => 3, "height" => 2,
                  "gridstack_id" => "bn_count_Phone_f70c489f-02c1-46e5-978b-94a95b5038ee",
                  "card_id"      => "bn_count_Phone",
                  "card_options" => "{\"color\":\"#d5e1ec\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 11, "y" => 6, "width" => 3, "height" => 2,
                  "gridstack_id" => "bn_count_Change_ab950dbd-cd25-466d-8dff-7dcaca386564",
                  "card_id"      => "bn_count_Change",
                  "card_options" => "{\"color\":\"#cae3c4\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 4, "y" => 8, "width" => 4, "height" => 2,
                  "gridstack_id" => "bn_count_Group_b84a93f2-a26c-49d7-82a4-5446697cc5b0",
                  "card_id"      => "bn_count_Group",
                  "card_options" => "{\"color\":\"#e0e0e0\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 4, "y" => 10, "width" => 4, "height" => 2,
                  "gridstack_id" => "bn_count_Profile_770b35e8-68e9-4b4f-9e09-5a11058f069f",
                  "card_id"      => "bn_count_Profile",
                  "card_options" => "{\"color\":\"#e0e0e0\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 8, "y" => 8, "width" => 3, "height" => 2,
                  "gridstack_id" => "bn_count_Supplier_36ff9011-e4cf-4d89-b9ab-346b9857d734",
                  "card_id"      => "bn_count_Supplier",
                  "card_options" => "{\"color\":\"#c9c9c9\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 8, "y" => 10, "width" => 3, "height" => 2,
                  "gridstack_id" => "bn_count_KnowbaseItem_a3785a56-bed4-4a30-8387-f251f5365b3b",
                  "card_id"      => "bn_count_KnowbaseItem",
                  "card_options" => "{\"color\":\"#c9c9c9\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 0, "y" => 10, "width" => 4, "height" => 2,
                  "gridstack_id" => "bn_count_Entity_9b82951a-ba52-45cc-a2d3-1d238ec37adf",
                  "card_id"      => "bn_count_Entity",
                  "card_options" => "{\"color\":\"#f9f9f9\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 11, "y" => 8, "width" => 3, "height" => 2,
                  "gridstack_id" => "bn_count_Document_7dc7f4b8-61ff-4147-b994-5541bddd7b66",
                  "card_id"      => "bn_count_Document",
                  "card_options" => "{\"color\":\"#b4b4b4\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 11, "y" => 10, "width" => 3, "height" => 2,
                  "gridstack_id" => "bn_count_Project_4d412ee2-8b79-469b-995f-4c0a05ab849d",
                  "card_id"      => "bn_count_Project",
                  "card_options" => "{\"color\":\"#b3b3b3\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ]
            ]
         ], [
            'key'     => 'assets',
            'name'    => __("Assets"),
            'context' => 'core',
            '_items'  => [
               [
                  "x" => 0, "y" => 0, "width" => 4, "height" => 3,
                  "gridstack_id" => "bn_count_Computer_34cfbaf9-a471-4852-b48c-0dadea7644de",
                  "card_id"      => "bn_count_Computer",
                  "card_options" => "{\"color\":\"#f3d0d0\",\"widgettype\":\"bigNumber\"}",
               ], [
                  "x" => 4, "y" => 0, "width" => 4, "height" => 3,
                  "gridstack_id" => "bn_count_Software_60091467-2137-49f4-8834-f6602a482079",
                  "card_id"      => "bn_count_Software",
                  "card_options" => "{\"color\":\"#d1f1a8\",\"widgettype\":\"bigNumber\"}",
               ], [
                  "x" => 8, "y" => 3, "width" => 4, "height" => 3,
                  "gridstack_id" => "bn_count_Printer_c9a385d4-76a3-4971-ad0e-1470efeafacc",
                  "card_id"      => "bn_count_Printer",
                  "card_options" => "{\"color\":\"#5da8d6\",\"widgettype\":\"bigNumber\"}",
               ], [
                  "x" => 12, "y" => 3, "width" => 4, "height" => 3,
                  "gridstack_id" => "bn_count_PDU_60053eb6-8dda-4416-9a4b-afd51889bd09",
                  "card_id"      => "bn_count_PDU",
                  "card_options" => "{\"color\":\"#ffb62f\",\"widgettype\":\"bigNumber\"}",
               ], [
                  "x" => 12, "y" => 0, "width" => 4, "height" => 3,
                  "gridstack_id" => "bn_count_Rack_0fdc196f-20d2-4f63-9ddb-b75c165cc664",
                  "card_id"      => "bn_count_Rack",
                  "card_options" => "{\"color\":\"#f7d79a\",\"widgettype\":\"bigNumber\"}",
               ], [
                  "x" => 16, "y" => 3, "width" => 4, "height" => 3,
                  "gridstack_id" => "bn_count_Phone_c31fde2d-510a-4482-b17d-2f65b61eae08",
                  "card_id"      => "bn_count_Phone",
                  "card_options" => "{\"color\":\"#a0cec2\",\"widgettype\":\"bigNumber\"}",
               ], [
                  "x" => 16, "y" => 0, "width" => 4, "height" => 3,
                  "gridstack_id" => "bn_count_Enclosure_c21ce30a-58c3-456a-81ec-3c5f01527a8f",
                  "card_id"      => "bn_count_Enclosure",
                  "card_options" => "{\"color\":\"#d7e8e4\",\"widgettype\":\"bigNumber\"}",
               ], [
                  "x" => 8, "y" => 0, "width" => 4, "height" => 3,
                  "gridstack_id" => "bn_count_NetworkEquipment_76f1e239-777b-4552-b053-ae5c64190347",
                  "card_id"      => "bn_count_NetworkEquipment",
                  "card_options" => "{\"color\":\"#c8dae4\",\"widgettype\":\"bigNumber\"}",
               ], [
                  "x" => 4, "y" => 3, "width" => 4, "height" => 3,
                  "gridstack_id" => "bn_count_SoftwareLicense_576e58fe-a386-480f-b405-1c2315b8ab47",
                  "card_id"      => "bn_count_SoftwareLicense",
                  "card_options" => "{\"color\":\"#9bc06b\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 0, "y" => 3, "width" => 4, "height" => 3,
                  "gridstack_id" => "bn_count_Monitor_890e16d3-b121-48c6-9713-d9c239d9a970",
                  "card_id"      => "bn_count_Monitor",
                  "card_options" => "{\"color\":\"#dc6f6f\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 4, "y" => 6, "width" => 4, "height" => 4,
                  "gridstack_id" => "count_Computer_Manufacturer_986e92e8-32e8-4a6f-806f-6f5383acbb3f",
                  "card_id"      => "count_Computer_Manufacturer",
                  "card_options" => "{\"color\":\"#f3f5f1\",\"widgettype\":\"hbar\",\"use_gradient\":\"1\",\"limit\":\"5\"}",
               ], [
                  "x" => 0, "y" => 6, "width" => 4, "height" => 4,
                  "gridstack_id" => "count_Computer_State_290c5920-9eab-4db8-8753-46108e60f1d8",
                  "card_id"      => "count_Computer_State",
                  "card_options" => "{\"color\":\"#fbf7f7\",\"widgettype\":\"donut\",\"use_gradient\":\"1\",\"limit\":\"5\"}",
               ], [
                  "x" => 8, "y" => 6, "width" => 4, "height" => 4,
                  "gridstack_id" => "count_Computer_ComputerType_c58f9c7e-22d5-478b-8226-d2a752bcbb09",
                  "card_id"      => "count_Computer_ComputerType",
                  "card_options" => "{\"color\":\"#f5f9fa\",\"widgettype\":\"donut\",\"use_gradient\":\"1\",\"limit\":\"5\"}",
               ], [
                  "x" => 12, "y" => 6, "width" => 4, "height" => 4,
                  "gridstack_id" => "count_NetworkEquipment_Manufacturer_8132b21c-6f7f-4dc1-af54-bea794cb96e9",
                  "card_id"      => "count_NetworkEquipment_Manufacturer",
                  "card_options" => "{\"color\":\"#fcf8ed\",\"widgettype\":\"hbar\",\"use_gradient\":\"0\",\"limit\":\"5\"}",
               ], [
                  "x" => 16, "y" => 6, "width" => 4, "height" => 4,
                  "gridstack_id" => "count_Monitor_Manufacturer_43b0c16b-af82-418e-aac1-f32b39705c0d",
                  "card_id"      => "count_Monitor_Manufacturer",
                  "card_options" => "{\"color\":\"#f9fbfb\",\"widgettype\":\"donut\",\"use_gradient\":\"1\",\"limit\":\"5\"}",
               ]
            ]
         ], [
            'key'     => 'assistance',
            'name'    => __("Assistance"),
            'context' => 'core',
            '_items'  => [
               [
                  "x" => 0, "y" => 0, "width" => 3, "height" => 2,
                  "gridstack_id" => "bn_count_Ticket_344e761b-f7e8-4617-8c90-154b266b4d67",
                  "card_id"      => "bn_count_Ticket",
                  "card_options" => "{\"color\":\"#ffdc64\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 0, "y" => 4, "width" => 3, "height" => 2,
                  "gridstack_id" => "bn_count_Problem_bdb4002b-a674-4493-820f-af85bed44d2a",
                  "card_id"      => "bn_count_Problem",
                  "card_options" => "{\"color\":\"#f0967b\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 0, "y" => 6, "width" => 3, "height" => 2,
                  "gridstack_id" => "bn_count_Change_b9b87513-4f40-41e6-8621-f51f9a30fb19",
                  "card_id"      => "bn_count_Change",
                  "card_options" => "{\"color\":\"#cae3c4\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 0, "y" => 2, "width" => 3, "height" => 2,
                  "gridstack_id" => "bn_count_tickets_late_1e9ae481-21b4-4463-a830-dec1b68ec5e7",
                  "card_id"      => "bn_count_tickets_late",
                  "card_options" => "{\"color\":\"#f8911f\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 3, "y" => 6, "width" => 3, "height" => 2,
                  "gridstack_id" => "bn_count_tickets_incoming_336a36d9-67fe-4475-880e-447bd766b8fe",
                  "card_id"      => "bn_count_tickets_incoming",
                  "card_options" => "{\"color\":\"#a0e19d\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 9, "y" => 8, "width" => 3, "height" => 2,
                  "gridstack_id" => "bn_count_tickets_closed_e004bab5-f2b6-4060-a401-a2a8b9885245",
                  "card_id"      => "bn_count_tickets_closed",
                  "card_options" => "{\"color\":\"#515151\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 6, "y" => 6, "width" => 3, "height" => 2,
                  "gridstack_id" => "bn_count_tickets_assigned_7455c855-6df8-4514-a3d9-8b0fce52bd63",
                  "card_id"      => "bn_count_tickets_assigned",
                  "card_options" => "{\"color\":\"#eaf5f7\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 9, "y" => 6, "width" => 3, "height" => 2,
                  "gridstack_id" => "bn_count_tickets_solved_5e9759b3-ee7e-4a14-b68f-1ac024ef55ee",
                  "card_id"      => "bn_count_tickets_solved",
                  "card_options" => "{\"color\":\"#d8d8d8\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 3, "y" => 8, "width" => 3, "height" => 2,
                  "gridstack_id" => "bn_count_tickets_waiting_102b2c2a-6ac6-4d73-ba47-8b09382fe00e",
                  "card_id"      => "bn_count_tickets_waiting",
                  "card_options" => "{\"color\":\"#ffcb7d\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 0, "y" => 8, "width" => 3, "height" => 2,
                  "gridstack_id" => "bn_count_TicketRecurrent_13f79539-61f6-45f7-8dde-045706e652f2",
                  "card_id"      => "bn_count_TicketRecurrent",
                  "card_options" => "{\"color\":\"#fafafa\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 6, "y" => 8, "width" => 3, "height" => 2,
                  "gridstack_id" => "bn_count_tickets_planned_267bf627-9d5e-4b6c-b53d-b8623d793ccf",
                  "card_id"      => "bn_count_tickets_planned",
                  "card_options" => "{\"color\":\"#6298d5\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 12, "y" => 6, "width" => 4, "height" => 4,
                  "gridstack_id" => "top_ticket_ITILCategory_0cba0c84-6c62-4cd8-8564-18614498d8e4",
                  "card_id"      => "top_ticket_ITILCategory",
                  "card_options" => "{\"color\":\"#f1f5ef\",\"widgettype\":\"donut\",\"use_gradient\":\"1\",\"limit\":\"7\"}",
               ], [
                  "x" => 16, "y" => 6, "width" => 4, "height" => 4,
                  "gridstack_id" => "top_ticket_RequestType_b9e43f34-8e94-4a6e-9023-c5d1e2ce7859",
                  "card_id"      => "top_ticket_RequestType",
                  "card_options" => "{\"color\":\"#f9fafb\",\"widgettype\":\"hbar\",\"use_gradient\":\"1\",\"limit\":\"4\"}",
               ], [
                  "x" => 20, "y" => 6, "width" => 4, "height" => 4,
                  "gridstack_id" => "top_ticket_Entity_a8e65812-519c-488e-9892-9adbe22fbd5c",
                  "card_id"      => "top_ticket_Entity",
                  "card_options" => "{\"color\":\"#f7f1f0\",\"widgettype\":\"donut\",\"use_gradient\":\"1\",\"limit\":\"7\"}",
               ], [
                  "x" => 3, "y" => 0, "width" => 12, "height" => 6,
                  "gridstack_id" => "ticket_evolution_76fd4926-ee5e-48db-b6d6-e2947c190c5e",
                  "card_id"      => "ticket_evolution",
                  "card_options" => "{\"color\":\"#f3f7f8\",\"widgettype\":\"areas\",\"use_gradient\":\"0\",\"limit\":\"12\"}",
               ], [
                  "x" => 15, "y" => 0, "width" => 11, "height" => 6,
                  "gridstack_id" => "ticket_status_5b256a35-b36b-4db5-ba11-ea7c125f126e",
                  "card_id"      => "ticket_status",
                  "card_options" => "{\"color\":\"#f7f3f2\",\"widgettype\":\"stackedbars\",\"use_gradient\":\"0\",\"limit\":\"12\"}",
               ]
            ]
         ], [
            'key'     => 'mini-tickets',
            'name'    => __("Mini tickets dashboard"),
            'context' => 'mini_core',
            '_items'  => [
               [
                  "x" => 24, "y" => 0, "width" => 4, "height" => 2,
                  "gridstack_id" => "bn_count_tickets_closed_ccf7246b-645a-40d2-8206-fa33c769e3f5",
                  "card_id"      => "bn_count_tickets_closed",
                  "card_options" => "{\"color\":\"#fafafa\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 0, "y" => 0, "width" => 4, "height" => 2,
                  "gridstack_id" => "bn_count_Ticket_d5bf3576-5033-40fb-bbdb-292294a7698e",
                  "card_id"      => "bn_count_Ticket",
                  "card_options" => "{\"color\":\"#ffd957\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 4, "y" => 0, "width" => 4, "height" => 2,
                  "gridstack_id" => "bn_count_tickets_incoming_055e813c-b0ce-4687-91ef-559249e8ddd8",
                  "card_id"      => "bn_count_tickets_incoming",
                  "card_options" => "{\"color\":\"#6fd169\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 8, "y" => 0, "width" => 4, "height" => 2,
                  "gridstack_id" => "bn_count_tickets_waiting_793c665b-b620-4b3a-a5a8-cf502defc008",
                  "card_id"      => "bn_count_tickets_waiting",
                  "card_options" => "{\"color\":\"#ffcb7d\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 12, "y" => 0, "width" => 4, "height" => 2,
                  "gridstack_id" => "bn_count_tickets_assigned_d3d2f697-52b4-435e-9030-a760dd649085",
                  "card_id"      => "bn_count_tickets_assigned",
                  "card_options" => "{\"color\":\"#eaf4f7\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 16, "y" => 0, "width" => 4, "height" => 2,
                  "gridstack_id" => "bn_count_tickets_planned_0c7f3569-c23b-4ee3-8e85-279229b23e70",
                  "card_id"      => "bn_count_tickets_planned",
                  "card_options" => "{\"color\":\"#6298d5\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ], [
                  "x" => 20, "y" => 0, "width" => 4, "height" => 2,
                  "gridstack_id" => "bn_count_tickets_solved_ae2406cf-e8e8-410b-b355-46e3f5705ee8",
                  "card_id"      => "bn_count_tickets_solved",
                  "card_options" => "{\"color\":\"#d7d7d7\",\"widgettype\":\"bigNumber\",\"use_gradient\":\"0\",\"limit\":\"7\"}",
               ]
            ]
         ]
      ] as $default_dashboard) {
         $items = $default_dashboard['_items'];
         unset($default_dashboard['_items']);

         // add current dashboard
         $dashboard_id = $dashboard_obj->add($default_dashboard);

         // add items to this new dashboard
         $query = $DB->buildInsert(
            \Glpi\Dashboard\Item::getTable(),
            [
               'dashboards_dashboards_id' => new QueryParam(),
               'gridstack_id'             => new QueryParam(),
               'card_id'                  => new QueryParam(),
               'x'                        => new QueryParam(),
               'y'                        => new QueryParam(),
               'width'                    => new QueryParam(),
               'height'                   => new QueryParam(),
               'card_options'             => new QueryParam(),
            ]
         );
         $stmt = $DB->prepare($query);
         foreach ($items as $item) {
            $stmt->bind_param('issiiiis',
               $dashboard_id,
               $item['gridstack_id'],
               $item['card_id'],
               $item['x'],
               $item['y'],
               $item['width'],
               $item['height'],
               $item['card_options'],
            );
            $stmt->execute();
         }
      }
   }
   /** /Dashboards */

   /** Domains */
   if (!$DB->tableExists('glpi_domaintypes')) {
      $query = "CREATE TABLE `glpi_domaintypes` (
            `id` int(11) NOT NULL        AUTO_INCREMENT,
            `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
            `entities_id` int(11) NOT NULL        DEFAULT '0',
            `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
            `comment` text COLLATE utf8_unicode_ci,
            PRIMARY KEY (`id`),
            KEY `name` (`name`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "add table glpi_domaintypes");
   }

   $dfields = [
      'domaintypes_id'        => 'integer',
      'date_expiration'       => 'timestamp',
      'users_id_tech'         => 'integer',
      'groups_id_tech'        => 'integer',
      'others'                => 'string',
      'is_helpdesk_visible'   => 'boolean',
      'is_deleted'            => 'boolean',
   ];
   $dindex = $dfields;
   unset($dindex['others']);
   $dindex = array_keys($dindex);
   $dindex[] = 'entities_id';

   $after = 'is_recursive';
   foreach ($dfields as $dfield => $dtype) {
      if (!$DB->fieldExists('glpi_domains', $dfield)) {
         $options = ['after' => $after];
         if ($dfield == 'is_helpdesk_visible') {
            $options['value'] = 1;
         }
         $migration->addField("glpi_domains", $dfield, $dtype, $options);
      }
      $after = $dfield;
   }

   //add indexes
   foreach ($dindex as $didx) {
      $migration->addKey('glpi_domains', $didx);
   }

   if (!$DB->tableExists('glpi_domains_items')) {
      $query = "CREATE TABLE `glpi_domains_items` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `domains_id` int(11) NOT NULL DEFAULT '0',
            `items_id` int(11) NOT NULL DEFAULT '0',
            `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unicity` (`domains_id`, `itemtype`, `items_id`),
            KEY `domains_id` (`domains_id`),
            KEY `FK_device` (`items_id`, `itemtype`),
            KEY `item` (`itemtype`, `items_id`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "add table glpi_domains_items");
   }

   foreach (['Computer', 'NetworkEquipment', 'Printer'] as $itemtype) {
      if ($DB->fieldExists($itemtype::getTable(), 'domains_id')) {
         $iterator = $DB->request([
            'SELECT' => ['id', 'domains_id'],
            'FROM'   => $itemtype::getTable(),
            'WHERE'  => ['domains_id' => ['>', 0]]
         ]);
         if (count($iterator)) {
            //migrate existing data
            $migration->migrationOneTable('glpi_domains_items');
            while ($row = $iterator->next()) {
               $DB->insert("glpi_domains_items", [
                  'domains_id'   => $row['domains_id'],
                  'itemtype'     => $itemtype,
                  'items_id'     => $row['id']
               ]);
            }
         }
         $migration->dropField($itemtype::getTable(), 'domains_id');
      }
   }

   if (!$DB->fieldExists('glpi_entities', 'use_domains_alert')) {
      $migration->addField("glpi_entities", "use_domains_alert", "integer", [
            'after'  => "use_reservations_alert",
            'value'  => -2
         ]
      );
   }

   if (!$DB->fieldExists('glpi_entities', 'send_domains_alert_close_expiries_delay')) {
      $migration->addField("glpi_entities", "send_domains_alert_close_expiries_delay", "integer", [
            'after'  => "use_domains_alert",
            'value'  => -2
         ]
      );
   }

   if (!$DB->fieldExists('glpi_entities', 'send_domains_alert_expired_delay')) {
      $migration->addField("glpi_entities", "send_domains_alert_expired_delay", "integer", [
            'after'  => "send_domains_alert_close_expiries_delay",
            'value'  => -2
         ]
      );
   }

   $ADDTODISPLAYPREF['domain'] = [3, 4, 2, 6, 7];
   $ADDTODISPLAYPREF['domainrecord'] = [2, 3, ];

   //update preferences
   $migration->addPostQuery(
      $DB->buildUpdate(
         'glpi_displaypreferences', [
            'num'       => '205',
         ], [
            'num'       => '33',
            'itemtype'  => [
               'Computer',
               'NetworkEquipment',
               'Printer'
            ]
         ]
      )
   );
   /** /Domains */

   /** Domains relations */
   if (!$DB->tableExists('glpi_domainrelations')) {
      $query = "CREATE TABLE `glpi_domainrelations` (
            `id` int(11) NOT NULL        AUTO_INCREMENT,
            `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
            `entities_id` int(11) NOT NULL        DEFAULT '0',
            `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
            `comment` text COLLATE utf8_unicode_ci,
            PRIMARY KEY (`id`),
            KEY `name` (`name`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "add table glpi_domainrelations");
      $relations = DomainRelation::getDefaults();
      foreach ($relations as $relation) {
         $migration->addPostQuery(
            $DB->buildInsert(
               DomainRelation::getTable(),
               $relation
            )
         );
      }
   }

   if (!$DB->fieldExists('glpi_domains_items', 'domainrelations_id')) {
      $migration->addField('glpi_domains_items', 'domainrelations_id', 'integer');
      $migration->addKey('glpi_domains_items', 'domainrelations_id');
   }
   /** /Domains relations */

   /** Domain records */
   if (!$DB->tableExists('glpi_domainrecordtypes')) {
      $query = "CREATE TABLE `glpi_domainrecordtypes` (
            `id` int(11) NOT NULL        AUTO_INCREMENT,
            `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
            `entities_id` int(11) NOT NULL        DEFAULT '0',
            `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
            `comment` text COLLATE utf8_unicode_ci,
            PRIMARY KEY (`id`),
            KEY `name` (`name`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "add table glpi_domainrecordtypes");
      $types = DomainRecordType::getDefaults();
      foreach ($types as $type) {
         $migration->addPostQuery(
            $DB->buildInsert(
               DomainRecordType::getTable(),
               $type
            )
         );
      }
   }

   if (!$DB->tableExists('glpi_domainrecords')) {
      $query = "CREATE TABLE `glpi_domainrecords` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
            `data` text COLLATE utf8_unicode_ci DEFAULT NULL,
            `entities_id` int(11) NOT NULL DEFAULT '0',
            `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
            `domains_id` int(11) NOT NULL DEFAULT '0',
            `domainrecordtypes_id` int(11) NOT NULL DEFAULT '0',
            `ttl` int(11) NOT NULL,
            `users_id_tech` int(11) NOT NULL DEFAULT '0',
            `groups_id_tech` int(11) NOT NULL DEFAULT '0',
            `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
            `comment` text COLLATE utf8_unicode_ci,
            `date_mod` timestamp NULL DEFAULT NULL,
            `date_creation` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `name` (`name`),
            KEY `entities_id` (`entities_id`),
            KEY `domains_id` (`domains_id`),
            KEY `domainrecordtypes_id` (`domainrecordtypes_id`),
            KEY `users_id_tech` (`users_id_tech`),
            KEY `groups_id_tech` (`groups_id_tech`),
            KEY `date_mod` (`date_mod`),
            KEY `is_deleted` (`is_deleted`),
            KEY `date_creation` (`date_creation`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "add table glpi_domainrecords");
   }

   if ($DB->fieldExists('glpi_domainrecords', 'status')) {
      $migration->dropField('glpi_domainrecords', 'status');
   }

   /** /Domain records */

   /** Domains expiration notifications */
   if (countElementsInTable('glpi_notifications', ['itemtype' => 'Domain']) === 0) {
      $DB->insertOrDie(
         'glpi_notificationtemplates',
         [
            'name'            => 'Alert domains',
            'itemtype'        => 'Domain',
            'date_mod'        => new \QueryExpression('NOW()'),
         ],
         'Add domains expiration notification template'
      );
      $notificationtemplate_id = $DB->insertId();

      $DB->insertOrDie(
         'glpi_notificationtemplatetranslations',
         [
            'notificationtemplates_id' => $notificationtemplate_id,
            'language'                 => '',
            'subject'                  => '##domain.action## : ##domain.entity##',
            'content_text'             => <<<PLAINTEXT
##lang.domain.entity## :##domain.entity##
##FOREACHdomains##
##lang.domain.name## : ##domain.name## - ##lang.domain.dateexpiration## : ##domain.dateexpiration##
##ENDFOREACHdomains##
PLAINTEXT
            ,
            'content_html'             => <<<HTML
&lt;p&gt;##lang.domain.entity## :##domain.entity##&lt;br /&gt; &lt;br /&gt;
##FOREACHdomains##&lt;br /&gt;
##lang.domain.name##  : ##domain.name## - ##lang.domain.dateexpiration## :  ##domain.dateexpiration##&lt;br /&gt;
##ENDFOREACHdomains##&lt;/p&gt;
HTML
            ,
         ],
         'Add domains expiration notification template translations'
      );

      $notifications_data = [
         [
            'event' => 'ExpiredDomains',
            'name'  => 'Alert expired domains',
         ],
         [
            'event' => 'DomainsWhichExpire',
            'name'  => 'Alert domains close expiries',
         ]
      ];
      foreach ($notifications_data as $notification_data) {
         $DB->insertOrDie(
            'glpi_notifications',
            [
               'name'            => $notification_data['name'],
               'entities_id'     => 0,
               'itemtype'        => 'Domain',
               'event'           => $notification_data['event'],
               'comment'         => null,
               'is_recursive'    => 1,
               'is_active'       => 1,
               'date_creation'   => new \QueryExpression('NOW()'),
               'date_mod'        => new \QueryExpression('NOW()'),
            ],
            'Add domains expiration notification'
         );
         $notification_id = $DB->insertId();

         $DB->insertOrDie(
            'glpi_notifications_notificationtemplates',
            [
               'notifications_id'         => $notification_id,
               'mode'                     => Notification_NotificationTemplate::MODE_MAIL,
               'notificationtemplates_id' => $notificationtemplate_id,
            ],
            'Add domains expiration notification template instance'
         );

         $DB->insertOrDie(
            'glpi_notificationtargets',
            [
               'items_id'         => Notification::ITEM_TECH_IN_CHARGE,
               'type'             => 1,
               'notifications_id' => $notification_id,
            ],
            'Add domains expiration notification targets'
         );

         $DB->insertOrDie(
            'glpi_notificationtargets',
            [
               'items_id'         => Notification::ITEM_TECH_GROUP_IN_CHARGE,
               'type'             => 1,
               'notifications_id' => $notification_id,
            ],
            'Add domains expiration notification targets'
         );
      }
   }
   /** /Domains expiration notifications */

   /** Impact context */

   // Create new impact_context table
   if (!$DB->tableExists('glpi_impactcontexts')) {
      $query = "CREATE TABLE `glpi_impactcontexts` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `positions` TEXT NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
            `zoom` FLOAT NOT NULL DEFAULT '0',
            `pan_x` FLOAT NOT NULL DEFAULT '0',
            `pan_y` FLOAT NOT NULL DEFAULT '0',
            `impact_color` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
            `depends_color` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
            `impact_and_depends_color` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
            `show_depends` TINYINT(1) NOT NULL DEFAULT '1',
            `show_impact` TINYINT(1) NOT NULL DEFAULT '1',
            `max_depth` INT(11) NOT NULL DEFAULT '5',
            PRIMARY KEY (`id`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "add table glpi_impactcontexts");

      // Update glpi_impactitems
      $migration->dropField("glpi_impactitems", "zoom");
      $migration->dropField("glpi_impactitems", "pan_x");
      $migration->dropField("glpi_impactitems", "pan_y");
      $migration->dropField("glpi_impactitems", "impact_color");
      $migration->dropField("glpi_impactitems", "depends_color");
      $migration->dropField("glpi_impactitems", "impact_and_depends_color");
      $migration->dropField("glpi_impactitems", "position_x");
      $migration->dropField("glpi_impactitems", "position_y");
      $migration->dropField("glpi_impactitems", "show_depends");
      $migration->dropField("glpi_impactitems", "show_impact");
      $migration->dropField("glpi_impactitems", "max_depth");
      $migration->addField("glpi_impactitems", "impactcontexts_id", "integer");
      $migration->addField("glpi_impactitems", "is_slave", "bool", ['value' => 1]);
      $migration->addKey("glpi_impactitems", "impactcontexts_id", "impactcontexts_id");
   }
   /** /Impact context */

   /** SSO logout URL */
   $migration->addConfig(['ssologout_url' => '']);
   /** SSO logout URL */

   /** A doc_item to rule them all! */
   $itemtypes = [
      'ITILFollowup' => 'content',
      'ITILSolution' => 'content',
      'Reminder'     => 'text',
      'KnowbaseItem' => 'answer'
   ];
   foreach (['Change', 'Problem', 'Ticket'] as $itiltype) {
      $itemtypes[$itiltype] = 'content';
      $itemtypes[$itiltype . 'Task'] = 'content';
   }
   $docs_input =[];
   foreach ($itemtypes as $itemtype => $field) {
      // Check ticket and child items (followups, tasks, solutions) contents
      $regexPattern = 'document\\\.send\\\.php\\\?docid=[0-9]+';
      $user_field = is_a($itemtype, CommonITILObject::class, true) ? 'users_id_recipient' : 'users_id';
      $result = $DB->request([
         'SELECT' => ['id', $field, $user_field],
         'FROM'   => $itemtype::getTable(),
         'WHERE'  => [
            $field => ['REGEXP', $regexPattern]
         ]
      ]);
      while ($data = $result->next()) {
         preg_match('/document\\.send\\.php\\?docid=([0-9]+)/', $data[$field], $matches);
         $docs_input[] = [
            'documents_id'       => $matches[1],
            'itemtype'           => $itemtype,
            'items_id'           => $data['id'],
            'timeline_position'  => CommonITILObject::NO_TIMELINE,
            'users_id'           => $data[$user_field],
         ];
      }
   }
   $ditem = new Document_Item();
   foreach ($docs_input as $doc_input) {
      if (!$ditem->getFromDBbyCrit($doc_input)) {
         $ditem->add($doc_input);
      }
   }
   /** /A doc_item to rule them all! */

   /** Appliances & webapps */
   require __DIR__ . '/update_94_95/appliances.php';
   /** /Appliances & webapps */

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
   $migration->addField("glpi_mailcollectors", "add_cc_to_observer", "boolean");
   /** /add new option to mailcollector */

   /** Password expiration policy */
   $migration->addConfig(
      [
         'password_expiration_delay'      => '-1',
         'password_expiration_notice'     => '-1',
         'password_expiration_lock_delay' => '-1',
      ]
   );
   if (!$DB->fieldExists('glpi_users', 'password_last_update')) {
      $migration->addField(
         'glpi_users',
         'password_last_update',
         'timestamp',
         [
            'null'   => true,
            'after'  => 'password',
         ]
      );
   }
   $passwordexpires_notif_count = countElementsInTable(
      'glpi_notifications',
      [
         'itemtype' => 'User',
         'event'    => 'passwordexpires',
      ]
   );
   if ($passwordexpires_notif_count === 0) {
      $DB->insertOrDie(
         'glpi_notifications',
         [
            'name'            => 'Password expires alert',
            'entities_id'     => 0,
            'itemtype'        => 'User',
            'event'           => 'passwordexpires',
            'comment'         => null,
            'is_recursive'    => 1,
            'is_active'       => 1,
            'date_creation'   => new \QueryExpression('NOW()'),
            'date_mod'        => new \QueryExpression('NOW()'),
         ],
         'Add password expires notification'
      );
      $notification_id = $DB->insertId();

      $DB->insertOrDie(
         'glpi_notificationtemplates',
         [
            'name'            => 'Password expires alert',
            'itemtype'        => 'User',
            'date_mod'        => new \QueryExpression('NOW()'),
         ],
         'Add password expires notification template'
      );
      $notificationtemplate_id = $DB->insertId();

      $DB->insertOrDie(
         'glpi_notifications_notificationtemplates',
         [
            'notifications_id'         => $notification_id,
            'mode'                     => Notification_NotificationTemplate::MODE_MAIL,
            'notificationtemplates_id' => $notificationtemplate_id,
         ],
         'Add password expires notification template instance'
      );

      $DB->insertOrDie(
         'glpi_notificationtargets',
         [
            'items_id'         => 19,
            'type'             => 1,
            'notifications_id' => $notification_id,
         ],
         'Add password expires notification targets'
      );

      $DB->insertOrDie(
         'glpi_notificationtemplatetranslations',
         [
            'notificationtemplates_id' => $notificationtemplate_id,
            'language'                 => '',
            'subject'                  => '##user.action##',
            'content_text'             => <<<PLAINTEXT
##user.realname## ##user.firstname##,

##IFuser.password.has_expired=1##
##lang.password.has_expired.information##
##ENDIFuser.password.has_expired##
##ELSEuser.password.has_expired##
##lang.password.expires_soon.information##
##ENDELSEuser.password.has_expired##
##lang.user.password.expiration.date##: ##user.password.expiration.date##
##IFuser.account.lock.date##
##lang.user.account.lock.date##: ##user.account.lock.date##
##ENDIFuser.account.lock.date##

##password.update.link## ##user.password.update.url##
PLAINTEXT
            ,
            'content_html'             => <<<HTML
&lt;p&gt;&lt;strong&gt;##user.realname## ##user.firstname##&lt;/strong&gt;&lt;/p&gt;

##IFuser.password.has_expired=1##
&lt;p&gt;##lang.password.has_expired.information##&lt;/p&gt;
##ENDIFuser.password.has_expired##
##ELSEuser.password.has_expired##
&lt;p&gt;##lang.password.expires_soon.information##&lt;/p&gt;
##ENDELSEuser.password.has_expired##
&lt;p&gt;##lang.user.password.expiration.date##: ##user.password.expiration.date##&lt;/p&gt;
##IFuser.account.lock.date##
&lt;p&gt;##lang.user.account.lock.date##: ##user.account.lock.date##&lt;/p&gt;
##ENDIFuser.account.lock.date##

&lt;p&gt;##lang.password.update.link## &lt;a href="##user.password.update.url##"&gt;##user.password.update.url##&lt;/a&gt;&lt;/p&gt;
HTML
            ,
         ],
         'Add password expires notification template translations'
      );
   }
   CronTask::Register(
      'User',
      'passwordexpiration',
      DAY_TIMESTAMP,
      [
         'mode'  => CronTask::MODE_EXTERNAL,
         'state' => CronTask::STATE_DISABLE,
         'param' => 100,
      ]
   );
   /** /Password expiration policy */

   /** Update default right assignement rule (only it exactly match previous default rule) */
   $prev_rule = [
      'entities_id'  => 0,
      'sub_type'     => 'RuleRight',
      'ranking'      => 1,
      'name'         => 'Root',
      'description'  => '',
      'match'        => 'OR',
      'is_active'    => 1,
      'is_recursive' => 0,
      'uuid'         => '500717c8-2bd6e957-53a12b5fd35745.02608131',
      'condition'    => 0,
   ];
   $rule = new Rule();
   if ($rule->getFromDBByCrit($prev_rule)) {
      $rule->getRuleWithCriteriasAndActions($rule->fields['id'], true, true);

      $prev_criteria = [
         [
            'rules_id'  => $rule->fields['id'],
            'criteria'  => 'uid',
            'condition' => 0,
            'pattern'   => '*',
         ],
         [
            'rules_id'  => $rule->fields['id'],
            'criteria'  => 'samaccountname',
            'condition' => 0,
            'pattern'   => '*',
         ],
         [
            'rules_id'  => $rule->fields['id'],
            'criteria'  => 'MAIL_EMAIL',
            'condition' => 0,
            'pattern'   => '*',
         ],
      ];
      $prev_actions = [
         [
            'rules_id'    => $rule->fields['id'],
            'action_type' => 'assign',
            'field'       => 'entities_id',
            'value'       => '0',
         ],
      ];

      $matching_criteria = 0;
      foreach ($rule->criterias as $criteria) {
         $existing_criteria = $criteria->fields;
         unset($existing_criteria['id']);
         if (in_array($existing_criteria, $prev_criteria)) {
            $matching_criteria++;
         }
      }

      $matching_actions  = 0;
      foreach ($rule->actions as $action) {
         $existing_action = $action->fields;
         unset($existing_action['id']);
         if (in_array($existing_action, $prev_actions)) {
            $matching_actions++;
         }
      }

      if (count($rule->criterias) == count($prev_criteria)
          && count($rule->criterias) == $matching_criteria
          && count($rule->actions) == count($prev_actions)
          && count($rule->actions) == $matching_actions) {
         // rule matches previous default rule (same criteria and actions)
         // so we can replace criteria
         $DB->deleteOrDie('glpi_rulecriterias', ['rules_id' => $rule->fields['id']]);
         $DB->insertOrDie(
            'glpi_rulecriterias',
            [
               'rules_id'  => $rule->fields['id'],
               'criteria'  => 'TYPE',
               'condition' => 0,
               'pattern'   => Auth::LDAP,
            ],
            'Update default right assignement rule'
         );
         $DB->insertOrDie(
            'glpi_rulecriterias',
            [
               'rules_id'  => $rule->fields['id'],
               'criteria'  => 'TYPE',
               'condition' => 0,
               'pattern'   => Auth::MAIL,
            ],
            'Update default right assignement rule'
         );
      }
   }
   /** /Update default right assignement rule */

   /** Passive Datacenter equipments */
   if (!$DB->tableExists('glpi_passivedcequipments')) {
      $query = "CREATE TABLE `glpi_passivedcequipments` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
         `entities_id` int(11) NOT NULL DEFAULT '0',
         `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
         `locations_id` int(11) NOT NULL DEFAULT '0',
         `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
         `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
         `passivedcequipmentmodels_id` int(11) DEFAULT NULL,
         `passivedcequipmenttypes_id` int(11) NOT NULL DEFAULT '0',
         `users_id_tech` int(11) NOT NULL DEFAULT '0',
         `groups_id_tech` int(11) NOT NULL DEFAULT '0',
         `is_template` tinyint(1) NOT NULL DEFAULT '0',
         `template_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
         `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
         `states_id` int(11) NOT NULL DEFAULT '0' COMMENT 'RELATION to states (id)',
         `comment` text COLLATE utf8_unicode_ci,
         `manufacturers_id` int(11) NOT NULL DEFAULT '0',
         `date_mod` timestamp NULL DEFAULT NULL,
         `date_creation` timestamp NULL DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `entities_id` (`entities_id`),
         KEY `is_recursive` (`is_recursive`),
         KEY `locations_id` (`locations_id`),
         KEY `passivedcequipmentmodels_id` (`passivedcequipmentmodels_id`),
         KEY `passivedcequipmenttypes_id` (`passivedcequipmenttypes_id`),
         KEY `users_id_tech` (`users_id_tech`),
         KEY `group_id_tech` (`groups_id_tech`),
         KEY `is_template` (`is_template`),
         KEY `is_deleted` (`is_deleted`),
         KEY `states_id` (`states_id`),
         KEY `manufacturers_id` (`manufacturers_id`)
       ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "add table glpi_passivedcequipments");
   }
   if (!$DB->tableExists('glpi_passivedcequipmentmodels')) {
      $query = "CREATE TABLE `glpi_passivedcequipmentmodels` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
         `comment` text COLLATE utf8_unicode_ci,
         `product_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
         `weight` int(11) NOT NULL DEFAULT '0',
         `required_units` int(11) NOT NULL DEFAULT '1',
         `depth` float NOT NULL DEFAULT 1,
         `power_connections` int(11) NOT NULL DEFAULT '0',
         `power_consumption` int(11) NOT NULL DEFAULT '0',
         `is_half_rack` tinyint(1) NOT NULL DEFAULT '0',
         `picture_front` text COLLATE utf8_unicode_ci,
         `picture_rear` text COLLATE utf8_unicode_ci,
         `date_mod` timestamp NULL DEFAULT NULL,
         `date_creation` timestamp NULL DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `name` (`name`),
         KEY `date_mod` (`date_mod`),
         KEY `date_creation` (`date_creation`),
         KEY `product_number` (`product_number`)
       ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "add table glpi_passivedcequipmentmodels");
   }
   if (!$DB->tableExists('glpi_passivedcequipmenttypes')) {
      $query = "CREATE TABLE `glpi_passivedcequipmenttypes` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
         `comment` text COLLATE utf8_unicode_ci,
         `date_mod` timestamp NULL DEFAULT NULL,
         `date_creation` timestamp NULL DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `name` (`name`),
         KEY `date_mod` (`date_mod`),
         KEY `date_creation` (`date_creation`)
       ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "add table glpi_passivedcequipmenttypes");
   }
   if (!$DB->fieldExists('glpi_states', 'is_visible_passivedcequipment')) {
      $migration->addField('glpi_states', 'is_visible_passivedcequipment', 'bool', [
         'value' => 1,
         'after' => 'is_visible_rack'
      ]);
      $migration->addKey('glpi_states', 'is_visible_passivedcequipment');
   }
   /** /Passive Datacenter equipments */

   if (!$DB->fieldExists('glpi_profiles', 'managed_domainrecordtypes')) {
      $migration->addField(
         'glpi_profiles',
         'managed_domainrecordtypes',
         'text',
         [
            'after'     => 'change_status'
         ]
      );
   }

   $migration->addPostQuery(
      $DB->buildUpdate(
         'glpi_profiles', [
            'managed_domainrecordtypes' => exportArrayToDB([])
         ], [
            'managed_domainrecordtypes' => ['', null]
         ]
      )
   );

   // Add anonymize_support_agents to entity
   if (!$DB->fieldExists("glpi_entities", "anonymize_support_agents")) {
      $migration->addField(
         "glpi_entities",
         "anonymize_support_agents",
         "integer",
         [
            'after'     => "suppliers_as_private",
            'value'     => -2,               // Inherit as default value
            'update'    => '0',              // Not enabled for root entity
            'condition' => 'WHERE `id` = 0'
         ]
      );
   }

   /**  Add default impact itemtypes */
   $impact_default = exportArrayToDB(Impact::getDefaultItemtypes());
   $migration->addConfig([Impact::CONF_ENABLED => $impact_default]);
   /**  /Add default impact itemtypes */

   /** Appliances & webapps */
   require __DIR__ . '/update_94_95/appliances.php';
   /** /Appliances & webapps */

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

   // Add new field states in contract
   if (!$DB->fieldExists('glpi_states', 'is_visible_contract')) {
      $migration->addField('glpi_states', 'is_visible_contract', 'bool', [
         'value' => 1,
         'after' => 'is_visible_cluster'
      ]);
      $migration->addKey('glpi_states', 'is_visible_contract');
   }

   if (!$DB->fieldExists('glpi_contracts', 'states_id')) {
      $migration->addField('glpi_contracts', 'states_id', 'int', [
         'value' => 0,
         'after' => 'is_template'
      ]);
      $migration->addKey('glpi_contracts', 'states_id');
   }

   // No-reply notifications
   if (!$DB->fieldExists(Notification::getTable(), 'allow_response')) {
      $migration->addField(Notification::getTable(), 'allow_response', 'bool', [
         'value' => 1
      ]);
   }

   $migration->addConfig([
      'admin_email_noreply'      => "",
      'admin_email_noreply_name' => "",
   ]);
   // /No-reply notifications

   $migration->executeMigration();

   return $updateresult;
}
