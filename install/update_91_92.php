<?php
/*
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

/**
 * Update from 9.1 to 9.2
 *
 * @return bool for success (will die for most error)
**/
function update91to92() {
   global $DB, $migration, $CFG_GLPI;

   $current_config   = Config::getConfigurationValues('core');
   $updateresult     = true;
   $ADDTODISPLAYPREF = array();

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), '9.2'));
   $migration->setVersion('9.2');

   $backup_tables = false;
   // table already exist but deleted during the migration
   // not table created during the migration
   $newtables     = array(
      'glpi_businesscriticities',
      'glpi_knowbaseitems_items'
   );

   foreach ($newtables as $new_table) {
      // rename new tables if exists ?
      if (TableExists($new_table)) {
         $migration->dropTable("backup_$new_table");
         $migration->displayWarning("$new_table table already exists. ".
                                    "A backup have been done to backup_$new_table.");
         $backup_tables = true;
         $query         = $migration->renameTable("$new_table", "backup_$new_table");
      }
   }
   if ($backup_tables) {
      $migration->displayWarning("You can delete backup tables if you have no need of them.",
                                 true);
   }

   //put you migration script here

   // add business criticity
   $migration->addField("glpi_infocoms", "businesscriticities_id", "integer");
   $migration->addKey("glpi_infocoms", "businesscriticities_id");
   if (!TableExists("glpi_businesscriticities")) {
      $query = "CREATE TABLE `glpi_businesscriticities` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
        `entities_id` int(11) NOT NULL DEFAULT '0',
        `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
        `comment` text COLLATE utf8_unicode_ci,
        `date_mod` datetime DEFAULT NULL,
        `date_creation` datetime DEFAULT NULL,
        `businesscriticities_id` int(11) NOT NULL DEFAULT '0',
        `completename` text COLLATE utf8_unicode_ci,
        `level` int(11) NOT NULL DEFAULT '0',
        `ancestors_cache` longtext COLLATE utf8_unicode_ci,
        `sons_cache` longtext COLLATE utf8_unicode_ci,
        PRIMARY KEY (`id`),
        KEY `name` (`name`),
        KEY `unicity` (`businesscriticities_id`,`name`),
        KEY `date_mod` (`date_mod`),
        KEY `date_creation` (`date_creation`)
      ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "Add business criticity table");
   }

   // Issue #1250 - Add decimal to monitor size
   $migration->changeField('glpi_monitors', 'size', 'size', 'DECIMAL(5,2)');

   //Make software license type a tree dropdown
   $migration->addField("glpi_softwarelicensetypes", "softwarelicensetypes_id", "integer");
   $migration->addKey("glpi_softwarelicensetypes", "softwarelicensetypes_id");
   $migration->addField("glpi_softwarelicensetypes", "level", "integer");
   $migration->addField("glpi_softwarelicensetypes", "ancestors_cache", "longtext");
   $migration->addField("glpi_softwarelicensetypes", "sons_cache", "longtext");
   $migration->addField("glpi_softwarelicensetypes", "entities_id", "integer");
   $migration->addField("glpi_softwarelicensetypes", "is_recursive", "bool");
   $tree = $migration->addField("glpi_softwarelicensetypes", "completename", "text");
   $migration->migrationOneTable('glpi_softwarelicensetypes');

   //First time the dropdown is changed from CommonDropdown to CommonTreeDropdown
   if ($tree) {
      $query = "UPDATE `glpi_softwarelicensetypes`
                SET `completename`=`name`, `is_recursive`='1'";
      $DB->queryOrDie($query, "9.2 make glpi_softwarelicensetypes a tree dropdown");
   }

   // give READ right on components to profiles having UPDATE right
   $query = "UPDATE `glpi_profilerights`
             SET `rights` = `rights` | " . READ . "
             WHERE (`rights` & " . UPDATE .") = '" . UPDATE ."'
                   AND `name` = 'device'";
   $DB->queryOrDie($query, "grant READ right on components to profiles having UPDATE right");

   $migration->displayMessage(sprintf(__('Add of - %s to database'), 'Knowbase item link to tickets'));
   if (!TableExists('glpi_knowbaseitems_items')) {
      $query = "CREATE TABLE `glpi_knowbaseitems_items` (
                 `id` int(11) NOT NULL AUTO_INCREMENT,
                 `knowbaseitems_id` int(11) NOT NULL,
                 `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
                 `items_id` int(11) NOT NULL DEFAULT '0',
                 `date_creation` datetime DEFAULT NULL,
                 `date_mod` datetime DEFAULT NULL,
                 PRIMARY KEY (`id`),
                 UNIQUE KEY `unicity` (`itemtype`,`items_id`,`knowbaseitems_id`),
                 KEY `itemtype` (`itemtype`),
                 KEY `item_id` (`items_id`),
                 KEY `item` (`itemtype`,`items_id`)
               ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "9.2 add table glpi_knowbaseitems_items");
   }

   $migration->displayMessage(sprintf(__('Add of - %s to database'), 'Knowbase item revisions'));
   if (!TableExists('glpi_knowbaseitems_revisions')) {
      $query = "CREATE TABLE `glpi_knowbaseitems_revisions` (
                 `id` int(11) NOT NULL AUTO_INCREMENT,
                 `knowbaseitems_id` int(11) NOT NULL,
                 `revision` int(11) NOT NULL,
                 `name` text COLLATE utf8_unicode_ci,
                 `answer` longtext COLLATE utf8_unicode_ci,
                 `language` varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
                 `users_id` int(11) NOT NULL,
                 `date_creation` datetime DEFAULT NULL,
                 PRIMARY KEY (`id`),
                 UNIQUE KEY `unicity` (`knowbaseitems_id`, `revision`, `language`),
                 KEY `revision` (`revision`)
               ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "9.2 add table glpi_knowbaseitems_revisions");
   }

   $migration->addField("glpi_knowbaseitemtranslations", "users_id", "integer");
   $migration->addKey("glpi_knowbaseitemtranslations", "users_id");

   //set kb translations users...
   $query = "SELECT `glpi_knowbaseitems`.`id`, `glpi_knowbaseitems`.`users_id`
             FROM `glpi_knowbaseitems`
             INNER JOIN `glpi_knowbaseitemtranslations`
                ON `glpi_knowbaseitemtranslations`.`knowbaseitems_id` = `glpi_knowbaseitems`.`id`";

   if ($result = $DB->query($query)) {
      if ($DB->numrows($result)>0) {
         while ($data = $DB->fetch_assoc($result)) {
            $query = "UPDATE `glpi_knowbaseitemtranslations`
                          SET `users_id` = '{$data['users_id']}'
                          WHERE `knowbaseitems_id` = '{$data['id']}'";
            $DB->queryOrDie($query, 'Set knowledge base translations users');
         }
      }
   }

   $migration->addField("glpi_knowbaseitemtranslations", "date_creation", "DATE");
   $migration->addField("glpi_knowbaseitemtranslations", "date_mod", "DATE");

   $migration->displayMessage(sprintf(__('Add of - %s to database'), 'Knowbase item comments'));
   if (!TableExists('glpi_knowbaseitems_comments')) {
      $query = "CREATE TABLE `glpi_knowbaseitems_comments` (
                 `id` int(11) NOT NULL AUTO_INCREMENT,
                 `knowbaseitems_id` int(11) NOT NULL,
                 `users_id` int(11) NOT NULL DEFAULT '0',
                 `language` varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
                 `comment` text COLLATE utf8_unicode_ci NOT NULL,
                 `parent_comment_id` int(11) DEFAULT NULL,
                 `date_creation` datetime DEFAULT NULL,
                 `date_mod` datetime DEFAULT NULL,
                 PRIMARY KEY (`id`)
               ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "9.2 add table glpi_knowbaseitems_comments");
   }

   $query = "UPDATE `glpi_profilerights`
             SET `rights` = `rights` | " . KnowbaseItem::COMMENTS ."
             WHERE `name` = 'knowbase'";
   $DB->queryOrDie($query, "9.2 update knowledge base with comment right");

   // add kb category to task categories
   $migration->addField("glpi_taskcategories", "knowbaseitemcategories_id", "integer");

   // #1476 - Add users_id on glpi_documents_items
   $migration->addField("glpi_documents_items", "users_id", "integer", ['null' => TRUE]);
   $migration->addKey("glpi_documents_items", "users_id");
   $migration->addPostQuery(
      "UPDATE glpi_documents_items GDI, glpi_documents GD SET GDI.users_id = GD.users_id WHERE GDI.documents_id = GD.id",
      "9.2 update set users_id on glpi_documents_items"
   );

   // ************ Keep it at the end **************
   $migration->executeMigration();

   return $updateresult;
}
