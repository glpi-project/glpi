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

class ProjectTask_TicketTest extends DbTestCase
{
    public function testDuplicateLinkPrevention(): void
    {
        $this->login();

        $project = $this->createItem('Project', [
            'name' => 'Test Project',
        ]);

        $task = $this->createItem('ProjectTask', [
            'name' => 'Test Task',
            'projects_id' => $project->getID(),
        ]);

        $ticket = $this->createItem('Ticket', [
            'name' => 'Test Ticket',
            'content' => 'Test content',
        ]);

        $link = new \ProjectTask_Ticket();
        $link_id = $link->add([
            'projecttasks_id' => $task->getID(),
            'tickets_id' => $ticket->getID(),
        ]);
        $this->assertGreaterThan(0, $link_id);

        $this->assertFalse(
            $link->add([
                'projecttasks_id' => $task->getID(),
                'tickets_id' => $ticket->getID(),
            ])
        );
        $this->hasSessionMessages(ERROR, ['Relation already exists.']);
    }
}
