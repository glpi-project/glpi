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

namespace tests\units\Glpi\Dashboard;

use DbTestCase;
use Group;
use SLA;
use Slm;
use Ticket;

/* Test for inc/dashboard/widget.class.php */

class Provider extends DbTestCase
{
    public function testNbTicketsByAgreementStatusAndTechnician()
    {
        global $DB;

       // Prepare context
        $slm = new Slm();
        $slm->add([
            'name' => 'SLM',
        ]);

        $slaTto = new SLA();
        $slaTto->add([
            'name' => 'sla tto',
            'type' => '1', // TTO
            'number_time' => 4,
            'definition_time' => 'hour',
            'slms_id' => $slm->getID(),
        ]);

        $slaTtr = new SLA();
        $slaTtr->add([
            'name' => 'sla ttr',
            'type' => '0', // TTR
            'number_time' => 4,
            'definition_time' => 'hour',
            'slms_id' => $slm->getID(),
        ]);

        $ticket = new Ticket();
        $ticket->add([
            'name' => "test dashboard card SLA / tech",
            'content' => 'foo',
            '_users_id_assign' => 2, // glpi
            'sla_id_tto'       => $slaTto->getID(),
            'sla_id_ttr'       => $slaTtr->getID(),
            'status'           => Ticket::ASSIGNED,
        ]);
        $this->boolean($ticket->isNewItem())->isFalse();

        $output = \Glpi\Dashboard\Provider::nbTicketsByAgreementStatusAndTechnician();
        $this->array($output)
         ->hasKeys([
             'label',
             'data',
             'icon'
         ]);
        $this->array($output['data'])
         ->hasKeys([
             'series',
             'labels',
         ]);

       // 1 label: the user glpi
        $this->integer(count($output['data']['labels']))->isEqualTo(1);
        $this->string($output['data']['labels'][0])->isEqualTo('glpi');

        $this->array($output['data']['series']);
        $this->integer(count($output['data']['series']))->isEqualTo(4);

       // labels of series
        $this->string($output['data']['series'][0]['name'])->isEqualTo('Late own and resolve');
        $this->string($output['data']['series'][1]['name'])->isEqualTo('Late resolve');
        $this->string($output['data']['series'][2]['name'])->isEqualTo('Late own');
        $this->string($output['data']['series'][3]['name'])->isEqualTo('On time');

        $this->integer($output['data']['series'][0]['data'][0])->isEqualTo(0);
        $this->integer($output['data']['series'][1]['data'][0])->isEqualTo(0);
        $this->integer($output['data']['series'][2]['data'][0])->isEqualTo(0);
        $this->integer($output['data']['series'][3]['data'][0])->isEqualTo(1);

        $DB->update(
            $ticket::getTable(),
            [
                'date'                       => '2021-01-01 00:00',
                'time_to_own'                => '2021-01-01 01:00',
                'takeintoaccount_delay_stat' => 50000,
            ],
            [
                'id' => $ticket->getID()
            ]
        );
        $ticket->getFromDB($ticket->getID());

        $output = \Glpi\Dashboard\Provider::nbTicketsByAgreementStatusAndTechnician();
        $this->integer($output['data']['series'][0]['data'][0])->isEqualTo(0);
        $this->integer($output['data']['series'][1]['data'][0])->isEqualTo(0);
        $this->integer($output['data']['series'][2]['data'][0])->isEqualTo(1);
        $this->integer($output['data']['series'][3]['data'][0])->isEqualTo(0);

        $DB->update(
            $ticket::getTable(),
            [
                'date'                       => '2021-01-01 00:00',
                'time_to_own'                => '2021-01-01 01:00',
                'takeintoaccount_delay_stat' => 60,
                'solvedate'                  => '2021-02-01 00:00',
                'time_to_resolve'            => '2021-01-02 00:00'
            ],
            [
                'id' => $ticket->getID()
            ]
        );
        $ticket->getFromDB($ticket->getID());
        $output = \Glpi\Dashboard\Provider::nbTicketsByAgreementStatusAndTechnician();
        $this->integer($output['data']['series'][0]['data'][0])->isEqualTo(0);
        $this->integer($output['data']['series'][1]['data'][0])->isEqualTo(1);
        $this->integer($output['data']['series'][2]['data'][0])->isEqualTo(0);
        $this->integer($output['data']['series'][3]['data'][0])->isEqualTo(0);

        $DB->update(
            $ticket::getTable(),
            [
                'date'                       => '2021-01-01 00:00',
                'time_to_own'                => '2021-01-01 01:00',
                'takeintoaccount_delay_stat' => 50000,
                'solvedate'                  => '2021-02-01 00:00',
                'time_to_resolve'            => '2021-01-02 00:00'
            ],
            [
                'id' => $ticket->getID()
            ]
        );
        $ticket->getFromDB($ticket->getID());
        $output = \Glpi\Dashboard\Provider::nbTicketsByAgreementStatusAndTechnician();
        $this->integer($output['data']['series'][0]['data'][0])->isEqualTo(1);
        $this->integer($output['data']['series'][1]['data'][0])->isEqualTo(0);
        $this->integer($output['data']['series'][2]['data'][0])->isEqualTo(0);
        $this->integer($output['data']['series'][3]['data'][0])->isEqualTo(0);
    }


