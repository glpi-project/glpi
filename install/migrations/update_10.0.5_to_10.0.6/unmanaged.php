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
 * @var DB $DB
 * @var Migration $migration
 */

//set default configuration for import_unmanaged
$migration->addConfig(['import_unmanaged' => 1], 'inventory');

//add last_inventory_update field
$migration->addField('glpi_unmanageds', 'last_inventory_update', 'timestamp');
$migration->addField("glpi_unmanageds", "groups_id_tech", "int unsigned NOT NULL DEFAULT '0'", ["after" => "states_id"]);
$migration->addKey('glpi_unmanageds', 'groups_id_tech');

// add default rules for unmanaged device
$rules[] = [
    'name'      => 'Unmanaged update (by name)',
    'uuid'      => 'glpi_rule_import_asset_unmanaged_update_name',
    'match'     => 'AND',
    'is_active' => 1,
    'criteria'  => [
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
    'action'    => [
        [
            'field'         => '_inventory',
            'action_type'  => "assign",
            'value'         => RuleImportAsset::RULE_ACTION_LINK_OR_IMPORT,
        ]
    ]
];

$rules[] = [
    'name'      => 'Unmanaged import (by name)',
    'uuid'      => 'glpi_rule_import_asset_unmanaged_import_name',
    'match'     => 'AND',
    'is_active' => 1,
    'criteria'  => [
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
    'action'    => [
        [
            'field'         => '_inventory',
            'action_type'  => "assign",
            'value'         => RuleImportAsset::RULE_ACTION_LINK_OR_IMPORT,
        ]
    ]
];

$rules[] = [
    'name'      => 'Unmanaged import denied',
    'uuid'      => 'glpi_rule_import_asset_unmanaged_import_denied',
    'match'     => 'AND',
    'is_active' => 1,
    'criteria'  => [
        [
            'criteria'  => 'itemtype',
            'condition' => Rule::PATTERN_IS,
            'pattern'   => 'Unmanaged'
        ]
    ],
    'action'    => [
        [
            'field'         => '_inventory',
            'action_type'  => "assign",
            'value'         => RuleImportAsset::RULE_ACTION_DENIED
        ]
    ]
];

$query = "SELECT MAX(`ranking`) FROM `glpi_rules`";
$result = $DB->query($query);
$ranking   = $DB->result($result, 0, 0);

foreach ($rules as $rule) {
    $rulecollection = new RuleImportAssetCollection();
    $input = [
        'is_active' => $rule['is_active'],
        'name'      => $rule['name'],
        'uuid'      => $rule['uuid'],
        'match'     => $rule['match'],
        'sub_type'  => RuleImportAsset::getType(),
        'ranking'   => $ranking
    ];

    if ($rulecollection->getFromDBByCrit(['uuid' => $rule['uuid']])) {
        //rule already exists with uuid, ignore.
        continue;
    }

    //create rule
    $DB->queryOrDie($DB->buildInsert(
        "glpi_rules",
        [
            'is_active' => $input['is_active'],
            'name'      => $input['name'],
            'match'     => $input['match'],
            'sub_type'  => $input['sub_type'],
            'uuid'      => $input['uuid'],
            'ranking'   => $input['ranking'],
        ]
    ), "10.0.0.6 add Unmanaged RuleIMportAsset");
    $rule_id = $DB->insertId();

    // Add criteria
    foreach ($rule['criteria'] as $criteria) {
        $DB->queryOrDie($DB->buildInsert(
            "glpi_rulecriterias",
            [
                'rules_id'  => $rule_id,
                'criteria'  => $criteria['criteria'],
                'condition' => $criteria['condition'],
                'pattern'   => $criteria['pattern'],
            ]
        ));
    }

    // Add action
    foreach ($rule['action'] as $action) {
        $DB->queryOrDie($DB->buildInsert(
            "glpi_ruleactions",
            [
                'rules_id'      => $rule_id,
                'action_type'   => $action['action_type'],
                'field'         => $action['field'],
                'value'         => $action['value'],
            ]
        ));
    }

    $ranking++;
}
