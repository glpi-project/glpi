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

namespace tests\units\Glpi\ContentTemplates\Parameters;

use Glpi\ContentTemplates\Parameters\UserParameters;

include_once __DIR__ . '/../../../../abstracts/AbstractParameters.php';

class UserParametersTest extends AbstractParameters
{
    public function testGetValues(): void
    {
        $this->createItem('UserTitle', ['name' => 'test title']);
        $this->createItem('UserCategory', ['name' => 'test category']);
        $this->createItem('Location', ['name' => 'test location']);

        $test_entity_id    = getItemByTypeName('Entity', '_test_child_2', true);
        $test_user_id      = getItemByTypeName('User', TU_USER, true);
        $test_usertitle    = getItemByTypeName('UserTitle', 'test title', true);
        $test_usercategory = getItemByTypeName('UserCategory', 'test category', true);
        $test_location     = getItemByTypeName('Location', 'test location', true);

        $this->createItem('User', [
            'name'                => 'user_testGetValues',
            'entities_id'         => $test_entity_id,
            'firstname'           => 'firstname',
            'realname'            => 'lastname',
            '_useremails'         => ['test@email.com'],
            'phone'               => '0101010101',
            'phone2'              => '0202020202',
            'mobile'              => '0303030303',
            'users_id_supervisor' => $test_user_id,
            'usertitles_id'       => $test_usertitle,
            'usercategories_id'   => $test_usercategory,
            'locations_id'        => $test_location,
        ]);

        $parameters = new UserParameters();
        $values = $parameters->getValues(getItemByTypeName('User', 'user_testGetValues'));

        $this->assertEquals(
            [
                'id'          => getItemByTypeName('User', 'user_testGetValues', true),
                'login'       => 'user_testGetValues',
                'fullname'    => 'lastname firstname',
                'email'       => 'test@email.com',
                'phone'       => '0101010101',
                'phone2'      => '0202020202',
                'mobile'      => '0303030303',
                'firstname'   => 'firstname',
                'realname'    => 'lastname',
                'responsible' => TU_USER,
                'location' => [
                    'id'           => $test_location,
                    'name'         => 'test location',
                    'completename' => 'test location',
                ],
                'usertitle'  => [
                    'id'   => $test_usertitle,
                    'name' => 'test title',
                ],
                'usercategory'  => [
                    'id'   => $test_usercategory,
                    'name' => 'test category',
                ],
                'used_items'  => [],
            ],
            $values
        );

        $this->testGetAvailableParameters($values, $parameters->getAvailableParameters());
    }
}
