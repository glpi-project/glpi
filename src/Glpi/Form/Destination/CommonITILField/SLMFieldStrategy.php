<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\Form\Destination\CommonITILField;

enum SLMFieldStrategy: string
{
    case FROM_TEMPLATE = 'from_template';
    case SPECIFIC_VALUE = 'specific_value';

    public function getLabel(SLMField $field): string
    {
        return match ($this) {
            self::FROM_TEMPLATE     => __("From template"),
            self::SPECIFIC_VALUE    => sprintf(__("Specific %s"), $field->getSLM()->getTypeName(1)),
        };
    }

    public function getSLMID(
        SLMFieldConfig $config,
    ): ?int {
        return match ($this) {
            self::FROM_TEMPLATE => null, // Let the template apply its default value by itself.
            self::SPECIFIC_VALUE => $config->getSpecificSLMID(),
        };
    }
}
