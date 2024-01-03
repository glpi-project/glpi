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
 * Update from 9.2.1 to 9.2.2
 *
 * @return bool for success (will die for most error)
 **/
function update921to922()
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
    $migration->displayTitle(sprintf(__('Update to %s'), '9.2.2'));
    $migration->setVersion('9.2.2');

    $migration->addConfig([
        'smtp_retry_time' => 5,
    ]);

    $migration->addPostQuery(
        $DB->buildDelete("glpi_configs", [
            'context'   => "core",
            'name'      => "default_graphtype"
        ])
    );

    $migration->addPostQuery(
        $DB->buildDelete(
            "glpi_crontasks",
            ['name' => "optimize"]
        )
    );

   // ************ Keep it at the end **************
    $migration->executeMigration();

    return $updateresult;
}
