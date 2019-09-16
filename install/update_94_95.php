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
      $set = ['is_active' => 1];
      $migration->addPostQuery(
         $DB->buildUpdate(
            'glpi_suppliers',
            $set,
            [true]
        ),
        $set
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
   $migration->dropKey('glpi_softwarelicenses', 'softwares_id_expire_number');
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
   $set = ['name' => 'itiltemplate'];
   $migration->addPostQuery(
      $DB->buildUpdate(
         'glpi_profilerights',
         $set,
         ['name' => 'tickettemplate']
      ),
      $set
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

         $set = [
            'id'           => 1,
            'name'         => 'Default',
            'is_recursive' => 1
         ];
         $migration->addPostQuery(
            $DB->buildInsert(
               "glpi_{$itiltype}templates",
               $set
            ),
            $set
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

         $set = [
            'id'                       => 1,
            $itiltype.'templates_id'   => 1,
            'num'                      => 21
         ];
         $migration->addPostQuery(
            $DB->buildInsert(
               "glpi_{$itiltype}templatemandatoryfields",
               $set
            ),
            $set
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
      $migration->addField('glpi_documents_items', 'date_creation', 'datetime');
      $set = [
         'date_creation' => new \QueryExpression(
            $DB->quoteName('date_mod')
         )
      ];
      $migration->addPostQuery(
         $DB->buildUpdate(
            'glpi_documents_items',
            $set,
            [true]
         ),
         $set
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
   if (!$DB->fieldExists('glpi_itilcategories', 'changeemplates_id')) {
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

   $migration->executeMigration();

   return $updateresult;
}
