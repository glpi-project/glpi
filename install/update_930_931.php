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

/**
 * Update from 9.3.0 to 9.3.1
 *
 * @return bool for success (will die for most error)
**/
function update930to931() {
   global $migration;

   $updateresult = true;

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), '9.3.1'));
   $migration->setVersion('9.3.1');

   /** Change field type */
   $migration->changeField(
      'glpi_notifications_notificationtemplates',
      'notifications_id',
      'notifications_id',
      'integer'
   );
   /** /Change field type */

   // add option to hide/show source on login page
   $migration->addConfig(['display_login_source' => 1]);

   // supplier now have use_notification = 1 by default
   $migration->changeField(
      'glpi_suppliers_tickets',
      'use_notification',
      'use_notification',
      'bool', [
         'value' => 1
      ]
   );

   // ************ Keep it at the end **************
   $migration->executeMigration();

   return $updateresult;
}
