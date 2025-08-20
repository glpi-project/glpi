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

use function Safe\preg_replace;

/**
 * Update from 9.4.0 to 9.4.1
 *
 * @return bool
 **/
function update940to941()
{
    /**
     * @var DBmysql $DB
     * @var Migration $migration
     */
    global $DB, $migration;

    $updateresult     = true;

    $migration->setVersion('9.4.1');

    /** Add a search option for profile id */
    if (countElementsInTable('glpi_displaypreferences', ['num' => '2', 'itemtype' => 'Profile'])) {
        // First, update SO ID of 'interface' field display preference
        $migration->addPostQuery($DB->buildUpdate(
            'glpi_displaypreferences',
            [
                'num' => '5',
            ],
            [
                'num' => '2',
                'itemtype' => 'Profile',
            ]
        ));

        // Then add 'id' field display preference
        $rank_result = $DB->request(
            [
                'SELECT' => ['MAX' => 'rank AS maxrank'],
                'FROM'   => 'glpi_displaypreferences',
                'WHERE'  => [
                    'itemtype'  => 'Profile',
                    'users_id'  => '0',
                ],
            ]
        )->current();
        $migration->addPostQuery(
            $DB->buildInsert(
                'glpi_displaypreferences',
                [
                    'num'      => '2',
                    'itemtype' => 'Profile',
                    'users_id' => '0',
                    'rank'     => $rank_result['maxrank'] + 1,
                ]
            )
        );
    }
    /** /Add a search option for profile id */

    /** Fix URL of images inside ITIL objects contents */
    // There is an exact copy of this process in "update941to942()".
    // First version of this migration was working
    // on MariaDB but not on MySQL due to usage of "\d" in a REGEXP expression.
    // It has been fixed here for people who had not yet updated to 9.4.1 but have been put there
    // for people already having updated to 9.4.1.
    $migration->displayMessage(__('Fix URL of images in ITIL tasks, followups and solutions.'));

    // Search for contents that does not contains the itil object parameter after the docid parameter
    // (i.e. having a quote that ends the href just after the docid param value).
    // 1st capturing group is the end of href attribute value
    // 2nd capturing group is the href attribute ending quote
    $quotes_possible_exp   = ['\'', '&apos;', '&#39;', '&#x27;', '"', '&quot', '&#34;', '&#x22;'];
    $missing_param_pattern = '(document\.send\.php\?docid=[0-9]+)(' . implode('|', $quotes_possible_exp) . ')';

    $itil_mappings = [
        'Change' => [
            'itil_table' => 'glpi_changes',
            'itil_fkey'  => 'changes_id',
            'task_table' => 'glpi_changetasks',
        ],
        'Problem' => [
            'itil_table' => 'glpi_problems',
            'itil_fkey'  => 'problems_id',
            'task_table' => 'glpi_problemtasks',
        ],
        'Ticket' => [
            'itil_table' => 'glpi_tickets',
            'itil_fkey'  => 'tickets_id',
            'task_table' => 'glpi_tickettasks',
        ],
    ];

    $fix_content_fct = (fn($content, $itil_id, $itil_fkey)
        // Add itil object param between docid param ($1) and ending quote ($2)
        => preg_replace(
            '/' . $missing_param_pattern . '/',
            '$1&amp;' . http_build_query([$itil_fkey => $itil_id]) . '$2',
            $content
        ));

    foreach ($itil_mappings as $itil_type => $itil_specs) {
        $itil_fkey  = $itil_specs['itil_fkey'];
        $task_table = $itil_specs['task_table'];

        // Fix followups and solutions
        foreach (['glpi_itilfollowups', 'glpi_itilsolutions'] as $itil_element_table) {
            $elements_to_fix = $DB->request(
                [
                    'SELECT'    => ['id', 'items_id', 'content'],
                    'FROM'      => $itil_element_table,
                    'WHERE'     => [
                        'itemtype' => $itil_type,
                        'content'  => ['REGEXP', $missing_param_pattern],
                    ],
                ]
            );
            foreach ($elements_to_fix as $data) {
                $data['content'] = $fix_content_fct($data['content'], $data['items_id'], $itil_fkey);
                $DB->update($itil_element_table, $data, ['id' => $data['id']]);
            }
        }

        // Fix tasks
        $tasks_to_fix = $DB->request(
            [
                'SELECT'    => ['id', $itil_fkey, 'content'],
                'FROM'      => $task_table,
                'WHERE'     => [
                    'content'  => ['REGEXP', $missing_param_pattern],
                ],
            ]
        );
        foreach ($tasks_to_fix as $data) {
            $data['content'] = $fix_content_fct($data['content'], $data[$itil_fkey], $itil_fkey);
            $DB->update($task_table, $data, ['id' => $data['id']]);
        }
    }
    /** /Fix URL of images inside ITIL objects contents */

    // Create a dedicated token for rememberme process
    if (!$DB->fieldExists('glpi_users', 'cookie_token')) {
        $migration->addField('glpi_users', 'cookie_token', 'string', ['after' => 'api_token_date']);
        $migration->addField('glpi_users', 'cookie_token_date', 'datetime', ['after' => 'cookie_token']);
    }

    // ************ Keep it at the end **************
    $migration->executeMigration();

    return $updateresult;
}
