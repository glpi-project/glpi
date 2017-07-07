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

/// Update from 0.5 to 0.51
function update05to051() {
   global $DB;

   echo "<p class='center'>Version 0.51 </p>";

   /*******************************GLPI 0.51***********************************************/

   if (!$DB->fieldExists("glpi_infocoms", "facture", false)) {
      $query = "ALTER TABLE `glpi_infocoms`
                ADD `facture` char(255) NOT NULL default ''";
      $DB->queryOrDie($query, "0.51 add field facture");
   }

   if (!$DB->fieldExists("glpi_enterprises", "fax", false)) {
      $query = "ALTER TABLE `glpi_enterprises`
                ADD `fax` char(255) NOT NULL default ''";
      $DB->queryOrDie($query, "0.51 add field fax");
   }

   if (!$DB->fieldExists("glpi_docs", "link", false)) {
      $query = "ALTER TABLE `glpi_docs`
                ADD `link` char(255) NOT NULL default ''";
      $DB->queryOrDie($query, "0.51 add field fax");
   }

   if (!$DB->tableExists("glpi_dropdown_contact_type")) {
      $query = "CREATE TABLE `glpi_dropdown_contact_type` (
                  `ID` int(11) NOT NULL auto_increment,
                  `name` varchar(255) NOT NULL default '',
                  PRIMARY KEY (`ID`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.51 add table dropdown_contact_type");

      $query = "INSERT INTO `glpi_dropdown_contact_type`
                       (`name`)
                VALUES ('".__('Technician')."')";
      $DB->queryOrDie($query, "0.51 add entries to dropdown_contact_type");

      $query = "INSERT INTO `glpi_dropdown_contact_type`
                       (`name`)
                VALUES ('".__('Commercial')."')";
      $DB->queryOrDie($query, "0.51 add entries to dropdown_contact_type");
   }

   if (!$DB->fieldExists("glpi_config", "cartridges_alarm", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `cartridges_alarm` int(11) NOT NULL default '10'";
      $DB->queryOrDie($query, "0.51 add field cartridges_alarm");
   }

   if (!$DB->tableExists("glpi_state_item")) {
      $query = "ALTER TABLE `glpi_repair_item`
                RENAME `glpi_state_item`";
      $DB->queryOrDie($query, "0.51 alter glpi_state_item table name");

      $query = "ALTER TABLE `glpi_state_item`
                ADD `state` INT DEFAULT '1'";
      $DB->queryOrDie($query, "0.51 add state field");
   }

   if (!$DB->tableExists("glpi_dropdown_state")) {
      $query = "CREATE TABLE `glpi_dropdown_state` (
                  `ID` int(11) NOT NULL auto_increment,
                  `name` varchar(255) default NULL,
                  PRIMARY KEY (`ID`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.51 add state field");
   }

}

