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

/**
 * @var \DBmysql $DB
 * @var \Migration $migration
 */

//move criteria 'os_name' to 'name' for 'RuleDictionnaryOperatingSystem'
//move criteria 'os_version' to 'name' for 'RuleDictionnaryOperatingSystemVersion'
//move criteria 'os_edition' to 'name' for 'RuleDictionnaryOperatingSystemEdition'
//move criteria 'arch_name' to 'name' for 'RuleDictionnaryOperatingSystemArchitecture'
//move criteria 'servicepack_name' to 'name' for 'RuleDictionnaryOperatingSystemServicePack'

$sub_types = [
    'servicepack_name' => 'RuleDictionnaryOperatingSystemServicePack',
    'os_edition' => 'RuleDictionnaryOperatingSystemEdition',
    'arch_name' => 'RuleDictionnaryOperatingSystemArchitecture',
    'os_version' => 'RuleDictionnaryOperatingSystemVersion',
    'os_name' => 'RuleDictionnaryOperatingSystem',
];

//Get all glpi_rulecrtiteria with 'name' criteria for OS Dictionnary
foreach ($sub_types as $criteria => $sub_type) {
    $migration->addPostQuery(
        $DB->buildUpdate(
            'glpi_rulecriterias',
            ['criteria' => 'name'],
            ['criteria' => $criteria],
            [
                'INNER JOIN' => [
                    'glpi_rules' => [
                        'FKEY' => [
                            'glpi_rulecriterias' => 'rules_id',
                            'glpi_rules' => 'id',
                            [
                                'AND' => ['glpi_rules.sub_type' => $sub_type],
                            ]
                        ],
                    ],
                ],
            ]
        )
    );
}
