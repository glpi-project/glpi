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
 * Update from 9.4.3 to 9.4.5
 *
 * @return bool for success (will die for most error)
 **/
function update943to945()
{
    /**
     * @var \DBmysql $DB
     * @var \Migration $migration
     */
    global $DB, $migration;

    $updateresult     = true;

    //TRANS: %s is the number of new version
    $migration->displayTitle(sprintf(__('Update to %s'), '9.4.5'));
    $migration->setVersion('9.4.5');

    /** Add OLA TTR begin date field to Tickets */
    $iterator = new DBmysqlIterator(null);
    $migration->addField(
        'glpi_tickets',
        'ola_ttr_begin_date',
        'datetime',
        [
            'after'     => 'olalevels_id_ttr',
            'update'    => $DB->quoteName('date'), // Assign ticket creation date by default
            'condition' => 'WHERE ' . $iterator->analyseCrit(['NOT' => ['olas_id_ttr' => '0']]),
        ]
    );
    /** /Add OLA TTR begin date field to Tickets */

    /** Fix language fields */
    $translatable_tables = [
        'glpi_dropdowntranslations'             => 'DEFAULT NULL',
        'glpi_knowbaseitemtranslations'         => 'DEFAULT NULL',
        'glpi_notificationtemplatetranslations' => "NOT NULL DEFAULT ''",
        'glpi_knowbaseitems_revisions'          => 'DEFAULT NULL',
        'glpi_knowbaseitems_comments'           => 'DEFAULT NULL',
    ];
    foreach ($translatable_tables as $table => $default) {
        $migration->changeField(
            $table,
            'language',
            'language',
            'varchar(10) COLLATE utf8_unicode_ci ' . $default
        );
        $migration->addPostQuery(
            $DB->buildUpdate(
                $table,
                ['language' => 'es_419'],
                ['language' => 'es_41']
            )
        );
    }
    /** /Fix language fields */

    // ************ Keep it at the end **************
    $migration->executeMigration();

    return $updateresult;
}
