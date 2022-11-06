<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

/**
 * Update from 9.5.3 to 9.5.4
 *
 * @return bool for success (will die for most error)
 **/
function update953to954()
{
    global $DB, $migration;

    $updateresult = true;

   //TRANS: %s is the number of new version
    $migration->displayTitle(sprintf(__('Update to %s'), '9.5.4'));
    $migration->setVersion('9.5.4');

   /* Remove invalid Profile SO */
    $DB->delete('glpi_displaypreferences', ['itemtype' => 'Profile', 'num' => 62]);
   /* /Remove invalid Profile SO */

   /* Add is_default_profile */
    $migration->addField("glpi_profiles_users", "is_default_profile", "bool");
   /* /Add is_default_profile */

   // ************ Keep it at the end **************
    $migration->executeMigration();

    return $updateresult;
}
