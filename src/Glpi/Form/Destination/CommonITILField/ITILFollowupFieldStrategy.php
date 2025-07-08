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

enum ITILFollowupFieldStrategy: string
{
    case NO_FOLLOWUP       = 'no_followup';
    case SPECIFIC_VALUES   = 'specific_values';

    public function getLabel(): string
    {
        return match ($this) {
            self::NO_FOLLOWUP       => __("No Followup"),
            self::SPECIFIC_VALUES   => __("Specific Followup templates"),
        };
    }

    public function getITILFollowupTemplatesIDs(
        ITILFollowupFieldConfig $config
    ): ?array {
        return match ($this) {
            self::NO_FOLLOWUP      => null,
            self::SPECIFIC_VALUES  => $config->getSpecificITILFollowupTemplatesIds(),
        };
    }
}
