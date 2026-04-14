<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace Glpi\Asset\CustomFieldType;

use Glpi\Asset\CustomFieldOption\BooleanOption;
use Glpi\Asset\CustomFieldOption\ProfileRestrictOption;

/**
 * Special type used for native fields that don't fit in any other type, usually because they output raw HTML, but should work with custom assets.
 * This type only exposes options to make the field show in full width, and to hide it for specific profiles.
 */
class RawType extends AbstractType
{
    public static function isAllowedForCustomFields(): bool
    {
        return false;
    }

    public static function getName(): string
    {
        return '';
    }

    public function getOptions(): array
    {
        return [
            new BooleanOption($this->custom_field, 'full_width', __('Full width'), false),
            new ProfileRestrictOption($this->custom_field, 'hidden', __('Hidden for these profiles'), false),
        ];
    }

    public function getFormInput(string $name, mixed $value, ?string $label = null, bool $for_default = false): string
    {
        return '';
    }
}
