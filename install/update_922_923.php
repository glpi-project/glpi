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
 * Update from 9.2.2 to 9.2.3
 *
 * @return bool for success (will die for most error)
**/
function update922to923() {
   global $DB, $migration, $CFG_GLPI;

   $current_config   = Config::getConfigurationValues('core');
   $updateresult     = true;
   $ADDTODISPLAYPREF = [];

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), '9.2.3'));
   $migration->setVersion('9.2.3');

   //add a column for the model
   if (!$DB->fieldExists("glpi_devicepcis", "devicenetworkcardmodels_id")) {
      $migration->addField(
         "glpi_devicepcis",
         "devicenetworkcardmodels_id",
         "int(11) NOT NULL DEFAULT '0'",
         ['after' => 'manufacturers_id']
      );
      $migration->addKey('glpi_devicepcis', 'devicenetworkcardmodels_id');
   }

   //fix notificationtemplates_id in translations table
   $notifs = [
      'Certificate',
      'SavedSearch_Alert'
   ];
   foreach ($notifs as $notif) {
      $notification = new Notification();
      $template = new NotificationTemplate();

      if ($notification->getFromDBByCrit(['itemtype' => $notif, 'event' => 'alert'])
         && $template->getFromDBByCrit(['itemtype' => $notif])
      ) {
         $query = "UPDATE glpi_notificationtemplatetranslations SET " .
            "notificationtemplates_id = " . $template->fields['id'] .
            " WHERE notificationtemplates_id = " . $notification->fields['id'];
         $DB->queryOrDie($query);

         if ($notif == 'SavedSearch_Alert'
            && countElementsInTable(
               'glpi_notifications_notificationtemplates',
               "notifications_id = ".$notification->fields['id'].
               " AND notificationtemplates_id = ".$template->fields['id'] .
               " AND mode = '".Notification_NotificationTemplate::MODE_MAIL."'"
            ) == 0
         ) {
            //Add missing notification template link for saved searches
            $query = "INSERT INTO glpi_notifications_notificationtemplates " .
               "(notifications_id, mode, notificationtemplates_id) " .
               "VALUES(".$notification->fields['id'].", '".Notification_NotificationTemplate::MODE_MAIL.
               "', ".$template->fields['id'].")";
            $DB->queryOrDie($query);
         }
      }
   }

   // ************ Keep it at the end **************
   $migration->executeMigration();

   return $updateresult;
}
