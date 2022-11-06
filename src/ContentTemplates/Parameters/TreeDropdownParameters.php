<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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
 * Abstract parameters class for "CommonTreeDropdown" items.
 *
 * @since 10.0.0
 */
abstract class TreeDropdownParameters extends DropdownParameters
{
    public function getAvailableParameters(): array
    {
        $parameter = parent::getAvailableParameters();
        $parameter[] = new AttributeParameter("completename", __('Complete name'));
        return $parameter;
    }

    protected function defineValues(CommonDBTM $item): array
    {

       // Output "unsanitized" values
        $fields = Sanitizer::unsanitize($item->fields);

        $values = parent::defineValues($item);
        $values['completename'] = $fields['completename'];
        return $values;
    }
}
