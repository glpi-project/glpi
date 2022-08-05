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

use Glpi\Toolbox\Sanitizer;

/**
 * @var DB $DB
 * @var Migration $migration
 */

$itil_types = ['Ticket', 'Change', 'Problem'];
$iterator = $DB->request([
    'SELECT' => ['id'],
    'FROM'   => 'glpi_notifications',
    'WHERE'  => [
        'itemtype' => $itil_types,
        'event'    => 'assign_group',
    ],
]);

foreach ($iterator as $notification) {
    $target_iterator = $DB->request([
        'SELECT' => ['id', 'items_id'],
        'FROM'   => 'glpi_notificationtargets',
        'WHERE'  => [
            'notifications_id' => $notification['id']
        ],
    ]);
    $targets = [];
    foreach ($target_iterator as $target) {
        $targets[$target['id']] = $target['items_id'];
    }
    $removed_item_group = false;
    foreach ($targets as $target_id => $target) {
        if ((int) $target['items_id'] === Notification::ITEM_TECH_GROUP_IN_CHARGE) {
            $DB->deleteOrDie('glpi_notificationtargets', [
                'id' => $target['id'],
            ]);
            $removed_item_group = true;
            unset($targets[$target_id]);
        }
    }
    // If no targets remain, add a new one with items_id = Notification::ASSIGN_GROUP
    if (count($targets) === 0) {
        $DB->insertOrDie('glpi_notificationtargets', [
            'notifications_id'  => $notification['id'],
            'type'              => Notification::USER_TYPE,
            'items_id'          => Notification::ASSIGN_GROUP,
        ]);
    }
}
