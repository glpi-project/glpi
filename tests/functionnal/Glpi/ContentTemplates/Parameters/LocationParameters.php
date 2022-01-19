<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace tests\units\Glpi\ContentTemplates\Parameters;

class LocationParameters extends AbstractParameters
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
            'locations_id' => getItemByTypeName('Location', 'location_testGetValues_parent', true)
        ]);

        $parameters = $this->newTestedInstance();
        $values = $parameters->getValues(getItemByTypeName('Location', 'location_testGetValues'));
        $this->array($values)->isEqualTo([
            'id'   => getItemByTypeName('Location', 'location_testGetValues', true),
            'name' => 'location_testGetValues',
            'completename' => 'location_testGetValues_parent > location_testGetValues',
        ]);

        $this->testGetAvailableParameters($values, $parameters->getAvailableParameters());
    }
}
