<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

use Glpi\Tests\CommonITILRecurrentTest;

class TicketRecurrentTest extends CommonITILRecurrentTest
{
    protected function getChildClass()
    {
        return \TicketRecurrent::class;
    }

    public function testTicketPerItemUsesTheSameOpeningDate(): void
    {
        global $DB;

        $this->login();

        $template = $this->createItem(\TicketTemplate::class, [
            'name' => $this->getUniqueString(),
        ]);
        $recurrent = $this->createItem(\TicketRecurrent::class, [
            'name'               => $this->getUniqueString(),
            'tickettemplates_id' => $template->getID(),
            'begin_date'         => '2020-01-01 00:00:00',
            'end_date'           => null,
            'periodicity'        => DAY_TIMESTAMP,
            'create_before'      => 0,
            'calendars_id'       => 0,
            'is_active'          => 1,
            'ticket_per_item'    => 1,
            'entities_id'        => $this->getTestRootEntity(true),
        ]);
        foreach ([1, 2] as $i) {
            $computer = $this->createItem(\Computer::class, [
                'name'        => $this->getUniqueString(),
                'entities_id' => $this->getTestRootEntity(true),
            ]);
            $this->createItem(\Item_TicketRecurrent::class, [
                'ticketrecurrents_id' => $recurrent->getID(),
                'itemtype'            => \Computer::class,
                'items_id'            => $computer->getID(),
            ]);
        }

        $DB->update(\TicketRecurrent::getTable(), ['next_creation_date' => '2020-01-01 00:00:00'], ['id' => $recurrent->getID()]);

        \CommonITILRecurrentCron::cronRecurrentItems($this->createMock(\CronTask::class));

        $dates = array_column(iterator_to_array($DB->request([
            'SELECT' => 'date',
            'FROM'   => \Ticket::getTable(),
            'WHERE'  => ['tickettemplates_id' => $template->getID()],
        ])), 'date');
        $this->assertSame(['2020-01-01 00:00:00', '2020-01-01 00:00:00'], $dates);
    }
}
