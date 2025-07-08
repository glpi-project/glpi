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

namespace Glpi\Asset\CustomFieldOption;

use Glpi\Asset\CustomFieldDefinition;

abstract class AbstractOption implements OptionInterface
{
    public function __construct(
        protected CustomFieldDefinition $custom_field,
        protected string $key,
        protected string $name,
        protected bool $apply_to_default = true,
        protected mixed $default_value = null
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setValue(mixed $value): void
    {
        $this->custom_field->fields['field_options'][$this->key] = $this->normalizeValue($value);
    }

    public function getValue(): mixed
    {
        return $this->custom_field->fields['field_options'][$this->key] ?? $this->default_value;
    }

    public function normalizeValue(mixed $value): mixed
    {
        return $value;
    }

    public function getApplyToDefault(): bool
    {
        return $this->apply_to_default;
    }
}
