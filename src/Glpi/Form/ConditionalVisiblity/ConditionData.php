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

namespace Glpi\Form\ConditionalVisiblity;

final class ConditionData
{
    public function __construct(
        private string $item_uuid,
        private string $item_type,
        private ?string $value_operator,
        private mixed $value,
        private ?string $logic_operator = null,
    ) {
    }

    /**
     * Itemtype + uuid, used for dropdowns to allow selecting type + item using
     * a single dropdown
     */
    public function getItemDropdownKey(): string
    {
        return $this->item_type . '-' . $this->item_uuid;
    }

    public function getItemUuid(): string
    {
        return $this->item_uuid;
    }

    public function getItemType(): string
    {
        return $this->item_type;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getLogicOperator(): LogicOperator
    {
        // Fallback to "AND" if no value is set.
        return LogicOperator::tryFrom($this->logic_operator ?? "") ?? LogicOperator::AND;
    }

    public function getValueOperator(): ?ValueOperator
    {
        // No follback here as an empty value is valid if the condition is not
        // fully specified yet.
        return ValueOperator::tryFrom($this->value_operator ?? "");
    }
}
