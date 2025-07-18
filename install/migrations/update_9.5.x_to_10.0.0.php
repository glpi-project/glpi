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

use function Safe\preg_match;
use function Safe\scandir;

/**
 * Update from 9.5.x to 10.0.0
 *
 * @return bool
 **/
function update95xto1000()
{
    /**
     * @var DBmysql $DB
     * @var Migration $migration
     */
    global $DB, $migration;

    $updateresult       = true;
    $ADDTODISPLAYPREF   = [];
    $DELFROMDISPLAYPREF = [];
    $update_dir = __DIR__ . '/update_9.5.x_to_10.0.0/';

    $migration->setVersion('10.0.0');

    $update_scripts = scandir($update_dir);
    foreach ($update_scripts as $update_script) {
        if (preg_match('/\.php$/', $update_script) !== 1) {
            continue;
        }
        require $update_dir . $update_script;
    }

    // ************ Keep it at the end **************
    $migration->updateDisplayPrefs($ADDTODISPLAYPREF, $DELFROMDISPLAYPREF);

    $migration->executeMigration();

    $migration->addWarningMessage(
        '"utf8mb4" support requires additional migration which can be performed via the "php bin/console migration:utf8mb4" command.'
    );

    return $updateresult;
}
