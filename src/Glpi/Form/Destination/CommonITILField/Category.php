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

namespace Glpi\Form\Destination\CommonITILField;

enum Category: string
{
    case PROPERTIES       = 'properties';
    case ACTORS           = 'actors';
    case TIMELINE         = 'timeline';
    case SERVICE_LEVEL    = 'service_level';
    case ASSOCIATED_ITEMS = 'associated_items';

    public function getLabel(): string
    {
        return match ($this) {
            self::PROPERTIES       => __("Properties"),
            self::ACTORS           => __("Actors"),
            self::TIMELINE         => __("Timeline"),
            self::SERVICE_LEVEL    => __("Service levels"),
            self::ASSOCIATED_ITEMS => __("Associated items"),
        };
    }

    public function getWeight(): int
    {
        return match ($this) {
            self::PROPERTIES       => 10,
            self::ACTORS           => 20,
            self::TIMELINE         => 30,
            self::SERVICE_LEVEL    => 40,
            self::ASSOCIATED_ITEMS => 50,
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::PROPERTIES       => 'ti ti-alert-circle',
            self::ACTORS           => 'ti ti-user',
            self::TIMELINE         => 'ti ti-messages',
            self::SERVICE_LEVEL    => 'ti ti-stopwatch',
            self::ASSOCIATED_ITEMS => 'ti ti-link',
        };
    }
}
