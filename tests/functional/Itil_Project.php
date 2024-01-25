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

use DbTestCase;

/* Test for inc/itil_project.class.php */

class Itil_Project extends DbTestCase
{
    /**
     * Test ability to link ITIL items to a project.
     *
     * @return void
     */
    public function testLink()
    {

        $this->login();

        $project = new \Project();
        $itil_project = new \Itil_Project();

        $this->integer(
            (int)$project->add([
                'name' => 'Some project',
            ])
        )->isGreaterThan(0);
        $baseProjectId = $project->fields['id'];

        $items = [];
        foreach ([\Change::class, \Problem::class, \Ticket::class] as $itemtype) {
            $item = new $itemtype();

            $this->integer(
                (int)$item->add([
                    'name'    => 'ITIL item ' . $itemtype,
                    'content' => 'ITIL item ' . $itemtype,
                ])
            )->isGreaterThan(0);
            $items[] = $item;

           // Item should be linkable to a project
            $this->integer(
                (int)$itil_project->add([
                    'itemtype'    => $itemtype,
                    'items_id'    => $item->fields['id'],
                    'projects_id' => $baseProjectId,
                ])
            )->isGreaterThan(0);

           // Count displayed in tab name should be equal to count of ITIL items linked to project
            $this->integer(
                (int)preg_replace('/[^\d]*(\d+)[^\d]*/', '$1', $itil_project->getTabNameForItem($project))
            )->isEqualTo(count($items));
        }

       //add a task
        $ptask = new \ProjectTask();
        $ptid = (int)$ptask->add([
            'name' => 'Task for test project Clone',
            'projects_id' => $baseProjectId
        ]);
        $this->integer($ptid)->isGreaterThan(0);

       // Clone project should clone its links to ITIL items and task
        $cloneProjectId = (int)$project->add([
            'name'   => 'Some project clone',
            '_oldID' => $baseProjectId,
        ]);
        $this->integer($cloneProjectId)->isGreaterThan(0);

        $this->integer($cloneProjectId)->isNotEqualTo($baseProjectId, 'Project has not been cloned (same id)!');

        $this->integer(
            countElementsInTable($ptask::getTable(), ['projects_id' => $baseProjectId])
        )->isEqualTo(1);

        $this->integer(
            countElementsInTable($ptask::getTable(), ['projects_id' => $cloneProjectId])
        )->isEqualTo(1);

        $this->integer(
            countElementsInTable($itil_project::getTable(), ['projects_id' => $cloneProjectId])
        )->isEqualTo(count($items));

       // Deletion of project should delete links with ITIL items
        $this->boolean($project->delete(['id' => $baseProjectId], true))->isTrue();

        $this->integer(
            countElementsInTable($itil_project::getTable(), ['projects_id' => $baseProjectId])
        )->isEqualTo(0);

       // Deletion of ITIL items should delete links with project
        foreach ($items as $item) {
            $itemtype = $item->getType();
            $items_id = $item->fields['id'];
            $this->boolean((new $itemtype())->delete(['id' => $items_id], true))->isTrue();

            $this->integer(
                countElementsInTable(
                    $itil_project::getTable(),
                    [
                        'itemtype' => $itemtype,
                        'items_id' => $items_id,
                    ]
                )
            )->isEqualTo(0);
        }
    }
}
