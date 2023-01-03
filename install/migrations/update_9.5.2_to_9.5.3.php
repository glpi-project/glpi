<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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
 * Update from 9.5.2 to 9.5.3
 *
 * @return bool for success (will die for most error)
 **/
function update952to953()
{
    global $DB, $migration;

    $updateresult     = true;

   //TRANS: %s is the number of new version
    $migration->displayTitle(sprintf(__('Update to %s'), '9.5.3'));
    $migration->setVersion('9.5.3');

   /* Fix rule criteria names */
    $mapping = [
        'RuleMailCollector' => [
            'GROUPS' => '_groups_id_requester'
        ],
        'RuleRight' => [
            'GROUPS' => '_groups_id',
        ],
        'RuleTicket' => [
            'users_locations' => '_locations_id_of_requester',
            'items_locations' => '_locations_id_of_item',
            'items_groups'    => '_groups_id_of_item',
            'items_states'    => '_states_id_of_item',
        ]
    ];
    foreach ($mapping as $type => $names) {
        foreach ($names as $oldname => $newname) {
            $migration->addPostQuery(
                $DB->buildUpdate(
                    'glpi_rulecriterias',
                    ['criteria' => $newname],
                    ['glpi_rulecriterias.criteria' => $oldname, 'glpi_rules.sub_type' => $type],
                    [
                        'LEFT JOIN' => [
                            'glpi_rules' => [
                                'FKEY' => [
                                    'glpi_rulecriterias' => 'rules_id',
                                    'glpi_rules'         => 'id'
                                ],
                            ],
                        ],
                    ]
                )
            );
        }
    }
   /* /Fix rule criteria names */

   // ************ Keep it at the end **************
    $migration->executeMigration();

    return $updateresult;
}
