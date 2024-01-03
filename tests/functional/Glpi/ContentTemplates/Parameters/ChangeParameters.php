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

namespace tests\units\Glpi\ContentTemplates\Parameters;

class ChangeParameters extends AbstractParameters
{
    public function testGetValues(): void
    {
        $this->login();
        $test_entity_id = getItemByTypeName('Entity', '_test_child_2', true);

        $this->createItem('ITILCategory', [
            'name' => 'category_testGetValues'
        ]);

        $itilcategories_id = getItemByTypeName('ITILCategory', 'category_testGetValues', true);

        $observer_groups_id = getItemByTypeName('Group', '_test_group_1', true);

        $this->createItem('Change', [
            'name'                => 'change_testGetValues',
            'content'             => '<p>change_testGetValues content</p>',
            'entities_id'         => $test_entity_id,
            'date'                => '2021-07-19 17:11:28',
            'itilcategories_id'   => $itilcategories_id,
            '_groups_id_observer' => [$observer_groups_id],
        ]);

        $changes_id = getItemByTypeName('Change', 'change_testGetValues', true);

        $parameters = $this->newTestedInstance();
        $values = $parameters->getValues(getItemByTypeName('Change', 'change_testGetValues'));
        $this->array($values)->isEqualTo([
            'id'        => $changes_id,
            'ref'       => "#$changes_id",
            'link'      => "<a  href='/glpi/front/change.form.php?id=$changes_id'  title=\"change_testGetValues\">change_testGetValues</a>",
            'name'      => 'change_testGetValues',
            'content'   => '<p>change_testGetValues content</p>',
            'date'      => '2021-07-19 17:11:28',
            'solvedate' => null,
            'closedate' => null,
            'status'    => 'New',
            'urgency'   => 'Medium',
            'impact'    => 'Medium',
            'priority'  => 'Medium',
            'entity' => [
                'id'           => $test_entity_id,
                'name'         => '_test_child_2',
                'completename' => 'Root entity > _test_root_entity > _test_child_2',
            ],
            'itilcategory' => [
                'id'           => $itilcategories_id,
                'name'         => 'category_testGetValues',
                'completename' => 'category_testGetValues',
            ],
            'requesters' => [
                'users'  => [],
                'groups' => [],
            ],
            'observers' => [
                'users'  => [],
                'groups' => [
                    [
                        'id'           => $observer_groups_id,
                        'name'         => '_test_group_1',
                        'completename' => '_test_group_1',
                    ],
                ],
            ],
            'assignees' => [
                'users'     => [],
                'groups'    => [],
                'suppliers' => [],
            ],
        ]);

        $this->testGetAvailableParameters($values, $parameters->getAvailableParameters());
    }
}
