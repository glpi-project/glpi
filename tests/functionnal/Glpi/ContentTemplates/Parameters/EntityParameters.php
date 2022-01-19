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

class EntityParameters extends AbstractParameters
{
    public function testGetValues(): void
    {
        $parameters = $this->newTestedInstance();
        $values = $parameters->getValues(getItemByTypeName('Entity', '_test_child_2'));
        $this->array($values)->isEqualTo([
            'id'   => getItemByTypeName('Entity', '_test_child_2', true),
            'name' => '_test_child_2',
            'completename' => 'Root entity > _test_root_entity > _test_child_2',
        ]);

        $this->testGetAvailableParameters($values, $parameters->getAvailableParameters());
    }
}
