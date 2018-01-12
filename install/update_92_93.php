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
 * Update from 9.2 to 9.3
 *
 * @return bool for success (will die for most error)
**/
function update92to93() {
   global $DB, $migration, $CFG_GLPI;
   $dbutils = new DbUtils();

   $current_config   = Config::getConfigurationValues('core');
   $updateresult     = true;
   $ADDTODISPLAYPREF = [];

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), '9.3'));
   $migration->setVersion('9.3');

   //Create solutions table
   if (!$DB->tableExists('glpi_itilsolutions')) {
      $query = "CREATE TABLE `glpi_itilsolutions` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
         `items_id` int(11) NOT NULL DEFAULT '0',
         `solutiontypes_id` int(11) NOT NULL DEFAULT '0',
         `solutiontype_name` varchar(255) NULL DEFAULT NULL,
         `content` longtext COLLATE utf8_unicode_ci,
         `date_creation` datetime DEFAULT NULL,
         `date_mod` datetime DEFAULT NULL,
         `date_approval` datetime DEFAULT NULL,
         `users_id` int(11) NOT NULL DEFAULT '0',
         `user_name` varchar(255) NULL DEFAULT NULL,
         `users_id_editor` int(11) NOT NULL DEFAULT '0',
         `users_id_approval` int(11) NOT NULL DEFAULT '0',
         `user_name_approval` varchar(255) NULL DEFAULT NULL,
         `status` int(11) NOT NULL DEFAULT '1',
         `ticketfollowups_id` int(11) DEFAULT NULL  COMMENT 'Followup reference on reject or approve a ticket solution',
         PRIMARY KEY (`id`),
         KEY `itemtype` (`itemtype`),
         KEY `item_id` (`items_id`),
         KEY `item` (`itemtype`,`items_id`),
         KEY `solutiontypes_id` (`solutiontypes_id`),
         KEY `users_id` (`users_id`),
         KEY `users_id_editor` (`users_id_editor`),
         KEY `users_id_approval` (`users_id_approval`),
         KEY `status` (`status`),
         KEY `ticketfollowups_id` (`ticketfollowups_id`)
         ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "9.3 add table glpi_itilsolutions");
   }

   //add unicity key required for migration only
   $migration->addKey(
      'glpi_itilsolutions',
      ['itemtype', 'items_id', 'date_creation'],
      'migration_unicity',
      'UNIQUE'
   );
   $migration->migrationOneTable('glpi_itilsolutions');

   if ($DB->fieldExists('glpi_tickets', 'solution')) {
      //migrate solution history for tickets
      $query = "REPLACE INTO `glpi_itilsolutions` (itemtype, items_id, date_creation, users_id, user_name, solutiontypes_id, solutiontype_name, content, status, date_approval, ticketfollowups_id, users_id_approval, user_name_approval)
                  SELECT
                  'Ticket' AS itemtype,
                  obj.`id` AS items_id,
                  IFNULL(
                     glsolve.`date_mod`,
                     obj.`solvedate`
                  ) AS date_creation,
                  IF(glsolve.user_name REGEXP '[(][0-9]+[)]$', SUBSTRING_INDEX(SUBSTRING_INDEX(glsolve.`user_name`, '(', -1), ')', 1), 0) AS users_id,
                  IF(glsolve.user_name REGEXP '[(][0-9]+[)]$', NULL, glsolve.`user_name`) AS user_name,
                  IF(glsolvetype.`new_value` REGEXP '[(][0-9]+[)]$', SUBSTRING_INDEX(SUBSTRING_INDEX(glsolvetype.`new_value`, '(', -1), ')', 1), 0) AS solutiontypes_id,
                  IF(glsolvetype.`new_value` REGEXP '[(][0-9]+[)]$', NULL, glsolvetype.`new_value`) AS solutiontype_name,
                  IFNULL(
                     glcontent.`new_value`,
                     obj.`solution`
                  ) AS content,
                  IF(
                     IFNULL(glansw.`date_mod`, obj.`closedate`) IS NULL,
                     1,
                     IF(
                           glansw.`new_value` = 6 OR(
                              glansw.`new_value` IS NULL AND obj.`closedate` IS NOT NULL
                           ),
                           3,
                        2
                  )
               ) AS status,
               IFNULL(glansw.`date_mod`, obj.`closedate`) AS date_approval,
               fup.`id` AS 'ticketfollowups_id',
               IF(glansw.`user_name` REGEXP '[(][0-9]+[)]$', SUBSTRING_INDEX(SUBSTRING_INDEX(glansw.`user_name`, '(', -1), ')', 1), 0) AS users_id_approval,
               IF(glansw.`user_name` REGEXP '[(][0-9]+[)]$', NULL, glansw.`user_name`) AS user_name_approval
            FROM glpi_tickets AS obj
            LEFT JOIN `glpi_logs` AS glsolve
               ON glsolve.`itemtype` = 'Ticket' AND glsolve.`items_id` = obj.`id` AND glsolve.`id_search_option` = 12 AND glsolve.`new_value` = 5
            LEFT JOIN `glpi_logs` AS glsolvetype
               ON glsolvetype.`itemtype` = 'Ticket' AND glsolvetype.`items_id` = obj.`id` AND glsolvetype.`id_search_option` = 23 AND glsolvetype.`date_mod` = glsolve.`date_mod`
            LEFT JOIN `glpi_logs` AS glcontent
               ON glcontent.`id` =(
                  SELECT MAX(gl.`id`) FROM `glpi_logs` AS gl
                     WHERE gl.`itemtype` = 'Ticket' AND gl.`items_id` = obj.`id` AND gl.`id_search_option` = 24 AND gl.`id` < glsolve.`id`
                     GROUP BY gl.`items_id`
               )
            LEFT JOIN `glpi_logs` AS glansw
               ON glansw.`id` =(
                   SELECT MIN(gl.`id`) FROM `glpi_logs` AS gl
                  WHERE gl.`itemtype` = 'Ticket' AND gl.`items_id` = obj.`id` AND gl.`id_search_option` = 12 AND gl.`old_value` = 5 AND gl.`id` > glsolve.`id`
                  GROUP BY gl.`items_id`
               )
            LEFT JOIN `glpi_logs` AS glfup
               ON glfup.`itemtype` = 'Ticket' AND glfup.`items_id` = obj.`id` AND glfup.`itemtype_link` = 'TicketFollowup' AND glfup.`date_mod` = glansw.`date_mod`
            LEFT JOIN `glpi_ticketfollowups` AS fup
               ON fup.`id` = glfup.`new_value`
            WHERE
               obj.`solution` IS NOT NULL";
      $DB->queryOrDie($query, "9.3 migrate Ticket solution history");
      $migration->dropField('glpi_tickets', 'solution');
      $migration->dropKey('glpi_tickets', 'solutiontypes_id');
      $migration->dropField('glpi_tickets', 'solutiontypes_id');
   }

   if ($DB->fieldExists('glpi_problems', 'solution')) {
      // Problem soution history
      $query = "REPLACE INTO `glpi_itilsolutions` (itemtype, items_id, date_creation, users_id, user_name, solutiontypes_id, solutiontype_name, content, status, date_approval, ticketfollowups_id, users_id_approval, user_name_approval)
                  SELECT DISTINCT 'Problem' AS itemtype,
                      obj.`id` AS items_id,
                        IFNULL(glsolve.`date_mod`, obj.`solvedate`) AS date_creation,
                        IF(glsolve.user_name REGEXP '[(][0-9]+[)]$', SUBSTRING_INDEX(SUBSTRING_INDEX(glsolve.`user_name`, '(', -1), ')', 1), 0) AS users_id,
                        IF(glsolve.user_name REGEXP '[(][0-9]+[)]$', NULL, glsolve.`user_name`) AS user_name,
                        IF(glsolvetype.`new_value` REGEXP '[(][0-9]+[)]$', SUBSTRING_INDEX(SUBSTRING_INDEX(glsolvetype.`new_value`, '(', -1), ')', 1), 0) AS solutiontypes_id,
                        IF(glsolvetype.`new_value` REGEXP '[(][0-9]+[)]$', NULL, glsolvetype.`new_value`) AS solutiontype_name,
                        IFNULL(glcontent.`new_value`, obj.`solution`) AS content,
                        IF( IFNULL(glansw.`date_mod`, obj.`closedate`) IS NULL, 1, IF( glansw.`new_value` = 6 OR (glansw.`new_value` IS NULL AND obj.`closedate` IS NOT NULL), 3, 2)) AS status,
                        IFNULL(glansw.`date_mod`, obj.`closedate`) AS date_approval,
                        NULL AS 'ticketfollowups_id',
                        IF(glansw.`user_name` REGEXP '[(][0-9]+[)]$', SUBSTRING_INDEX(SUBSTRING_INDEX(glansw.`user_name`, '(', -1), ')', 1), 0) AS users_id_approval,
                        IF(glansw.`user_name` REGEXP '[(][0-9]+[)]$', NULL, glansw.`user_name`) AS user_name_approval
                     FROM glpi_problems AS obj
                     LEFT JOIN `glpi_logs` AS glsolve ON glsolve.`itemtype` = 'Problem' AND glsolve.`items_id` = obj.`id` AND glsolve.`id_search_option` = 12 AND glsolve.`new_value` = 5
                     LEFT JOIN `glpi_logs` AS glsolvetype ON glsolvetype.id = (select max(gl.id) from glpi_logs as gl where gl.itemtype='Problem' and gl.items_id=obj.id and gl.id_search_option=23 and gl.id < glsolve.id group by gl.items_id)
                     LEFT JOIN `glpi_logs` AS glcontent ON  glcontent.`id` = (SELECT MAX(gl.`id`) FROM `glpi_logs` AS gl WHERE gl.`itemtype`='Problem' AND gl.`items_id` = obj.`id` AND gl.`id_search_option` = 24 AND gl.`id` < glsolve.`id` GROUP BY gl.`items_id`)
                     LEFT JOIN `glpi_logs` AS glansw ON glansw.`id` = (SELECT MIN(gl.`id`) FROM `glpi_logs` AS gl WHERE gl.`itemtype`='Problem' AND gl.`items_id` = obj.`id` AND gl.`id_search_option` = 12 AND gl.`old_value` = 5 AND gl.`id` > glsolve.`id` GROUP BY gl.`items_id`)
                     WHERE obj.`solution` IS NOT NULL AND IFNULL(glsolve.`date_mod`, obj.`solvedate`) IS NOT NULL";
      $DB->queryOrDie($query, "9.3 migrate Problem solution history");
      $migration->dropField('glpi_problems', 'solution');
      $migration->dropKey('glpi_problems', 'solutiontypes_id');
      $migration->dropField('glpi_problems', 'solutiontypes_id');
   }

   if ($DB->fieldExists('glpi_changes', 'solution')) {
      // Change solution history
      $query = "REPLACE INTO `glpi_itilsolutions` (itemtype, items_id, date_creation, users_id, user_name, solutiontypes_id, solutiontype_name, content, status, date_approval, ticketfollowups_id, users_id_approval, user_name_approval)
                  SELECT DISTINCT 'Change' AS itemtype,
                     obj.`id` AS items_id,
                     IFNULL(glsolve.`date_mod`, obj.`solvedate`) AS date_creation,
                     IF(glsolve.user_name REGEXP '[(][0-9]+[)]$', SUBSTRING_INDEX(SUBSTRING_INDEX(glsolve.`user_name`, '(', -1), ')', 1), 0) AS users_id,
                     IF(glsolve.user_name REGEXP '[(][0-9]+[)]$', NULL, glsolve.`user_name`) AS user_name,
                     IF(glsolvetype.`new_value` REGEXP '[(][0-9]+[)]$', SUBSTRING_INDEX(SUBSTRING_INDEX(glsolvetype.`new_value`, '(', -1), ')', 1), 0) AS solutiontypes_id,
                     IF(glsolvetype.`new_value` REGEXP '[(][0-9]+[)]$', NULL, glsolvetype.`new_value`) AS solutiontype_name,
                     IFNULL(glcontent.`new_value`, obj.`solution`) AS content,
                     IF( IFNULL(glansw.`date_mod`, obj.`closedate`) IS NULL, 1, IF( glansw.`new_value` = 6 OR (glansw.`new_value` IS NULL AND obj.`closedate` IS NOT NULL), 3, 2)) AS status,
                     IFNULL(glansw.`date_mod`, obj.`closedate`) AS date_approval,
                     NULL AS 'ticketfollowups_id',
                     IF(glansw.`user_name` REGEXP '[(][0-9]+[)]$', SUBSTRING_INDEX(SUBSTRING_INDEX(glansw.`user_name`, '(', -1), ')', 1), 0) AS users_id_approval,
                     IF(glansw.`user_name` REGEXP '[(][0-9]+[)]$', NULL, glansw.`user_name`) AS user_name_approval
                  FROM glpi_changes AS obj
                  LEFT JOIN `glpi_logs` AS glsolve ON glsolve.`itemtype` = 'Change' AND glsolve.`items_id` = obj.`id` AND glsolve.`id_search_option` = 12 AND glsolve.`new_value` = 5
                  LEFT JOIN `glpi_logs` AS glsolvetype ON glsolvetype.id = (select max(gl.id) from glpi_logs as gl where gl.itemtype='Change' and gl.items_id=obj.id and gl.id_search_option=23 and gl.id < glsolve.id group by gl.items_id)
                  LEFT JOIN `glpi_logs` AS glcontent ON  glcontent.`id` = (SELECT MAX(gl.`id`) FROM `glpi_logs` AS gl WHERE gl.`itemtype`='Change' AND gl.`items_id` = obj.`id` AND gl.`id_search_option` = 24 AND gl.`id` < glsolve.`id` GROUP BY gl.`items_id`)
                  LEFT JOIN `glpi_logs` AS glansw ON glansw.`id` = (SELECT MIN(gl.`id`) FROM `glpi_logs` AS gl WHERE gl.`itemtype`='Change' AND gl.`items_id` = obj.`id` AND gl.`id_search_option` = 12 AND gl.`old_value` = 5 AND gl.`id` > glsolve.`id` GROUP BY gl.`items_id`)
                  WHERE obj.`solution` IS NOT NULL AND IFNULL(glsolve.`date_mod`, obj.`solvedate`) IS NOT NULL";
      $DB->queryOrDie($query, "9.3 migrate Change solution history");
      $migration->dropField('glpi_changes', 'solution');
      $migration->dropKey('glpi_changes', 'solutiontypes_id');
      $migration->dropField('glpi_changes', 'solutiontypes_id');
   }

   //drop migration unicity key
   $migration->dropKey('glpi_itilsolutions', 'migration_unicity');
   $migration->migrationOneTable('glpi_itilsolutions');

   /** Datacenters */
   if (!$DB->tableExists('glpi_datacenters')) {
      $query = "CREATE TABLE `glpi_datacenters` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
                  `locations_id` int(11) NOT NULL DEFAULT '0',
                  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `locations_id` (`locations_id`),
                  KEY `is_deleted` (`is_deleted`)
                  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "9.3 add table glpi_datacenters");
   }

   if (!$DB->tableExists('glpi_dcrooms')) {
      $query = "CREATE TABLE `glpi_dcrooms` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
                  `locations_id` int(11) NOT NULL DEFAULT '0',
                  `vis_cols` int(11) DEFAULT NULL,
                  `vis_rows` int(11) DEFAULT NULL,
                  `datacenters_id` int(11) NOT NULL DEFAULT '0',
                  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `locations_id` (`locations_id`),
                  KEY `datacenters_id` (`datacenters_id`),
                  KEY `is_deleted` (`is_deleted`)
                  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "9.3 add table glpi_dcrooms");
   }

   if (!$DB->tableExists('glpi_rackmodels')) {
      $query = "CREATE TABLE `glpi_rackmodels` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `comment` text COLLATE utf8_unicode_ci,
                  `product_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `name` (`name`),
                  KEY `product_number` (`product_number`)
                  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "9.3 add table glpi_rackmodels");
   }

   if (!$DB->tableExists('glpi_racktypes')) {
      $query = "CREATE TABLE `glpi_racktypes` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `comment` text COLLATE utf8_unicode_ci,
                  `date_creation` datetime DEFAULT NULL,
                  `date_mod` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `name` (`name`),
                  KEY `date_creation` (`date_creation`),
                  KEY `date_mod` (`date_mod`)
                  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "9.3 add table glpi_racktypes");
   }

   if (!$DB->tableExists('glpi_racks')) {
      $query = "CREATE TABLE `glpi_racks` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `comment` text COLLATE utf8_unicode_ci,
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
                  `locations_id` int(11) NOT NULL DEFAULT '0',
                  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `rackmodels_id` int(11) DEFAULT NULL,
                  `manufacturers_id` int(11) NOT NULL DEFAULT '0',
                  `racktypes_id` int(11) NOT NULL DEFAULT '0',
                  `states_id` int(11) NOT NULL DEFAULT '0',
                  `users_id_tech` int(11) NOT NULL DEFAULT '0',
                  `groups_id_tech` int(11) NOT NULL DEFAULT '0',
                  `width` int(11) DEFAULT NULL,
                  `height` int(11) DEFAULT NULL,
                  `depth` int(11) DEFAULT NULL,
                  `number_units` int(11) DEFAULT '0',
                  `is_template` tinyint(1) NOT NULL DEFAULT '0',
                  `template_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
                  `dcrooms_id` int(11) NOT NULL DEFAULT '0',
                  `room_orientation` int(11) NOT NULL DEFAULT '0',
                  `position` varchar(50),
                  `bgcolor` varchar(7) DEFAULT NULL,
                  `max_power` int(11) NOT NULL DEFAULT '0',
                  `mesured_power` int(11) NOT NULL DEFAULT '0',
                  `max_weight` int(11) NOT NULL DEFAULT '0',
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `locations_id` (`locations_id`),
                  KEY `rackmodels_id` (`rackmodels_id`),
                  KEY `manufacturers_id` (`manufacturers_id`),
                  KEY `racktypes_id` (`racktypes_id`),
                  KEY `states_id` (`states_id`),
                  KEY `users_id_tech` (`users_id_tech`),
                  KEY `group_id_tech` (`groups_id_tech`),
                  KEY `is_template` (`is_template`),
                  KEY `is_deleted` (`is_deleted`),
                  KEY `dcrooms_id` (`dcrooms_id`)
                  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "9.3 add table glpi_racks");
   }

   if (!$DB->tableExists('glpi_items_racks')) {
      $query = "CREATE TABLE `glpi_items_racks` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `racks_id` int(11) NOT NULL,
                  `itemtype` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                  `items_id` int(11) NOT NULL,
                  `position` int(11) NOT NULL,
                  `orientation` tinyint(1),
                  `bgcolor` varchar(7) DEFAULT NULL,
                  `hpos` tinyint(1) NOT NULL DEFAULT '0',
                  `is_reserved` tinyint(1) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `item` (`itemtype`,`items_id`, `is_reserved`),
                  KEY `relation` (`racks_id`,`itemtype`,`items_id`)
                  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "9.3 add table glpi_items_racks");
   }

   if (countElementsInTable("glpi_profilerights", "`name` = 'datacenter'") == 0) {
      //new right for datacenters
      //give full rights to profiles having config right
      foreach ($DB->request("glpi_profilerights", "`name` = 'config'") as $profrights) {
         if ($profrights['rights'] && (READ + UPDATE)) {
            $rightValue = CREATE | READ | UPDATE | DELETE  | PURGE | READNOTE | UPDATENOTE | UNLOCK;
         } else {
            $rightValue = 0;
         }
         $query = "INSERT INTO `glpi_profilerights`
                          (`id`, `profiles_id`, `name`, `rights`)
                   VALUES (NULL, '".$profrights['profiles_id']."', 'datacenter',
                           '".$rightValue."')";
         $DB->queryOrDie($query, "9.1 add right for datacenter");
      }
   }

   //devices models enhancement for datacenters
   $models = [
      'computer',
      'monitor',
      'networkequipment',
      'peripheral'
   ];

   $models_fields = [
      [
         'name'   => 'weight',
         'type'   => "int(11) NOT NULL DEFAULT '0'"
      ], [
         'name'   => 'required_units',
         'type'   => "int(11) NOT NULL DEFAULT '1'"
      ], [
         'name'   => 'depth',
         'type'   => "float NOT NULL DEFAULT 1"
      ], [
         'name'   => 'power_connections',
         'type'   => "int(11) NOT NULL DEFAULT '0'"
      ], [
         'name'   => 'power_consumption',
         'type'   => "int(11) NOT NULL DEFAULT '0'"
      ], [
         'name'   => 'is_half_rack',
         'type'   => "tinyint(1) NOT NULL DEFAULT '0'"
      ], [
         'name'   => 'picture_front',
         'type'   => "text"
      ], [
         'name'   => 'picture_rear',
         'type'   => "text"
      ]
   ];

   foreach ($models as $model) {
      $table = "glpi_{$model}models";
      $after = 'product_number';
      foreach ($models_fields as $field) {
         if (!$DB->fieldExists($table, $field['name'])) {
            $migration->addField(
               $table,
               $field['name'],
               $field['type'],
               ['after' => $after]
            );
         }
         $after = $field['name'];
      }
   }

   if (!$DB->tableExists('glpi_enclosuremodels')) {
      $query = "CREATE TABLE `glpi_enclosuremodels` (
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
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `name` (`name`),
                  KEY `date_mod` (`date_mod`),
                  KEY `date_creation` (`date_creation`),
                  KEY `product_number` (`product_number`)
                  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "9.3 add table glpi_enclosuremodels");
   }

   if (!$DB->tableExists('glpi_enclosures')) {
      $query = "CREATE TABLE `glpi_enclosures` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
                  `locations_id` int(11) NOT NULL DEFAULT '0',
                  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `enclosuremodels_id` int(11) DEFAULT NULL,
                  `users_id_tech` int(11) NOT NULL DEFAULT '0',
                  `groups_id_tech` int(11) NOT NULL DEFAULT '0',
                  `is_template` tinyint(1) NOT NULL DEFAULT '0',
                  `template_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
                  `orientation` tinyint(1),
                  `power_supplies` tinyint(1) NOT NULL DEFAULT '0',
                  `states_id` int(11) NOT NULL DEFAULT '0' COMMENT 'RELATION to states (id)',
                  `comment` text COLLATE utf8_unicode_ci,
                  `manufacturers_id` int(11) NOT NULL DEFAULT '0',
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `locations_id` (`locations_id`),
                  KEY `enclosuremodels_id` (`enclosuremodels_id`),
                  KEY `users_id_tech` (`users_id_tech`),
                  KEY `group_id_tech` (`groups_id_tech`),
                  KEY `is_template` (`is_template`),
                  KEY `is_deleted` (`is_deleted`),
                  KEY `states_id` (`states_id`),
                  KEY `manufacturers_id` (`manufacturers_id`)
                  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "9.3 add table glpi_enclosures");
   }

   if (!$DB->tableExists('glpi_items_enclosures')) {
      $query = "CREATE TABLE `glpi_items_enclosures` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `enclosures_id` int(11) NOT NULL,
                  `itemtype` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                  `items_id` int(11) NOT NULL,
                  `position` int(11) NOT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `item` (`itemtype`,`items_id`),
                  KEY `relation` (`enclosures_id`,`itemtype`,`items_id`)
                  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "9.3 add table glpi_items_enclosures");
   }

   if (!$DB->tableExists('glpi_pdumodels')) {
      $query = "CREATE TABLE `glpi_pdumodels` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `comment` text COLLATE utf8_unicode_ci,
                  `product_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `weight` int(11) NOT NULL DEFAULT '0',
                  `required_units` int(11) NOT NULL DEFAULT '1',
                  `depth` float NOT NULL DEFAULT 1,
                  `power_connections` int(11) NOT NULL DEFAULT '0',
                  `max_power` int(11) NOT NULL DEFAULT '0',
                  `is_half_rack` tinyint(1) NOT NULL DEFAULT '0',
                  `picture_front` text COLLATE utf8_unicode_ci,
                  `picture_rear` text COLLATE utf8_unicode_ci,
                  `is_rackable` tinyint(1) NOT NULL DEFAULT '0',
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `name` (`name`),
                  KEY `is_rackable` (`is_rackable`),
                  KEY `product_number` (`product_number`)
                  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "9.3 ad table glpi_pdumodels");
   }
   if ($DB->fieldExists('glpi_pdumodels', 'power_consumption')) {
      $migration->changeField('glpi_pdumodels',
                              'power_consumption',
                              'max_power',
                              'integer',
                              ['default' => 0]);
   }

   if (!$DB->tableExists('glpi_pdutypes')) {
      $query = "CREATE TABLE `glpi_pdutypes` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `comment` text COLLATE utf8_unicode_ci,
                  `date_creation` datetime DEFAULT NULL,
                  `date_mod` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `name` (`name`),
                  KEY `date_creation` (`date_creation`),
                  KEY `date_mod` (`date_mod`)
                  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "9.3 add table glpi_pdutypes");
   }

   if (!$DB->tableExists('glpi_pdus')) {
      $query = "CREATE TABLE `glpi_pdus` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
                  `locations_id` int(11) NOT NULL DEFAULT '0',
                  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `pdumodels_id` int(11) DEFAULT NULL,
                  `users_id_tech` int(11) NOT NULL DEFAULT '0',
                  `groups_id_tech` int(11) NOT NULL DEFAULT '0',
                  `is_template` tinyint(1) NOT NULL DEFAULT '0',
                  `template_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
                  `states_id` int(11) NOT NULL DEFAULT '0' COMMENT 'RELATION to states (id)',
                  `comment` text COLLATE utf8_unicode_ci,
                  `manufacturers_id` int(11) NOT NULL DEFAULT '0',
                  `pdutypes_id` int(11) NOT NULL DEFAULT '0',
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `locations_id` (`locations_id`),
                  KEY `pdumodels_id` (`pdumodels_id`),
                  KEY `users_id_tech` (`users_id_tech`),
                  KEY `group_id_tech` (`groups_id_tech`),
                  KEY `is_template` (`is_template`),
                  KEY `is_deleted` (`is_deleted`),
                  KEY `states_id` (`states_id`),
                  KEY `manufacturers_id` (`manufacturers_id`),
                  KEY `pdutypes_id` (`pdutypes_id`)
                  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "9.3 add table glpi_pdus");
   }

   if (!$DB->tableExists('glpi_plugs')) {
      $query = "CREATE TABLE `glpi_plugs` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `comment` text COLLATE utf8_unicode_ci,
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `name` (`name`),
                  KEY `date_mod` (`date_mod`),
                  KEY `date_creation` (`date_creation`)
                  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, '9.3 add table glpi_plugs');
   }

   if (!$DB->tableExists('glpi_pdus_plugs')) {
      $query = "CREATE TABLE `glpi_pdus_plugs` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `plugs_id` int(11) NOT NULL DEFAULT '0',
                  `pdus_id` int(11) NOT NULL DEFAULT '0',
                  `number_plugs` int(11) DEFAULT '0',
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `plugs_id` (`plugs_id`),
                  KEY `pdus_id` (`pdus_id`)
                  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, '9.3 add table glpi_pdus_plugs');
   }

   if (!countElementsInTable('glpi_plugs')) {
      $plugs = ['C13', 'C15', 'C19'];
      foreach ($plugs as $plug) {
         $migration->addPostQuery(
            $DB->buildInsert('glpi_plugs', ['name' => $plug])
         );
      }
   }

   if (!$DB->tableExists('glpi_pdus_racks')) {
      $query = "CREATE TABLE `glpi_pdus_racks` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `racks_id` int(11) NOT NULL DEFAULT '0',
                  `pdus_id` int(11) NOT NULL DEFAULT '0',
                  `side` int(11) DEFAULT '0',
                  `position` int(11) NOT NULL,
                  `bgcolor` varchar(7) DEFAULT NULL,
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `racks_id` (`racks_id`),
                  KEY `pdus_id` (`pdus_id`)
                  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, '9.3 add table glpi_pdus_racks');
   }

   /** /Datacenters */

   /** Add address to locations */
   if (!$DB->fieldExists('glpi_locations', 'address')) {
      $migration->addField(
         'glpi_locations',
         'address',
         'text',
         ['after' => 'sons_cache']
      );
   }

   if (!$DB->fieldExists('glpi_locations', 'postcode')) {
      $migration->addField(
         'glpi_locations',
         'postcode',
         'string',
         ['after' => 'address']
      );
   }

   if (!$DB->fieldExists('glpi_locations', 'town')) {
      $migration->addField(
         'glpi_locations',
         'town',
         'string',
         ['after' => 'postcode']
      );
   }

   if (!$DB->fieldExists('glpi_locations', 'state')) {
      $migration->addField(
         'glpi_locations',
         'state',
         'string',
         ['after' => 'town']
      );
   }

   if (!$DB->fieldExists('glpi_locations', 'country')) {
      $migration->addField(
         'glpi_locations',
         'country',
         'string',
         ['after' => 'state']
      );
   }
   /** /Add address to locations */

   /** Migrate computerdisks to items_disks */
   if (!$DB->tableExists('glpi_items_disks') && $DB->tableExists('glpi_computerdisks')) {
      $migration->renameTable('glpi_computerdisks', 'glpi_items_disks');
   }
   if ($DB->fieldExists('glpi_items_disks', 'computers_id')) {
      $migration->dropField('glpi_items_disks', 'items_id');
      $migration->dropKey('glpi_items_disks', 'computers_id');
      $migration->changeField(
         'glpi_items_disks',
         'computers_id',
         'items_id',
         'integer'
      );
      $migration->addKey('glpi_items_disks', 'items_id');
   }
   if (!$DB->fieldExists('glpi_items_disks', 'itemtype')) {
      $migration->addField('glpi_items_disks', 'itemtype', 'string', ['after' => 'entities_id']);
   }
   $migration->addKey('glpi_items_disks', 'itemtype');
   $migration->addKey('glpi_items_disks', ['itemtype', 'items_id'], 'item');
   $migration->addPostQuery(
      $DB->buildUpdate(
         'glpi_items_disks',
         ['itemtype' => 'Computer'],
         ['itemtype' => null]
      )
   );
   /** /Migrate computerdisks to items_disks */

   // ************ Keep it at the end **************
   $migration->executeMigration();

   return $updateresult;
}
