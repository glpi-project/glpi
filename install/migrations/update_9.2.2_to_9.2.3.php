<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

/** @file
 * @brief
 */

/**
 * Update from 9.2.2 to 9.2.3
 *
 * @return bool for success (will die for most error)
 **/
function update922to923()
{
    /**
     * @var \DBmysql $DB
     * @var \Migration $migration
     */
    global $DB, $migration;

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
            "int NOT NULL DEFAULT '0'",
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

        if (
            $notification->getFromDBByCrit(['itemtype' => $notif, 'event' => 'alert'])
            && $template->getFromDBByCrit(['itemtype' => $notif])
        ) {
            $DB->updateOrDie(
                "glpi_notificationtemplatetranslations",
                ["notificationtemplates_id" => $template->fields['id']],
                ["notificationtemplates_id" => $notification->fields['id']]
            );

            if (
                $notif == 'SavedSearch_Alert'
                && countElementsInTable(
                    'glpi_notifications_notificationtemplates',
                    [
                        'notifications_id'            =>  $notification->fields['id'],
                        'notificationtemplates_id'    => $template->fields['id'],
                        'mode'                        => Notification_NotificationTemplate::MODE_MAIL
                    ]
                ) == 0
            ) {
                //Add missing notification template link for saved searches
                $DB->insertOrDie("glpi_notifications_notificationtemplates", [
                    'notifications_id'         => $notification->fields['id'],
                    'mode'                     => Notification_NotificationTemplate::MODE_MAIL,
                    'notificationtemplates_id' => $template->fields['id']
                ]);
            }
        }
    }

   // ************ Keep it at the end **************
    $migration->executeMigration();

    return $updateresult;
}
