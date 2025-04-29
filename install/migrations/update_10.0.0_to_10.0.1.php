<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
 * Update from 10.0.0 to 10.0.1
 *
 * @return bool for success (will die for most error)
 **/
function update1000to1001()
{
    /**
     * @var \DBmysql $DB
     * @var \Migration $migration
     */
    global $DB, $migration;

    $updateresult       = true;
    $ADDTODISPLAYPREF   = [];
    $DELFROMDISPLAYPREF = [];
    $update_dir = __DIR__ . '/update_10.0.0_to_10.0.1/';

    //TRANS: %s is the number of new version
    $migration->displayTitle(sprintf(__('Update to %s'), '10.0.1'));
    $migration->setVersion('10.0.1');

    $update_scripts = scandir($update_dir);
    foreach ($update_scripts as $update_script) {
        if (preg_match('/\.php$/', $update_script) !== 1) {
            continue;
        }
        require $update_dir . $update_script;
    }

    // ************ Keep it at the end **************
    foreach ($ADDTODISPLAYPREF as $type => $tab) {
        $rank = 1;
        foreach ($tab as $newval) {
            $DB->updateOrInsert(
                "glpi_displaypreferences",
                [
                    'rank'      => $rank++,
                ],
                Toolbox::addslashes_deep(
                    [
                        'users_id'  => "0",
                        'itemtype'  => $type,
                        'num'       => $newval,
                    ]
                )
            );
        }
    }
    foreach ($DELFROMDISPLAYPREF as $type => $tab) {
        $DB->deleteOrDie(
            'glpi_displaypreferences',
            Toolbox::addslashes_deep(
                [
                    'itemtype'  => $type,
                    'num'       => $tab,
                ]
            )
        );
    }

    $migration->executeMigration();

    return $updateresult;
}
