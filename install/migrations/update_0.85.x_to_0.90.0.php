<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

use Glpi\DBAL\QueryFunction;

/**
 * Update from 0.85.5 to 0.90
 *
 * @return bool for success (will die for most error)
 **/
function update085xto0900()
{
    global $DB, $migration;

    $updateresult     = true;
    $ADDTODISPLAYPREF = [];

   //TRANS: %s is the number of new version
    $migration->displayTitle(sprintf(__('Update to %s'), '0.90'));
    $migration->setVersion('0.90');

    $backup_tables = false;
    $newtables     = [];

    foreach ($newtables as $new_table) {
       // rename new tables if exists ?
        if ($DB->tableExists($new_table)) {
            $migration->dropTable("backup_$new_table");
            $migration->displayWarning("$new_table table already exists. " .
                                    "A backup have been done to backup_$new_table.");
            $backup_tables = true;
            $query         = $migration->renameTable("$new_table", "backup_$new_table");
        }
    }
    if ($backup_tables) {
        $migration->displayWarning(
            "You can delete backup tables if you have no need of them.",
            true
        );
    }

   // Add Color selector
    Config::setConfigurationValues('core', ['palette' => 'auror']);
    $migration->addField("glpi_users", "palette", "char(20) DEFAULT NULL");

   // add layout config
    Config::setConfigurationValues('core', ['layout' => 'lefttab']);
    $migration->addField("glpi_users", "layout", "char(20) DEFAULT NULL");

   // add timeline config
    Config::setConfigurationValues('core', ['ticket_timeline' => 1]);
    Config::setConfigurationValues('core', ['ticket_timeline_keep_replaced_tabs' => 0]);
    $migration->addField("glpi_users", "ticket_timeline", "tinyint DEFAULT NULL");
    $migration->addField("glpi_users", "ticket_timeline_keep_replaced_tabs", "tinyint DEFAULT NULL");

   // clean unused parameter
    $migration->dropField("glpi_users", "dropdown_chars_limit");
    Config::deleteConfigurationValues('core', ['name' => 'dropdown_chars_limit']);

   // ************ Keep it at the end **************
   //TRANS: %s is the table or item to migrate
    $migration->displayMessage(sprintf(__('Data migration - %s'), 'glpi_displaypreferences'));

    foreach ($ADDTODISPLAYPREF as $type => $tab) {
        $it = $DB->request([
            'SELECT' => ['users_id'],
            'DISTINCT' => true,
            'FROM' => 'glpi_displaypreferences',
            'WHERE' => ['itemtype' => $type]
        ]);

        if (count($it) > 0) {
            foreach ($it as $data) {
                $rank = $DB->request([
                    'SELECT' => [
                        QueryFunction::max('rank')
                    ],
                    'FROM' => 'glpi_displaypreferences',
                    'WHERE' => [
                        'users_id' => $data['users_id'],
                        'itemtype' => $type
                    ]
                ])->current()['rank']++;

                foreach ($tab as $newval) {
                    $it2 = $DB->request([
                        'FROM' => 'glpi_displaypreferences',
                        'WHERE' => [
                            'users_id' => $data['users_id'],
                            'num' => $newval,
                            'itemtype' => $type
                        ]
                    ]);
                    if (count($it2) === 0) {
                        $DB->insertOrDie('glpi_displaypreferences', [
                            'itemtype' => $type,
                            'num' => $newval,
                            'rank' => $rank++,
                            'users_id' => $data['users_id']
                        ]);
                    }
                }
            }
        } else { // Add for default user
            $rank = 1;
            foreach ($tab as $newval) {
                $DB->insertOrDie('glpi_displaypreferences', [
                    'itemtype' => $type,
                    'num' => $newval,
                    'rank' => $rank++,
                    'users_id' => 0
                ]);
            }
        }
    }
   // change type of field solution in ticket.change and problem
    $migration->changeField('glpi_tickets', 'solution', 'solution', 'longtext');
    $migration->changeField('glpi_changes', 'solution', 'solution', 'longtext');
    $migration->changeField('glpi_problems', 'solution', 'solution', 'longtext');

   // must always be at the end
    $migration->executeMigration();

    return $updateresult;
}
