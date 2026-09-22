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

use Glpi\Tests\DbTestCase;

/* Test for inc/group.class.php */

class GroupTest extends DbTestCase
{
    public function testGetGroupsAncestorsIdsCacheIsInvalidatedOnParentChange(): void
    {
        $this->login();

        $group_a = $this->createItem("Group", ['name' => 'Ancestors cache group A']);
        $group_b = $this->createItem("Group", ['name' => 'Ancestors cache group B']);
        $child = $this->createItem("Group", [
            'name'      => 'Ancestors cache child',
            'groups_id' => $group_a->getID(),
        ]);

        $ancestors = \Group::getGroupsAncestorsIds([$child->getID()]);
        $this->assertContains($group_a->getID(), $ancestors);
        $this->assertNotContains($group_b->getID(), $ancestors);

        // Move the child under group B: a stale cache would keep returning group A
        $this->updateItem("Group", $child->getID(), ['groups_id' => $group_b->getID()]);

        $ancestors = \Group::getGroupsAncestorsIds([$child->getID()]);
        $this->assertContains($group_b->getID(), $ancestors);
        $this->assertNotContains($group_a->getID(), $ancestors);
    }
}
