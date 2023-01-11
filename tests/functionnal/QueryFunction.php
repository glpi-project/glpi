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

namespace tests\units;

class QueryFunction extends \GLPITestCase
{
    protected function addDateProvider()
    {
        return [
            ['`glpi_computers`.`date_mod`', '1', 'DAY', null, 'DATE_ADD(`glpi_computers`.`date_mod`, INTERVAL 1 DAY)'],
            ['`glpi_computers`.`date_mod`', '5-1', 'DAY', null, 'DATE_ADD(`glpi_computers`.`date_mod`, INTERVAL 5-1 DAY)'],
            ['`glpi_computers`.`date_mod`', '1', 'DAY', '`date_alias`', 'DATE_ADD(`glpi_computers`.`date_mod`, INTERVAL 1 DAY) AS `date_alias`'],
            ['`glpi_computers`.`date_mod`', '5', 'MONTH', null, 'DATE_ADD(`glpi_computers`.`date_mod`, INTERVAL 5 MONTH)'],
            ['`glpi_tickets`.`date_creation`', '`glpi_tickets`.`time_to_own`', 'SECOND', null, 'DATE_ADD(`glpi_tickets`.`date_creation`, INTERVAL `glpi_tickets`.`time_to_own` SECOND)'],
        ];
    }

    /**
     * @dataProvider addDateProvider
     */
    public function testAddDate($date, $interval, $interval_unit, $alias, $expected)
    {
        $this->string((string) \QueryFunction::addDate($date, $interval, $interval_unit, $alias))->isIdenticalTo($expected);
    }

    protected function concatProvider()
    {
        return [
            [["'A'", "'B'", "'C'"], null, "CONCAT('A', 'B', 'C')"],
            [["'A'", "'B'", "'C'"], '`concat_alias`', "CONCAT('A', 'B', 'C') AS `concat_alias`"],
            [["'A'", '`glpi_computers`.`name`', "'C'"], null, "CONCAT('A', `glpi_computers`.`name`, 'C')"],
            [["'A'", '`glpi_computers`.`name`', "'C'"], '`concat_alias`', "CONCAT('A', `glpi_computers`.`name`, 'C') AS `concat_alias`"],
        ];
    }

    /**
     * @dataProvider concatProvider
     */
    public function testConcat($params, $alias, $expected)
    {
        $this->string((string) \QueryFunction::concat($params, $alias))->isIdenticalTo($expected);
    }
}
