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

abstract class AbstractPlanningEvent extends \DbTestCase
{
    protected $myclass = "";
    protected $input   = [];

    protected $now       = "";
    protected $begin     = "";
    protected $end       = "";
    protected $duration  = "";
    protected $exdate1   = "";
    protected $exdate2   = "";

    public function setUp(): void
    {
        parent::setUp();

        $this->now      = time();
        $this->duration = 2 * \HOUR_TIMESTAMP;
        $this->begin    = date('Y-m-d H:i:s', $this->now);
        $this->end      = date('Y-m-d H:i:s', $this->now + $this->duration);
        $this->exdate1  = date('Y-m-d', $this->now + (2 * \DAY_TIMESTAMP));
        $this->exdate2  = date('Y-m-d', $this->now + (3 * \DAY_TIMESTAMP));

        $this->input = [
            'name'       => 'test add external event',
            'test'       => 'comment for external event',
            'plan'       => [
                'begin'     => $this->begin,
                '_duration' => $this->duration,
            ],
            'rrule'      => [
                'freq'      => 'daily',
                'interval'  => 1,
                'byweekday' => 'MO',
                'bymonth'   => 1,
                'exceptions' => "$this->exdate1, $this->exdate2"
            ],
            'state'      => \Planning::TODO,
            'background' => 1,
        ];
    }

    public function testAdd()
    {
        $this->login();

        $event = new $this->myclass();
        $id    = $event->add($this->input);

        $this->assertGreaterThan(0, (int)$id);
        $this->assertTrue($event->getFromDB($id));

       // check end date
        if (isset($event->fields['end'])) {
            $this->assertEquals($this->end, $event->fields['end']);
        }

        // check rrule encoding
        $exp_exdates = '"exceptions":["' . $this->exdate1 . '","' . $this->exdate2 . '"]';
        $this->assertEquals(
            '{"freq":"daily","interval":1,"byweekday":"MO","bymonth":1,' . $exp_exdates . '}',
            $event->fields['rrule']
        );

        return $event;
    }


    public function testUpdate()
    {
        $this->login();

        $event = new $this->myclass();
        $id    = $event->add($this->input);

        $new_begin = date("Y-m-d H:i:s", strtotime($this->begin) + $this->duration);
        $new_end   = date("Y-m-d H:i:s", strtotime($this->end) + $this->duration);

        $update = array_merge($this->input, [
            'id'         => $id,
            'name'       => 'updated external event',
            'test'       => 'updated comment for external event',
            'plan'       => [
                'begin'     => $new_begin,
                '_duration' => $this->duration,
            ],
            'rrule'      => [
                'freq'      => 'monthly',
                'interval'  => 2,
                'byweekday' => 'TU',
                'bymonth'   => 2,
            ],
            'state'      => \Planning::INFO,
            'background' => 0,
        ]);
        $this->assertTrue($event->update($update));

        // check dates (we added duration to both dates on update)
        if (isset($event->fields['begin'])) {
            $this->assertEquals($new_begin, $event->fields['begin']);
        }
        if (isset($event->fields['end'])) {
            $this->assertEquals($new_end, $event->fields['end']);
        }

        // check rrule encoding
        $this->assertEquals(
            '{"freq":"monthly","interval":2,"byweekday":"TU","bymonth":2}',
            $event->fields['rrule']
        );
    }


    public function testDelete()
    {
        $this->login();

        $event = new $this->myclass();
        $id    = $event->add($this->input);
        $this->assertGreaterThan(0, (int)$id);

        $this->assertTrue($event->delete(['id' => $id]));
        $this->assertFalse($event->getFromDB($id));
    }
}
