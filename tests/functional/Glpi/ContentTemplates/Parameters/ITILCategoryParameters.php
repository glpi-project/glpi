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

class ITILCategoryParameters extends AbstractParameters
{
    public function testGetValues(): void
    {
        $test_entity_id = getItemByTypeName('Entity', '_test_child_2', true);

        $this->createItem('ITILCategory', [
            'name'        => 'itilcategory_testGetValues',
            'entities_id' => $test_entity_id
        ]);

        $parameters = $this->newTestedInstance();
        $values = $parameters->getValues(getItemByTypeName('ITILCategory', 'itilcategory_testGetValues'));
        $this->array($values)->isEqualTo([
            'id'           => getItemByTypeName('ITILCategory', 'itilcategory_testGetValues', true),
            'name'         => 'itilcategory_testGetValues',
            'completename' => 'itilcategory_testGetValues',
        ]);

        $this->testGetAvailableParameters($values, $parameters->getAvailableParameters());
    }
}
