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

namespace tests\units\Glpi\Search;

class SearchOption extends \GLPITestCase
{
    protected function searchOptionIdGeneratorProvider(): iterable
    {
        yield [
            'string_identifier' => 'any string can be used',
            'plugin'            => null,
            'generated_id'      => 12835,
        ];
        yield [
            'string_identifier' => 'any string can be used',
            'plugin'            => 'MyPlugin',
            'generated_id'      => 80255,
        ];
        yield [
            'string_identifier' => 'any string can be used',
            'plugin'            => 'AnotherPlugin',
            'generated_id'      => 30995,
        ];
    }

    /**
     * @dataProvider searchOptionIdGeneratorProvider
     */
    public function testGenerateAPropbablyUniqueId(
        string $string_identifier,
        ?string $plugin,
        int $generated_id
    ) {
        $result = \Glpi\Search\SearchOption::generateAPropbablyUniqueId($string_identifier, $plugin);
        $this->integer($result)->isEqualTo($generated_id);
    }

    public function testGenerateAPropbablyUniqueIdRange()
    {
        for ($i = 'a'; $i < 'aaa'; $i = str_increment($i)) {
            $core_result = \Glpi\Search\SearchOption::generateAPropbablyUniqueId($i, null);
            $this->integer($core_result)->isGreaterThanOrEqualTo(10000);
            $this->integer($core_result)->isLessThanOrEqualTo(19999);

            $plugin_result = \Glpi\Search\SearchOption::generateAPropbablyUniqueId($i, 'MyPlugin');
            $this->integer($plugin_result)->isGreaterThanOrEqualTo(20000);
            $this->integer($plugin_result)->isLessThanOrEqualTo(99999);
        }
    }
}
