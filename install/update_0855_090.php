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
 * Update from 0.85.5 to 0.90
 *
 * @return bool for success (will die for most error)
**/
function update0855to090() {
   global $migration;

   $updateresult = true;

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), '0.90'));
   $migration->setVersion('0.90');

   // Add Color selector
   Config::setConfigurationValues('core', ['palette' => 'auror']);
   $migration->addField("glpi_users", "palette", "char(20) DEFAULT NULL");

   // add layout config
   Config::setConfigurationValues('core', ['layout' => 'lefttab']);
   $migration->addField("glpi_users", "layout", "char(20) DEFAULT NULL");

   // add timeline config
   Config::setConfigurationValues('core', ['ticket_timeline' => 1]);
   Config::setConfigurationValues('core', ['ticket_timeline_keep_replaced_tabs' => 0]);
   $migration->addField("glpi_users", "ticket_timeline", "tinyint(1) DEFAULT NULL");
   $migration->addField("glpi_users", "ticket_timeline_keep_replaced_tabs", "tinyint(1) DEFAULT NULL");

   // clean unused parameter
   $migration->dropField("glpi_users", "dropdown_chars_limit");
   Config::deleteConfigurationValues('core', ['name' => 'dropdown_chars_limit']);

   // change type of field solution in ticket.change and problem
   $migration->changeField('glpi_tickets', 'solution', 'solution', 'longtext');
   $migration->changeField('glpi_changes', 'solution', 'solution', 'longtext');
   $migration->changeField('glpi_problems', 'solution', 'solution', 'longtext');

   // ************ Keep it at the end **************
   $migration->executeMigration();

   return $updateresult;
}
