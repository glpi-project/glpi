<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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

/** @file
* @brief
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

   // ************ Keep it at the end **************
   $migration->executeMigration();

   return $updateresult;
}
