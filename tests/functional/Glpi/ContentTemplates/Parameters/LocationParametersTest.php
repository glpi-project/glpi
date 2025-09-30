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

use Glpi\ContentTemplates\Parameters\LocationParameters;

include_once __DIR__ . '/../../../../abstracts/AbstractParameters.php';

class LocationParametersTest extends AbstractParameters
{
    public function testGetValues(): void
    {
        $test_entity_id = getItemByTypeName('Entity', '_test_child_2', true);

        $this->createItem('Location', [
            'name'        => 'location_testGetValues_parent',
            'entities_id' => $test_entity_id,
        ]);

        $this->createItem('Location', [
            'name'        => 'location_testGetValues',
            'entities_id' => $test_entity_id,
            'locations_id' => getItemByTypeName('Location', 'location_testGetValues_parent', true),
        ]);

        $parameters = new LocationParameters();
        $values = $parameters->getValues(getItemByTypeName('Location', 'location_testGetValues'));
        $this->assertEquals(
            [
                'id'   => getItemByTypeName('Location', 'location_testGetValues', true),
                'name' => 'location_testGetValues',
                'completename' => 'location_testGetValues_parent > location_testGetValues',
            ],
            $values
        );

        $this->testGetAvailableParameters($values, $parameters->getAvailableParameters());
    }
}
