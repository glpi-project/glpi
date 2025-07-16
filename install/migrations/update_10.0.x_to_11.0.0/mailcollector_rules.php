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

use Glpi\DBAL\QueryExpression;

/**
 * @var DBmysql $DB
 * @var Migration $migration
 */
// Add a rule to refuse emails that corresponds to a GLPI notification
if (countElementsInTable('glpi_rules', ['uuid' => 'glpi_rule_mail_collector_glpi_notifications']) === 0) {
    // Add the missing rule
    $migration->createRule(
        [
            'name'          => 'GLPI notifications',
            'description'   => 'Exclude emails corresponding to a GLPI notification',
            'uuid'          => 'glpi_rule_mail_collector_glpi_notifications',
            'match'         => 'AND',
            'sub_type'      => 'RuleMailCollector',
            'is_active'     => 1,
            'entities_id'   => 0,
            'is_recursive'  => 1,
            'condition'     => 0,
        ],
        [
            [
                'criteria'  => 'message_id',
                'condition' => 6,
                'pattern'   => '/GLPI(_(?<uuid>[a-z0-9]+))?(-(?<itemtype>[a-z]+))?(-(?<items_id>[0-9]+))?(\/(?<event>[a-z_]+))?(\.(?<random>[0-9]+\.[0-9]+))?@(?<uname>.+)/i',
            ],
        ],
        [
            [
                'field'         => '_refuse_email_no_response',
                'action_type'  => "assign",
                'value'         => 1,
            ],
        ]
    );

    // Move all rules to an higher ranking and put the new rule to first position
    $migration->addPostQuery(
        $DB->buildUpdate(
            'glpi_rules',
            [
                'ranking'   => new QueryExpression($DB::quoteName('ranking') . ' + 1'),
            ],
            [
                'sub_type'  => 'RuleMailCollector',
            ]
        )
    );
    $migration->addPostQuery(
        $DB->buildUpdate(
            'glpi_rules',
            [
                'ranking'   => 1,
            ],
            [
                'uuid'      => 'glpi_rule_mail_collector_glpi_notifications',
            ]
        )
    );
}
