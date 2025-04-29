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
 * Update from 0.80 to 0.80.1
 *
 * @return bool for success (will die for most error)
 **/
function update0800to0801()
{
    /**
     * @var \DBmysql $DB
     * @var \Migration $migration
     */
    global $DB, $migration;

    $updateresult     = true;
    $ADDTODISPLAYPREF = [];

    //TRANS: %s is the number of new version
    $migration->displayTitle(sprintf(__('Update to %s'), '0.80.1'));
    $migration->setVersion('0.80.1');

    // Clean duplicates
    $query = "SELECT COUNT(*) AS CPT, `tickets_id`, `type`, `groups_id`
             FROM `glpi_groups_tickets`
             GROUP BY `tickets_id`, `type`, `groups_id`
             HAVING CPT > 1";
    if ($result = $DB->doQuery($query)) {
        if ($DB->numrows($result) > 0) {
            while ($data = $DB->fetchArray($result)) {
                // Skip first
                $query = "SELECT `id`
                      FROM `glpi_groups_tickets`
                      WHERE `tickets_id` = '" . $data['tickets_id'] . "'
                            AND `type` = '" . $data['type'] . "'
                            AND `groups_id` = '" . $data['groups_id'] . "'
                      ORDER BY `id` DESC
                      LIMIT 1,99999";
                if ($result2 = $DB->doQuery($query)) {
                    if ($DB->numrows($result2)) {
                        while ($data2 = $DB->fetchArray($result2)) {
                            $query = "DELETE
                               FROM `glpi_groups_tickets`
                               WHERE `id` ='" . $data2['id'] . "'";
                            $DB->doQueryOrDie($query, "0.80.1 clean to update glpi_groups_tickets");
                        }
                    }
                }
            }
        }
    }
    $migration->dropKey('glpi_groups_tickets', 'unicity');
    $migration->migrationOneTable('glpi_groups_tickets');
    $migration->addKey(
        "glpi_groups_tickets",
        ['tickets_id', 'type','groups_id'],
        "unicity",
        "UNIQUE"
    );

    // Clean duplicates
    $query = "SELECT COUNT(*) AS CPT, `tickets_id`, `type`, `users_id`, `alternative_email`
             FROM `glpi_tickets_users`
             GROUP BY `tickets_id`, `type`, `users_id`, `alternative_email`
             HAVING CPT > 1";
    if ($result = $DB->doQuery($query)) {
        if ($DB->numrows($result) > 0) {
            while ($data = $DB->fetchArray($result)) {
                // Skip first
                $query = "SELECT `id`
                      FROM `glpi_tickets_users`
                      WHERE `tickets_id` = '" . $data['tickets_id'] . "'
                            AND `type` = '" . $data['type'] . "'
                            AND `users_id` = '" . $data['users_id'] . "'
                            AND `alternative_email` = '" . $data['alternative_email'] . "'
                      ORDER BY `id` DESC
                      LIMIT 1,99999";
                if ($result2 = $DB->doQuery($query)) {
                    if ($DB->numrows($result2)) {
                        while ($data2 = $DB->fetchArray($result2)) {
                            $query = "DELETE
                               FROM `glpi_tickets_users`
                               WHERE `id` ='" . $data2['id'] . "'";
                            $DB->doQueryOrDie($query, "0.80.1 clean to update glpi_tickets_users");
                        }
                    }
                }
            }
        }
    }
    $migration->dropKey('glpi_tickets_users', 'tickets_id');
    $migration->migrationOneTable('glpi_tickets_users');
    $migration->addKey(
        "glpi_tickets_users",
        ['tickets_id', 'type','users_id','alternative_email'],
        "unicity",
        "UNIQUE"
    );

    $migration->addField("glpi_ocsservers", "ocs_version", "VARCHAR( 255 ) NULL");

    if ($migration->addField("glpi_slalevels", "entities_id", "INT NOT NULL DEFAULT 0")) {
        $migration->addField("glpi_slalevels", "is_recursive", "TINYINT NOT NULL DEFAULT 0");
        $migration->migrationOneTable('glpi_slalevels');

        $entities    = getAllDataFromTable('glpi_entities');
        $entities[0] = "Root";

        foreach (array_keys($entities) as $entID) {
            // Non recursive ones
            $query3 = "UPDATE `glpi_slalevels`
                    SET `entities_id` = $entID, `is_recursive` = 0
                    WHERE `slas_id` IN (SELECT `id`
                                        FROM `glpi_slas`
                                        WHERE `entities_id` = $entID
                                              AND `is_recursive` = 0)";
            $DB->doQueryOrDie($query3, "0.80.1 update entities_id and is_recursive=0 in glpi_slalevels");

            // Recursive ones
            $query3 = "UPDATE `glpi_slalevels`
                    SET `entities_id` = $entID, `is_recursive` = 1
                    WHERE `slas_id` IN (SELECT `id`
                                        FROM `glpi_slas`
                                        WHERE `entities_id` = $entID
                                              AND `is_recursive` = 1)";
            $DB->doQueryOrDie($query3, "0.80.1 update entities_id and is_recursive=1 in glpi_slalevels");
        }
    }

    // must always be at the end
    $migration->executeMigration();

    return $updateresult;
}
