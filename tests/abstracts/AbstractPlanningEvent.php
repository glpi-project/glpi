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

    public function beforeTestMethod($method)
    {
        parent::beforeTestMethod($method);

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

        $this->integer((int) $id)->isGreaterThan(0);
        $this->boolean($event->getFromDB($id))->isTrue();

       // check end date
        if (isset($event->fields['end'])) {
            $this->string($event->fields['end'])->isEqualTo($this->end);
        }

       // check rrule encoding
        $exp_exdates = '"exceptions":["' . $this->exdate1 . '","' . $this->exdate2 . '"]';
        $this->string($event->fields['rrule'])
           ->isEqualTo('{"freq":"daily","interval":1,"byweekday":"MO","bymonth":1,' . $exp_exdates . '}');

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
        $this->boolean($event->update($update))->isTrue();

       // check dates (we added duration to both dates on update)
        if (isset($event->fields['begin'])) {
            $this->string($event->fields['begin'])
            ->isEqualTo($new_begin);
        }
        if (isset($event->fields['end'])) {
            $this->string($event->fields['end'])
            ->isEqualTo($new_end);
        }

       // check rrule encoding
        $this->string($event->fields['rrule'])
           ->isEqualTo('{"freq":"monthly","interval":2,"byweekday":"TU","bymonth":2}');
    }


    public function testDelete()
    {
        $this->login();

        $event = new $this->myclass();
        $id    = $event->add($this->input);
        $this->integer((int)$id)->isGreaterThan(0);

        $this->boolean($event->delete([
            'id' => $id,
        ]))->isTrue();
        $this->boolean($event->getFromDB($id))->isFalse();
    }
}
