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

use Glpi\ContentTemplates\Parameters\AssetParameters;

include_once __DIR__ . '/../../../../abstracts/AbstractParameters.php';

class AssetParametersTest extends AbstractParameters
{
    public function testGetValues(): void
    {
        $test_entity_id = getItemByTypeName('Entity', '_test_child_2', true);

        // Create a computer model, type and state for testing
        $this->createItem('ComputerModel', ['name' => 'Test Model']);
        $this->createItem('ComputerType', ['name' => 'Test Type']);
        $this->createItem('State', ['name' => 'Test State']);

        $test_model_id = getItemByTypeName('ComputerModel', 'Test Model', true);
        $test_type_id = getItemByTypeName('ComputerType', 'Test Type', true);
        $test_state_id = getItemByTypeName('State', 'Test State', true);

        $this->createItem('Computer', [
            'name'               => 'pc_testGetValues',
            'serial'             => 'abcd1234',
            'entities_id'        => $test_entity_id,
            'computermodels_id'  => $test_model_id,
            'computertypes_id'   => $test_type_id,
            'states_id'          => $test_state_id,
        ]);

        $parameters = new AssetParameters();
        $values = $parameters->getValues(getItemByTypeName('Computer', 'pc_testGetValues'));
        $this->assertEquals(
            [
                'id'          => getItemByTypeName('Computer', 'pc_testGetValues', true),
                'name'        => 'pc_testGetValues',
                'itemtype'    => 'Computer',
                'serial'      => 'abcd1234',
                'model'       => 'Test Model',
                'type'        => 'Test Type',
                'state'       => 'Test State',
                'entity' => [
                    'id'           => $test_entity_id,
                    'name'         => '_test_child_2',
                    'completename' => 'Root entity > _test_root_entity > _test_child_2',
                ],
            ],
            $values
        );

        $this->testGetAvailableParameters($values, $parameters->getAvailableParameters());
    }
}
