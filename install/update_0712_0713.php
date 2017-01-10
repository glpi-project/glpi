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

/// Update from 0.71.2 to 0.71.3
function update0712to0713() {
   global $DB, $CFG_GLPI;

   if (!FieldExists("glpi_rule_cache_software", "ignore_ocs_import", false)) {
      $query = "ALTER TABLE `glpi_rule_cache_software`
                ADD `ignore_ocs_import` VARCHAR( 255 ) NULL ";
      $DB->queryOrDie($query, "0.71.3 add ignore_ocs_import field in dictionary cache");
   }

   // Update to longtext for fields which may be very long
   if (FieldExists("glpi_kbitems", "answer", false)) {

      if (isIndex("glpi_kbitems","fulltext")) { // to avoid pb in altering column answer
         $query = "ALTER TABLE `glpi_kbitems`
                   DROP INDEX `fulltext`";
         $DB->queryOrDie($query, "0.71.3 alter kbitem drop index Fulltext");
      }

      // field question : only to change latin1 to utf-8 if not done in update 0.68.3 to 0.71
      // before creating index fulltext based on 2 fields (perhaps both are not in same encoding)
      $query = "ALTER TABLE `glpi_kbitems`
                CHANGE `question` `question` TEXT,
                CHANGE `answer` `answer` LONGTEXT NULL DEFAULT NULL ";
      $DB->queryOrDie($query, "0.71.3 alter kbitem answer field to longtext");

      $query = "ALTER TABLE `glpi_kbitems`
                ADD FULLTEXT `fulltext` (`question`,`answer`)";
      $DB->queryOrDie($query, "0.71.3 alter kbitem re-add index Fulltext");
   }

   if (FieldExists("glpi_tracking", "contents", false)) {
      $query = "ALTER TABLE `glpi_tracking`
                CHANGE `contents` `contents` LONGTEXT NULL DEFAULT NULL ";
      $DB->queryOrDie($query, "0.71.3 alter tracking contents field to longtext");
   }


}
?>
