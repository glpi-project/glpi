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
 * Update from 10.0.x to 11.0.0
 *
 * @return bool
 */
function update100xto1100()
{
    /**
     * @var DBmysql $DB
     * @var Migration $migration
     */
    global $DB, $migration;

    $updateresult              = true;
    $ADDTODISPLAYPREF          = [];
    $ADDTODISPLAYPREF_HELPDESK = [];
    $DELFROMDISPLAYPREF        = [];
    $update_dir                = __DIR__ . '/update_10.0.x_to_11.0.0/';

    $migration->setVersion('11.0.0');

    $update_scripts = scandir($update_dir);
    foreach ($update_scripts as $update_script) {
        if (preg_match('/\.php$/', $update_script) !== 1) {
            continue;
        }
        require $update_dir . $update_script;
    }

    // ************ Keep it at the end **************
    $migration->updateDisplayPrefs($ADDTODISPLAYPREF, $DELFROMDISPLAYPREF);

    // @phpstan-ignore foreach.emptyArray (populated from child files)
    foreach ($ADDTODISPLAYPREF_HELPDESK as $type => $tab) {
        $rank = 1;
        foreach ($tab as $newval) {
            $DB->updateOrInsert(
                'glpi_displaypreferences',
                [
                    'rank'      => $rank++,
                ],
                [
                    'users_id'  => '0',
                    'itemtype'  => $type,
                    'num'       => $newval,
                    'interface' => 'helpdesk',
                ]
            );
        }
    }

    $migration->executeMigration();

    return $updateresult;
}
