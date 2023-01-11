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

global $DB;

/** Rename 'name' criteria in dictionnaries */
//move criteria 'name' to 'os_name' for 'RuleDictionnaryOperatingSystem'
//move criteria 'name' to 'os_version' for 'RuleDictionnaryOperatingSystemVersion'
//move criteria 'name' to 'os_edition' for 'RuleDictionnaryOperatingSystemEdition'
//move criteria 'name' to 'arch_name' for 'RuleDictionnaryOperatingSystemArchitecture'
//move criteria 'name' to 'servicepack_name' for 'RuleDictionnaryOperatingSystemServicePack'

$subType = [
    'servicepack_name' => 'RuleDictionnaryOperatingSystemServicePack',
    'os_edition' => 'RuleDictionnaryOperatingSystemEdition',
    'arch_name' => 'RuleDictionnaryOperatingSystemArchitecture',
    'os_version' => 'RuleDictionnaryOperatingSystemVersion',
    'os_name' => 'RuleDictionnaryOperatingSystem',
];

//Get all glpi_rulecriteria with 'name' criteria for OS Dictionnary
$result = $DB->request(
    [
        'SELECT'    => [
            'glpi_rulecriterias.id AS criteria_id' ,
            'glpi_rulecriterias.criteria' ,
            'glpi_rules.sub_type' ,
        ],
        'FROM'      => 'glpi_rulecriterias',
        'LEFT JOIN' => [
            'glpi_rules' => [
                'FKEY' => [
                    'glpi_rulecriterias'   => 'rules_id',
                    'glpi_rules'            => 'id',
                ]
            ]
        ],
        'WHERE'     => [
            'glpi_rulecriterias.criteria'      => 'name',
            'glpi_rules.sub_type' => array_values($subType)
        ],
    ]
);

//foreach criteria, change 'name' key to desired
foreach ($result as $data) {
    $migration->addPostQuery(
        $DB->buildUpdate(
            'glpi_rulecriterias',
            [
                'criteria' => array_search($data['sub_type'], $subType),
            ],
            [
                'id' => $data['criteria_id'],
            ]
        )
    );
}
/** /Rename 'name' criteria in dictionnaries */

/** Init 'initialized_rules_collections' config */
$migration->addConfig(['initialized_rules_collections' => '[]']);
/** /Init 'initialized_rules_collections' config */

/** Fix 'contact' rule criteria */
$migration->addPostQuery(
    $DB->buildUpdate(
        'glpi_rulecriterias',
        [
            'pattern' => $DB->escape('/(.*)[,|\/]/'),
        ],
        [
            'id' => 19,
            'pattern' => '/(.*)[,|/]/',
        ]
    )
);
/** /Fix 'contact' rule criteria */
