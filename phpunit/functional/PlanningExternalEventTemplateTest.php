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

namespace tests\units;

include_once __DIR__ . '/../abstracts/AbstractPlanningEvent.php';

class PlanningExternalEventTemplateTest extends \AbstractPlanningEvent
{
    protected $myclass = "\PlanningExternalEventTemplate";

    public function setUp(): void
    {
        parent::setUp();

        $this->input = array_merge($this->input, [
            '_planningrecall' => [
                'before_time' => 2 * \HOUR_TIMESTAMP,
            ],
        ]);
    }

    public function testAdd()
    {
        $event = parent::testAdd();

        $this->assertEquals(
            2 * \HOUR_TIMESTAMP,
            (int) $event->fields['before_time']
        );
        $this->assertEquals(
            2 * \HOUR_TIMESTAMP,
            (int) $event->fields['duration']
        );
    }
}
