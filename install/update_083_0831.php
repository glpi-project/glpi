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
 * Update from 0.83 to 0.83.1
 *
 * @return bool for success (will die for most error)
**/
function update083to0831() {
   global $migration;

   $migration->displayTitle(sprintf(__('Update to %s'), '0.83.1'));
   $migration->setVersion('0.83.1');

   $migration->addField('glpi_configs', 'allow_search_view', 'integer', ['value' => 2]);
   $migration->addField('glpi_configs', 'allow_search_all', 'bool', ['value' => 1]);
   $migration->addField('glpi_configs', 'allow_search_global', 'bool', ['value' => 1]);

   $migration->addKey('glpi_tickets', 'name');

   $migration->addField("glpi_profiles", "knowbase_admin", "char",
                        ['after'     => "knowbase",
                              'update'    => "1",
                              'condition' => ['config' => 'w']]);

   $migration->addField("glpi_configs", "display_count_on_home", "integer", ['value' => 5]);
   $migration->addField("glpi_users", "display_count_on_home", "int(11) NULL DEFAULT NULL");

   // ************ Keep it at the end **************
   $migration->displayMessage('Migration of glpi_displaypreferences');

   // must always be at the end
   $migration->executeMigration();

   return true;
}
