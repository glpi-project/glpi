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

use Glpi\ContentTemplates\Parameters\SupplierParameters;

include_once __DIR__ . '/../../../../abstracts/AbstractParameters.php';

class SupplierParametersTest extends AbstractParameters
{
    public function testGetValues(): void
    {
        $test_entity_id = getItemByTypeName('Entity', '_test_child_2', true);

        $this->createItem('Supplier', [
            'name'        => 'supplier_testGetValues',
            'entities_id' => $test_entity_id,
            'address'     => '221B Baker Street',
            'town'        => 'London',
            'postcode'    => 'NW1 6XE',
            'state'       => 'England',
            'country'     => 'UK',
            'phonenumber' => '+44 20 7224 ...0',
            'fax'         => '+44 20 7224 ...1',
            'email'       => 'test@glpi-project.org',
            'website'     => 'https://glpi-project.org',
        ]);

        $parameters = new SupplierParameters();
        $values = $parameters->getValues(getItemByTypeName('Supplier', 'supplier_testGetValues'));
        $this->assertEquals(
            [
                'id'       => getItemByTypeName('Supplier', 'supplier_testGetValues', true),
                'name'     => 'supplier_testGetValues',
                'address'  => '221B Baker Street',
                'city'     => 'London',
                'postcode' => 'NW1 6XE',
                'state'    => 'England',
                'country'  => 'UK',
                'phone'    => '+44 20 7224 ...0',
                'fax'      => '+44 20 7224 ...1',
                'email'    => 'test@glpi-project.org',
                'website'  => 'https://glpi-project.org',
            ],
            $values
        );

        $this->testGetAvailableParameters($values, $parameters->getAvailableParameters());
    }
}
