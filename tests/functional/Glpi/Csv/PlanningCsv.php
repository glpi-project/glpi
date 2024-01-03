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

namespace tests\units\Glpi\Csv;

use CsvTestCase;

/* Test for inc/planningcsv.class.php */

class PlanningCsv extends CsvTestCase
{
    public function getTestData(): array
    {
        $this->login();

       //create calendar entryies
        $reminder = new \Reminder();
        $begin = new \DateTime();
        $begin->sub(new \DateInterval('P10D'));
        $fbegin = $begin->format('Y-m-d H:i:s');
        $end = new \DateTime();
        $end->add(new \DateInterval('P5D'));
        $fend = $end->format('Y-m-d H:i:s');
        $rid = (int)$reminder->add([
            'name'            => 'This is a "test"',
            'is_planned'      => 1,
            'begin_view_date' => $fbegin,
            'end_view_date'   => $fend,
            'plan'            => [
                'begin'           => $fbegin,
                'end'             => $fend
            ]
        ]);
        $this->integer($rid)->isGreaterThan(0);

        $ticket = new \Ticket();
        $tid = (int)$ticket->add([
            'name'         => 'ticket title',
            'description'  => 'a description',
            'content'      => '',
            'entities_id'  => getItemByTypeName('Entity', '_test_root_entity', true)
        ]);
        $this->integer($tid)->isGreaterThan(0);
        $this->boolean($ticket->isNewItem())->isFalse();

        $task = new \TicketTask();
        $tasksstates = [
            \Planning::TODO,
            \Planning::TODO,
            \Planning::INFO
        ];
        $date = new \DateTime();
        $date->sub(new \DateInterval('P6M'));
        $tasks = [];
        foreach ($tasksstates as $taskstate) {
            $edate = clone $date;
            $edate->add(new \DateInterval('P2D'));
            $input = [
                'content'         => sprintf('Task with "%s" state', $taskstate),
                'state'           => $taskstate,
                'tickets_id'      => $tid,
                'users_id_tech'   => \Session::getLoginUserID(),
                'begin'           => $date->format('Y-m-d H:i:s'),
                'end'             => $edate->format('Y-m-d H:i:s'),
                'actiontime'      => 172800
            ];
            $ttid = (int)$task->add($input);
            $this->integer($ttid)->isGreaterThan(0);
            $this->boolean($task->getFromDB($ttid))->isTrue();
            $input['id'] = $task->fields['id'];
            if ($taskstate !== \Planning::INFO) {
               //INFO are not present in planning
                $tasks[] = $input;
            }
            $date->add(new \DateInterval('P1Y'));
        }

        $user = new \User();
        $this->boolean($user->getFromDB(\Session::getLoginUserID()))->isTrue();

        $expected_header = [
            'Actor',
            'Title',
            'Item type',
            'Item id',
            'Begin date',
            'End date'
        ];

        $expected_content = [
            [
                'actor'     => $user->getFriendlyName(),
                'title'     => 'This is a "test"',
                'itemtype'  => 'Reminder',
                'items_id'  => $rid,
                'begindate' => $fbegin,
                'enddate'   => $fend
            ]
        ];

        foreach ($tasks as $input) {
            $expected_content[] = [
                'actor'     => $user->getFriendlyName(),
                'title'     => 'ticket title',
                'itemtype'  => 'Ticket task',
                'items_id'  => $input['id'],
                'begindate' => $input['begin'],
                'enddate'   => $input['end']
            ];
        }

        return [
            [
                'export' => new \Glpi\Csv\PlanningCsv(\Session::getLoginUserID(), 0),
                'expected' => [
                    'cols'     => 6,
                    'rows'     => 3,
                    'filename' => 'planning.csv',
                    'header'   => $expected_header,
                    'content'  => $expected_content,
                ]
            ],
            [
                'export' => new \Glpi\Csv\PlanningCsv(\Session::getLoginUserID(), 0, 'Reminder'),
                'expected' => [
                    'cols'     => 6,
                    'rows'     => 1,
                    'filename' => 'planning.csv',
                    'header'   => $expected_header,
                    'content'  => [$expected_content[0]],
                ]
            ]
        ];
    }
}
