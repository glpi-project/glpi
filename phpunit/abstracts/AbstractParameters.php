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

use DbTestCase;
use Glpi\ContentTemplates\TemplateManager;

class AbstractParameters extends DbTestCase
{
    protected function testGetAvailableParameters($values, $parameters): void
    {
        $parameters = TemplateManager::computeParameters($parameters);

        $values_keys = array_keys($values);
        $parameters_keys = array_column($parameters, 'key');

        // Remove "flat" arrays (requester.user, requester.groups, ...)
        $parameters_keys = array_map(function ($parameter) {
            $properties = explode('.', $parameter);
            return array_shift($properties);
        }, $parameters_keys);
        $parameters_keys = array_values(array_unique($parameters_keys));

        $this->assertEquals($values_keys, $parameters_keys);
    }
}
