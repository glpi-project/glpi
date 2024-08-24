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

namespace tests\units;

include_once __DIR__ . '/../abstracts/AbstractPlanningEvent.php';

class PlanningExternalEventTest extends \AbstractPlanningEvent
{
    public $myclass = "\PlanningExternalEvent";


    public function testAddInstanceException()
    {
        $this->login();

        $event     = new $this->myclass();
        $id        = $event->add($this->input);
        $exception = date('Y-m-d', $this->now + DAY_TIMESTAMP);

        $this->assertTrue($event->addInstanceException($id, $exception));

        $rrule = json_decode($event->fields['rrule'], true);
        // original event has 2 exceptions, we add one
        $this->assertCount(3, $rrule['exceptions']);
        $this->assertContains($exception, $rrule['exceptions']);
    }


    public function testCreateInstanceClone()
    {
        $this->login();

        $event     = new $this->myclass();
        $serie_id  = $event->add($this->input);
        $start     = date('Y-m-d H:i:s', $this->now + DAY_TIMESTAMP);
        $start_day = date('Y-m-d', $this->now + DAY_TIMESTAMP);

        // the clone of series should not have rrule
        $new_event = $event->createInstanceClone($serie_id, $start);
        $this->assertInstanceOf($this->myclass, $new_event);
        $this->assertNotEquals($serie_id, $new_event->fields['id']);
        $this->assertNull($new_event->fields['rrule']);

        // original event should have the instance exception
        $rrule = json_decode($event->fields['rrule'], true);
        // original event has 2 exceptions, we add one
        $this->assertCount(3, $rrule['exceptions']);
        $this->assertContains($start_day, $rrule['exceptions']);
    }
}
