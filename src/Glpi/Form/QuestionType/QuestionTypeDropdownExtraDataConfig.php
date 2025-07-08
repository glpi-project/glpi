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

namespace Glpi\Form\QuestionType;

use Override;

final class QuestionTypeDropdownExtraDataConfig extends QuestionTypeSelectableExtraDataConfig
{
    // Unique reference to hardcoded name used for serialization
    public const IS_MULTIPLE_DROPDOWN = "is_multiple_dropdown";

    public function __construct(
        array $options,
        private bool $is_multiple_dropdown = false,
    ) {
        parent::__construct(options: $options);
    }

    #[Override]
    public static function jsonDeserialize(array $data): self
    {
        return new self(
            options: $data[self::OPTIONS] ?? [],
            is_multiple_dropdown: $data[self::IS_MULTIPLE_DROPDOWN] ?? false,
        );
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                self::IS_MULTIPLE_DROPDOWN => $this->is_multiple_dropdown,
            ]
        );
    }

    public function isMultipleDropdown(): bool
    {
        return $this->is_multiple_dropdown;
    }
}
