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

namespace tests\units\Glpi\Api\HL\RSQL;

use Glpi\Api\HL\Doc\Schema;
use Glpi\Api\HL\Search;
use GLPITestCase;

class Parser extends GLPITestCase
{
    protected function parseProvider()
    {
        return [
            [
                [[5, 'id'], [6, '=='], [7, '20']],
                "(`_`.`id` = '20')"
            ],
            [
                [
                    [3, "("], [3, "("], [5, "model.name"], [6, "=in="], [7, "(A2696,A2757,A2777)"], [1, ";"],
                    [5, "name"], [6, "=like="], [7, "*Staff*"], [4, ")"], [2, ","], [3, "("], [5, "model.name"],
                    [6, "=in="], [7, "(A2602,A2604,A2603,A2605)"], [1, ";"], [5, "name"], [6, "=like="], [7, "*Student*"],
                    [4, ")"], [4, ")"], [2, ","], [5, "name"], [6, "=in="], [7, "(A2436,A2764,A2437,A2766)"]
                ],
                "(((`model`.`name` IN ('A2696', 'A2757', 'A2777')) AND (`_`.`name` LIKE CAST('%Staff%' AS BINARY))) OR ((`model`.`name` IN ('A2602', 'A2604', 'A2603', 'A2605')) AND (`_`.`name` LIKE CAST('%Student%' AS BINARY)))) OR (`_`.`name` IN ('A2436', 'A2764', 'A2437', 'A2766'))"
            ],
            [
                [[5, 'name'], [6, '=='], [7, '(test']],
                "(`_`.`name` = '(test')"
            ]
        ];
    }

    /**
     * @dataProvider parseProvider
     */
    public function testParse(array $tokens, string $expected)
    {
        $schema = [
            'properties' => [
                'id' => ['type' => 'integer'],
                'name' => ['type' => 'string'],
                'comment' => ['type' => 'string'],
                'model' => [
                    'type' => 'object',
                    'x-join' => [],
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'name' => ['type' => 'string'],
                    ]
                ]
            ]
        ];
        $search_class = new \ReflectionClass(Search::class);
        $search = $search_class->newInstanceWithoutConstructor();
        $search_class->getProperty('schema')->setValue($search, $schema);
        $search_class->getProperty('flattened_properties')->setValue($search, Schema::flattenProperties($schema['properties']));
        $search_class->getProperty('joins')->setValue($search, Schema::getJoins($schema['properties']));
        $parser = new \Glpi\Api\HL\RSQL\Parser($search);
        $this->string((string)$parser->parse($tokens))->isEqualTo($expected);
    }
}
