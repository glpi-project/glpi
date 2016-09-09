<?php
/*
 * @version $Id$
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

/// Update from 0.4 and 0.41 to 0.42
function update04to042() {
   global $DB;

   echo "<p class='center'>Version 0.42 </p>";

   if (!TableExists("glpi_reservation_item")) {
      $query = "CREATE TABLE `glpi_reservation_item` (
                  `ID` int(11) NOT NULL auto_increment,
                  `device_type` tinyint(4) NOT NULL default '0',
                  `id_device` int(11) NOT NULL default '0',
                  `comments` text NOT NULL,
                  PRIMARY KEY  (`ID`),
                  KEY `device_type` (`device_type`)
                )";
      $DB->queryOrDie($query, "4201");
   }

   if (!TableExists("glpi_reservation_resa")) {
      $query = "CREATE TABLE `glpi_reservation_resa` (
                  `ID` bigint(20) NOT NULL auto_increment,
                  `id_item` int(11) NOT NULL default '0',
                  `begin` datetime NOT NULL default '0000-00-00 00:00:00',
                  `end` datetime NOT NULL default '0000-00-00 00:00:00',
                  `id_user` int(11) NOT NULL default '0',
                  PRIMARY KEY  (`ID`),
                  KEY `id_item` (`id_item`),
                  KEY `id_user` (`id_user`),
                  KEY `begin` (`begin`),
                  KEY `end` (`end`)
                )";
      $DB->queryOrDie($query, "4202");
   }

   if (!FieldExists("glpi_tracking","device_type", false)) {
      $query = "ALTER TABLE `glpi_tracking`
                ADD `device_type` INT DEFAULT '1' NOT NULL AFTER `assign` ";
      $DB->queryOrDie($query, "4203");
   }

   // Ajout language par defaut
   if (!FieldExists("glpi_config","default_language", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `default_language` VARCHAR(255) DEFAULT 'english' NOT NULL ";
      $DB->queryOrDie($query, "4204");
   }

}

?>
