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
 * Update from 9.4.0 to 9.4.1
 *
 * @return bool for success (will die for most error)
**/
function update940to941() {
   global $DB, $migration;

   $updateresult     = true;

   /** Add a search option for profile id */
   $migration->addPostQuery($DB->buildUpdate(
      'glpi_displaypreferences',
      [
         'num' => '5'
      ],
      [
         'num' => '2',
         'itemtype' => 'Profile'
      ]
   ));

   // ************ Keep it at the end **************
   $migration->executeMigration();

   return $updateresult;
}
