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

/// Update from 0.71 to 0.71.2
function update071to0712() {
   global $DB, $CFG_GLPI;


   $query = "UPDATE `glpi_display`
             SET `num` = 120
             WHERE `num` = 121";
   $DB->queryOrDie($query, "0.71.2 Update display index in view item");

   $query = "UPDATE `glpi_rules_actions`
             SET `field` = '_ignore_ocs_import'
             WHERE `action_type` = 'ignore'";
   $DB->queryOrDie($query, "0.71.2 Update ignore field for soft dict");

} // fin 0.71 #####################################################################################
?>
