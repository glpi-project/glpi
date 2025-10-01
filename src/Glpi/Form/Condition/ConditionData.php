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

namespace Glpi\Form\Condition;

use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\Comment;
use Glpi\Form\Condition\ConditionHandler\ConditionHandlerInterface;
use Glpi\Form\Question;
use Glpi\Form\Section;
use JsonException;
use JsonSerializable;
use Override;

use function Safe\json_decode;

final class ConditionData implements JsonSerializable
{
    public function __construct(
        private string $item_uuid,
        private string $item_type,
        private ?string $value_operator,
        private mixed $value,
        private ?string $logic_operator = null,
    ) {}

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

    public function getItemType(): Type
    {
        return Type::from($this->item_type);
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
        // No fallback here as an empty value is valid if the condition is not
        // fully specified yet.
        return ValueOperator::tryFrom($this->value_operator ?? "");
    }

    /**
     * Check if the condition is valid and fully specified
     *
     * @return bool True if the condition is valid, false otherwise
     */
    public function isValid(): bool
    {
        // Check if item UUID is not empty
        if (empty($this->item_uuid)) {
            return false;
        }

        // Check if item type is valid
        if (Type::tryFrom($this->item_type) === null) {
            return false;
        }

        // Check if value operator is valid
        $value_operator = $this->getValueOperator();
        if ($value_operator === null) {
            return false;
        }

        // Retrieve supported value operators
        $item = $this->getItem();
        if ($item === null) {
            return false;
        }
        $supported_value_operators = array_filter(
            $item->getConditionHandlers($this->getItemConfig()),
            fn(ConditionHandlerInterface $handler): bool => in_array(
                $value_operator,
                $handler->getSupportedValueOperators(),
            ),
        );

        // Check if value operator is supported by item
        if ($supported_value_operators === []) {
            return false;
        }

        return true;
    }

    private function getItem(): ?UsedAsCriteriaInterface
    {
        return match ($this->getItemType()) {
            Type::QUESTION => Question::getByUuid($this->getItemUuid())?->getQuestionType(),
            Type::SECTION  => Section::getByUuid($this->getItemUuid()),
            Type::COMMENT  => Comment::getByUuid($this->getItemUuid())
        };
    }

    private function getItemConfig(): ?JsonFieldInterface
    {
        if ($this->getItemType() !== Type::QUESTION) {
            return null;
        }

        $question = Question::getByUuid($this->getItemUuid());
        $item     = $question->getQuestionType();
        if (!$question) {
            return null;
        }

        try {
            $raw_config = json_decode($question->fields['extra_data'] ?? '', true);
            $config = $item->getExtraDataConfig($raw_config);
        } catch (JsonException $e) {
            $config = null;
        }

        return $config;
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'item'           => $this->getItemDropdownKey(),
            'item_uuid'      => $this->item_uuid,
            'item_type'      => $this->item_type,
            'value_operator' => $this->value_operator,
            'value'          => $this->value,
            'logic_operator' => $this->logic_operator,
        ];
    }
}