    public function testNbTicketsByAgreementStatusAndTechnicianGroup()
    {
        global $DB;

       // Prepare context
        $slm = new Slm();
        $slm->add([
            'name' => 'SLM',
        ]);

        $slaTto = new SLA();
        $slaTto->add([
            'name' => 'sla tto',
            'type' => '1', // TTO
            'number_time' => 4,
            'definition_time' => 'hour',
            'slms_id' => $slm->getID(),
        ]);

        $slaTtr = new SLA();
        $slaTtr->add([
            'name' => 'sla ttr',
            'type' => '0', // TTR
            'number_time' => 4,
            'definition_time' => 'hour',
            'slms_id' => $slm->getID(),
        ]);

        $group = new Group();
        $group->add([
            'entities_id' => 0,
            'name'        => 'group sla test',
            'level'       => 1,
            'groups_id'   => 0,
        ]);
        $this->boolean($group->isNewItem())->isFalse();

        $ticket = new Ticket();
        $ticket->add([
            'name' => "test dashboard card SLA / tech",
            'content' => 'foo',
            '_groups_id_assign' => $group->getID(),
            'sla_id_tto'       => $slaTto->getID(),
            'sla_id_ttr'       => $slaTtr->getID(),
            'status'           => Ticket::ASSIGNED,
        ]);
        $this->boolean($ticket->isNewItem())->isFalse();

        $output = \Glpi\Dashboard\Provider::nbTicketsByAgreementStatusAndTechnicianGroup();
        $this->array($output)
         ->hasKeys([
             'label',
             'data',
             'icon'
         ]);
        $this->array($output['data'])
         ->hasKeys([
             'series',
             'labels',
         ]);

       // 1 label: the user glpi
        $this->integer(count($output['data']['labels']))->isEqualTo(1);
        $this->string($output['data']['labels'][0])->isEqualTo('group sla test');

        $this->array($output['data']['series']);
        $this->integer(count($output['data']['series']))->isEqualTo(4);

       // labels of series
        $this->string($output['data']['series'][0]['name'])->isEqualTo('Late own and resolve');
        $this->string($output['data']['series'][1]['name'])->isEqualTo('Late resolve');
        $this->string($output['data']['series'][2]['name'])->isEqualTo('Late own');
        $this->string($output['data']['series'][3]['name'])->isEqualTo('On time');

        $this->integer($output['data']['series'][0]['data'][0])->isEqualTo(0);
        $this->integer($output['data']['series'][1]['data'][0])->isEqualTo(0);
        $this->integer($output['data']['series'][2]['data'][0])->isEqualTo(0);
        $this->integer($output['data']['series'][3]['data'][0])->isEqualTo(1);

        $DB->update(
            $ticket::getTable(),
            [
                'date'                       => '2021-01-01 00:00',
                'time_to_own'                => '2021-01-01 01:00',
                'takeintoaccount_delay_stat' => 50000,
            ],
            [
                'id' => $ticket->getID()
            ]
        );
        $ticket->getFromDB($ticket->getID());

        $output = \Glpi\Dashboard\Provider::nbTicketsByAgreementStatusAndTechnicianGroup();
        $this->integer($output['data']['series'][0]['data'][0])->isEqualTo(0);
        $this->integer($output['data']['series'][1]['data'][0])->isEqualTo(0);
        $this->integer($output['data']['series'][2]['data'][0])->isEqualTo(1);
        $this->integer($output['data']['series'][3]['data'][0])->isEqualTo(0);

        $DB->update(
            $ticket::getTable(),
            [
                'date'                       => '2021-01-01 00:00',
                'time_to_own'                => '2021-01-01 01:00',
                'takeintoaccount_delay_stat' => 60,
                'solvedate'                  => '2021-02-01 00:00',
                'time_to_resolve'            => '2021-01-02 00:00'
            ],
            [
                'id' => $ticket->getID()
            ]
        );
        $ticket->getFromDB($ticket->getID());
        $output = \Glpi\Dashboard\Provider::nbTicketsByAgreementStatusAndTechnicianGroup();
        $this->integer($output['data']['series'][0]['data'][0])->isEqualTo(0);
        $this->integer($output['data']['series'][1]['data'][0])->isEqualTo(1);
        $this->integer($output['data']['series'][2]['data'][0])->isEqualTo(0);
        $this->integer($output['data']['series'][3]['data'][0])->isEqualTo(0);

        $DB->update(
            $ticket::getTable(),
            [
                'date'                       => '2021-01-01 00:00',
                'time_to_own'                => '2021-01-01 01:00',
                'takeintoaccount_delay_stat' => 50000,
                'solvedate'                  => '2021-02-01 00:00',
                'time_to_resolve'            => '2021-01-02 00:00'
            ],
            [
                'id' => $ticket->getID()
            ]
        );
        $ticket->getFromDB($ticket->getID());
        $output = \Glpi\Dashboard\Provider::nbTicketsByAgreementStatusAndTechnicianGroup();
        $this->integer($output['data']['series'][0]['data'][0])->isEqualTo(1);
        $this->integer($output['data']['series'][1]['data'][0])->isEqualTo(0);
        $this->integer($output['data']['series'][2]['data'][0])->isEqualTo(0);
        $this->integer($output['data']['series'][3]['data'][0])->isEqualTo(0);
    }
}
