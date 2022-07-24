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
 * Update from 9.5.x to 10.0.0
 *
 * @return bool for success (will die for most error)
 **/
function update95xto1000()
{
    global $DB, $migration;

    $updateresult       = true;
    $ADDTODISPLAYPREF   = [];
    $DELFROMDISPLAYPREF = [];
    $update_dir = __DIR__ . '/update_9.5.x_to_10.0.0/';

   //TRANS: %s is the number of new version
    $migration->displayTitle(sprintf(__('Update to %s'), '10.0.0'));
    $migration->setVersion('10.0.0');

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
                    'rank'      => $rank++
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
        $DB->delete(
            'glpi_displaypreferences',
            Toolbox::addslashes_deep(
                [
                    'itemtype'  => $type,
                    'num'       => $tab
                ]
            )
        );
    }

    $migration->executeMigration();

    $migration->displayWarning(
        '"utf8mb4" support requires additional migration which can be performed via the "php bin/console glpi:migration:utf8mb4" command.'
    );

    return $updateresult;
}
