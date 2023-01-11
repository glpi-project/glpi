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
 * @var DB $DB
 * @var Migration $migration
 */

//set default configuration for import_unmanaged
$migration->addConfig(['import_unmanaged' => 1], 'inventory');

//add last_inventory_update field
$migration->addField('glpi_unmanageds', 'last_inventory_update', 'timestamp');
$migration->addField("glpi_unmanageds", "groups_id_tech", "fkey", ["after" => "states_id"]);
$migration->addKey('glpi_unmanageds', 'groups_id_tech');

// add default rules for unmanaged device if RuleImportAsset already added
if (countElementsInTable(Rule::getTable(), ['sub_type' => 'RuleImportAsset']) > 0) {
    $migration->createRule(
        [
            'name'      => 'Unmanaged update (by name)',
            'uuid'      => 'glpi_rule_import_asset_unmanaged_update_name',
            'match'     => 'AND',
            'sub_type'  => RuleImportAsset::getType(),
            'is_active' => 1
        ],
        [
            [
                'criteria'  => 'itemtype',
                'condition' => Rule::PATTERN_IS,
                'pattern'   => 'Unmanaged'
            ],
            [
                'criteria'  => 'name',
                'condition' => Rule::PATTERN_EXISTS,
                'pattern'   => 1
            ],
            [
                'criteria'  => 'name',
                'condition' => Rule::PATTERN_FIND,
                'pattern'   => 1
            ]
        ],
        [
            [
                'field'         => '_inventory',
                'action_type'  => "assign",
                'value'         => RuleImportAsset::RULE_ACTION_LINK_OR_IMPORT,
            ]
        ]
    );

    $migration->createRule(
        [
            'name'      => 'Unmanaged import (by name)',
            'uuid'      => 'glpi_rule_import_asset_unmanaged_import_name',
            'match'     => 'AND',
            'sub_type'  => RuleImportAsset::getType(),
            'is_active' => 1
        ],
        [
            [
                'criteria'  => 'itemtype',
                'condition' => Rule::PATTERN_IS,
                'pattern'   => 'Unmanaged'
            ],
            [
                'criteria'  => 'name',
                'condition' => Rule::PATTERN_EXISTS,
                'pattern'   => 1
            ]
        ],
        [
            [
                'field'         => '_inventory',
                'action_type'  => "assign",
                'value'         => RuleImportAsset::RULE_ACTION_LINK_OR_IMPORT,
            ]
        ]
    );

    $migration->createRule(
        [
            'name'      => 'Unmanaged import denied',
            'uuid'      => 'glpi_rule_import_asset_unmanaged_import_denied',
            'match'     => 'AND',
            'sub_type'  => RuleImportAsset::getType(),
            'is_active' => 1
        ],
        [
            [
                'criteria'  => 'itemtype',
                'condition' => Rule::PATTERN_IS,
                'pattern'   => 'Unmanaged'
            ]
        ],
        [
            [
                'field'         => '_inventory',
                'action_type'  => "assign",
                'value'         => RuleImportAsset::RULE_ACTION_DENIED
            ]
        ]
    );
}
