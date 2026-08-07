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

use Contact;
use Glpi\Tests\DbTestCase;
use ProjectTaskTeamDropdown;

final class ProjectTaskTeamDropdownTest extends DbTestCase
{
    public function testFetchValuesOnlyReturnsRecursiveContactsFromParentEntity(): void
    {
        $this->login();
        $root_entity_id = $this->getTestRootEntity(only_id: true);
        $unique = uniqid('fetchvalues_contact_');

        $this->createItem(Contact::class, [
            'name'         => $unique . '_recursive',
            'entities_id'  => $root_entity_id,
            'is_recursive' => 1,
        ]);
        $this->createItem(Contact::class, [
            'name'         => $unique . '_not_recursive',
            'entities_id'  => $root_entity_id,
            'is_recursive' => 0,
        ]);

        $this->setEntity('_test_child_1', false);

        $values = ProjectTaskTeamDropdown::fetchValues($unique);
        $text_values = [];
        foreach ($values['results'] as $group) {
            foreach ($group['children'] as $item) {
                $text_values[] = $item['text'];
            }
        }

        $this->assertContains($unique . '_recursive', $text_values);
        $this->assertNotContains($unique . '_not_recursive', $text_values);
    }
}
