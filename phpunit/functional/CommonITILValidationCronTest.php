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

use DbTestCase;

class CommonITILValidationCronTest extends DbTestCase
{
    public function testRun()
    {
        global $DB, $CFG_GLPI;

        $this->login();

        $CFG_GLPI['use_notifications']  = true;

        // update entity
        $entity = new \Entity();
        $this->assertTrue(
            $entity->update([
                'id' => 0,
                'approval_reminder_repeat_interval' => 1,
            ])
        );

        // create ticket
        $ticket = new \Ticket();
        $ticket_id = $ticket->add([
            'name' => 'Ticket',
            'content' => 'Ticket',
        ]);
        $this->assertGreaterThan(0, $ticket_id);

        // create ticket validation
        $ticket_validation = new \TicketValidation();
        $ticket_validation_id = $ticket_validation->add([
            'tickets_id'      => $ticket_id,
            'itemtype_target' => 'User',
            'items_id_target' => getItemByTypeName('User', TU_USER, true),
        ]);
        $this->assertGreaterThan(0, $ticket_validation_id);

        // backdate ticket validation
        $this->assertTrue(
            $DB->update(
                \TicketValidation::getTable(),
                [
                    'submission_date' => date('Y-m-d H:i:s', strtotime('-1 day')),
                ],
                [
                    'id' => $ticket_validation_id,
                ]
            )
        );

        // retrieve crontask
        $crontask = new \CronTask();
        $this->assertTrue($crontask->getFromDBbyName('CommonITILValidationCron', 'approvalreminder'));

        // run cron
        $this->assertEquals(1, \CommonITILValidationCron::cronApprovalReminder($crontask));

        // verify last reminder date is set
        $this->assertTrue($ticket_validation->getFromDB($ticket_validation_id));
        $this->assertNotEmpty($ticket_validation->fields['last_reminder_date']);

        // reset last reminder date
        $this->assertTrue(
            $DB->update(
                \TicketValidation::getTable(),
                [
                    'last_reminder_date' => null,
                ],
                [
                    'id' => $ticket_validation_id,
                ]
            )
        );

        // verify last reminder date is empty
        $this->assertTrue($ticket_validation->getFromDB($ticket_validation_id));
        $this->assertEmpty((string) $ticket_validation->fields['last_reminder_date']);

        // Solve ticket
        $this->assertTrue(
            $ticket->update([
                'id' => $ticket_id,
                'status' => \Ticket::SOLVED,
            ])
        );

        // run cron
        $this->assertEquals(1, \CommonITILValidationCron::cronApprovalReminder($crontask));

        // verify last reminder date is empty
        $this->assertTrue($ticket_validation->getFromDB($ticket_validation_id));
        $this->assertEmpty((string) $ticket_validation->fields['last_reminder_date']);

        // Close ticket
        $this->assertTrue(
            $ticket->update([
                'id' => $ticket_id,
                'status' => \Ticket::CLOSED,
            ])
        );

        // run cron
        $this->assertEquals(1, \CommonITILValidationCron::cronApprovalReminder($crontask));

        // verify last reminder date is empty
        $this->assertTrue($ticket_validation->getFromDB($ticket_validation_id));
        $this->assertEmpty((string) $ticket_validation->fields['last_reminder_date']);
    }
}
