<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
 * Update from 0.84 to 0.84.1
 *
 * @return bool for success (will die for most error)
 **/
function update0840to0841()
{
    /**
     * @var \DBmysql $DB
     * @var \Migration $migration
     */
    global $DB, $migration;

    $updateresult     = true;
    $ADDTODISPLAYPREF = [];

    //TRANS: %s is the number of new version
    $migration->displayTitle(sprintf(__('Update to %s'), '0.84.1'));
    $migration->setVersion('0.84.1');

    $backup_tables = false;
    $newtables     = [];

    foreach ($newtables as $new_table) {
        // rename new tables if exists ?
        if ($DB->tableExists($new_table)) {
            $migration->dropTable("backup_$new_table");
            $migration->displayWarning("$new_table table already exists. " .
                                    "A backup have been done to backup_$new_table.");
            $backup_tables = true;
            $migration->renameTable("$new_table", "backup_$new_table");
        }
    }
    if ($backup_tables) {
        $migration->displayWarning(
            "You can delete backup tables if you have no need of them.",
            true
        );
    }

    // Add date_mod to document_item
    $migration->addField('glpi_documents_items', 'date_mod', 'datetime');
    $migration->migrationOneTable('glpi_documents_items');
    $query_doc_i = "UPDATE `glpi_documents_items` as `doc_i`
                   INNER JOIN `glpi_documents` as `doc`
                     ON  `doc`.`id` = `doc_i`.`documents_id`
                   SET `doc_i`.`date_mod` = `doc`.`date_mod`";
    $DB->doQueryOrDie(
        $query_doc_i,
        "0.84.1 update date_mod in glpi_documents_items"
    );

    // correct entities_id in documents_items
    $query_doc_i = "UPDATE `glpi_documents_items` as `doc_i`
                   INNER JOIN `glpi_documents` as `doc`
                     ON  `doc`.`id` = `doc_i`.`documents_id`
                   SET `doc_i`.`entities_id` = `doc`.`entities_id`,
                       `doc_i`.`is_recursive` = `doc`.`is_recursive`";
    $DB->doQueryOrDie($query_doc_i, "0.84.1 change entities_id in documents_items");

    // add delete_problem
    $migration->addField(
        'glpi_profiles',
        'delete_problem',
        'char',
        ['after'  => 'edit_all_problem',
            'update' => 'edit_all_problem',
        ]
    );

    // ************ Keep it at the end **************
    //TRANS: %s is the table or item to migrate
    $migration->displayMessage(sprintf(__('Data migration - %s'), 'glpi_displaypreferences'));

    foreach ($ADDTODISPLAYPREF as $type => $tab) {
        $query = "SELECT DISTINCT `users_id`
                FROM `glpi_displaypreferences`
                WHERE `itemtype` = '$type'";

        if ($result = $DB->doQuery($query)) {
            if ($DB->numrows($result) > 0) {
                while ($data = $DB->fetchAssoc($result)) {
                    $query = "SELECT MAX(`rank`)
                         FROM `glpi_displaypreferences`
                         WHERE `users_id` = '" . $data['users_id'] . "'
                               AND `itemtype` = '$type'";
                    $result = $DB->doQuery($query);
                    $rank   = $DB->result($result, 0, 0);
                    $rank++;

                    foreach ($tab as $newval) {
                        $query = "SELECT *
                            FROM `glpi_displaypreferences`
                            WHERE `users_id` = '" . $data['users_id'] . "'
                                  AND `num` = '$newval'
                                  AND `itemtype` = '$type'";
                        if ($result2 = $DB->doQuery($query)) {
                            if ($DB->numrows($result2) == 0) {
                                $query = "INSERT INTO `glpi_displaypreferences`
                                         (`itemtype` ,`num` ,`rank` ,`users_id`)
                                  VALUES ('$type', '$newval', '" . $rank++ . "',
                                          '" . $data['users_id'] . "')";
                                $DB->doQuery($query);
                            }
                        }
                    }
                }
            } else { // Add for default user
                $rank = 1;
                foreach ($tab as $newval) {
                    $query = "INSERT INTO `glpi_displaypreferences`
                                (`itemtype` ,`num` ,`rank` ,`users_id`)
                         VALUES ('$type', '$newval', '" . $rank++ . "', '0')";
                    $DB->doQuery($query);
                }
            }
        }
    }

    // must always be at the end
    $migration->executeMigration();

    return $updateresult;
}
