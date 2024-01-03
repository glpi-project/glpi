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

namespace Glpi\ContentTemplates\Parameters;

use CommonDBTM;
use Glpi\ContentTemplates\Parameters\ParametersTypes\AttributeParameter;
use Glpi\Toolbox\Sanitizer;

/**
 * Abstract parameters class for "CommonDropdown" items.
 *
 * @since 10.0.0
 */
abstract class DropdownParameters extends AbstractParameters
{
    public function getAvailableParameters(): array
    {
        return [
            new AttributeParameter("id", __('ID')),
            new AttributeParameter("name", __('Name')),
        ];
    }

    protected function defineValues(CommonDBTM $item): array
    {

       // Output "unsanitized" values
        $fields = Sanitizer::unsanitize($item->fields);

        return [
            'id'   => $fields['id'],
            'name' => $fields['name'],
        ];
    }
}
