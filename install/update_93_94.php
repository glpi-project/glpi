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
 * Update from 9.3 to 9.4
 *
 * @return bool for success (will die for most error)
**/
function update93to94() {
   global $DB, $migration, $CFG_GLPI;
   $dbutils = new DbUtils();

   $current_config   = Config::getConfigurationValues('core');
   $updateresult     = true;
   $ADDTODISPLAYPREF = [];

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), '9.4'));
   $migration->setVersion('9.4');

   //put your update rules here, and drop the line!

   //Create remindertranslations table
   if (!$DB->tableExists('glpi_remindertranslations')) {
      $query = "CREATE TABLE `glpi_remindertranslations` (
           `id` int(11) NOT NULL AUTO_INCREMENT,
           `reminders_id` int(11) NOT NULL DEFAULT '0',
           `language` varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
           `name` text COLLATE utf8_unicode_ci,
           `text` longtext COLLATE utf8_unicode_ci,
           `users_id` int(11) NOT NULL DEFAULT '0',
           `date_mod` datetime DEFAULT NULL,
           `date_creation` datetime DEFAULT NULL,
           PRIMARY KEY (`id`),
           KEY `item` (`reminders_id`,`language`),
           KEY `users_id` (`users_id`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "9.4 add table glpi_remindertranslations");
   }

   $migration->migrationOneTable('glpi_remindertranslations');

   // ************ Keep it at the end **************
   $migration->executeMigration();

   return $updateresult;
}
