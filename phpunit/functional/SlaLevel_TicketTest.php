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

use CommonITILObject;
use DbTestCase;

class SlaLevel_TicketTest extends DbTestCase
{
    /**
     * Create a SLM SLA TTO
     * to update ticket itilcategories_id if ticket is linked to a specific project
     * when time to own is exceeded
     */
    public function testProjectCriteria()
    {
        $this->login();

        //create project
        $project = new \Project();
        $project_input = ['name' => 'Project'];
        $project_id = $project->add(['name' => 'Project']);
        $this->checkInput($project, $project_id, $project_input);

        //create a ticket category
        $category    = new \ITILCategory();
        $category_id = $category->add([
            'name'                        => 'my itil category',
            'is_incident'                 => true,
            'is_request'                  => true,
        ]);
        $this->assertFalse($category->isNewItem());

        $slm    = new \SLM();
        $slm_id = $slm->add($slm_in = [
            'name'         => __METHOD__,
            'comment'      => $this->getUniqueString(),
            'calendars_id' => 0, //24:24 7j/7j
        ]);
        $this->checkInput($slm, $slm_id, $slm_in);

        // prepare sla/ola inputs
        $sla1_in = [
            'slms_id'         => $slm_id,
            'name'            => "SLA TTO",
            'comment'         => $this->getUniqueString(),
            'type'            => \SLM::TTO,
            'number_time'     => 1,
            'definition_time' => 'hour',
        ];

        // add sla (TTO)
        $sla    = new \SLA();
        $sla1_id = $sla->add($sla1_in);
        $this->checkInput($sla, $sla1_id, $sla1_in);

        // prepare levels input for sla
        $slal1_in = [
            'name'           => __METHOD__,
            'execution_time' => 0, //TIME TO OWN
            'is_active'      => 1,
            'match'          => 'AND',
            'slas_id'        => $sla1_id
        ];

        // add levels
        $slal = new \SlaLevel();
        $slal1_id = $slal->add($slal1_in);
        $this->checkInput($slal, $slal1_id, $slal1_in);


        // add criteria/actions
        $scrit_in = [
            'slalevels_id' => $slal1_id,
            'criteria'     => 'assign_project',
            'condition'    => 0, //is
            'pattern'      => $project_id
        ];


        $saction_in = [
            'slalevels_id' => $slal1_id,
            'action_type'  => 'assign',
            'field'        => 'itilcategories_id',
            'value'        => $category_id
        ];

        $scrit    = new \SlaLevelCriteria();
        $saction  = new \SlaLevelAction();

        $scrit_id   = $scrit->add($scrit_in);
        $saction_id = $saction->add($saction_in);

        $this->checkInput($scrit, $scrit_id, $scrit_in);
        $this->checkInput($saction, $saction_id, $saction_in);

        // test create ticket
        $ticket = new \Ticket();
        //$start_date = date("Y-m-d H:i:s", time() - 2 * HOUR_TIMESTAMP);
        $tickets_id = $ticket->add($ticket_input = [
            //'date'    => $start_date,
            'name'    => __METHOD__,
            'content' => __METHOD__,
            'slas_id_tto' => $sla1_id,
        ]);
        $this->checkInput($ticket, $tickets_id, $ticket_input);
        $this->assertEquals($sla1_id, (int)$ticket->getField('slas_id_tto'));
        $this->assertEquals(0, (int)$ticket->getField('itilcategories_id'));
        $this->assertEquals(\CommonITILObject::INCOMING, (int)$ticket->getField('status'));

        //add Project to ticket
        $itil_project = new \Itil_Project();
        $itil_project_input = ['itemtype' => 'Ticket', 'items_id' => $tickets_id, 'projects_id' => $project_id];
        $itil_project_id = $itil_project->add($itil_project_input);
        $this->checkInput($itil_project, $itil_project_id, $itil_project_input);

        //get SlaLevel_Ticket related to this ticket and SLM
        $slalevels_tickets = new \SlaLevel_Ticket();
        $this->assertTrue($slalevels_tickets->getFromDBByCrit([
            'tickets_id' => $tickets_id, 'slalevels_id' => $slal1_id
        ]));

        //fake glpi_slalevels_tickets.date to run crontask
        $this->assertTrue($slalevels_tickets->update([
            'id' => $slalevels_tickets->fields['id'], 'date' => date("Y-m-d H:i:s", time() - 2 * HOUR_TIMESTAMP)
        ]));

        //run automatic action
        //run crontask
        $task = new \CronTask();
        $this->assertSame(1, \SlaLevel_Ticket::cronSlaTicket($task));

        //check ticket category change
        //reload ticket
        $this->assertTrue($ticket->getFromDB($tickets_id));
        $this->assertEquals($category_id, $ticket->fields['itilcategories_id']);
    }
}
