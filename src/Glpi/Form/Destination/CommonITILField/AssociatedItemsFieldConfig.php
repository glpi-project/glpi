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

use CommonITILObject;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\Destination\ConfigFieldWithStrategiesInterface;
use Glpi\Form\Destination\HasFieldWithQuestionId;
use Override;

#[HasFieldWithQuestionId(self::SPECIFIC_QUESTION_IDS, is_array: true)]
final class AssociatedItemsFieldConfig implements
    JsonFieldInterface,
    ConfigFieldWithStrategiesInterface
{
    // Unique reference to hardcoded names used for serialization and forms input names
    public const STRATEGIES = 'strategies';
    public const SPECIFIC_QUESTION_IDS = 'specific_question_ids';
    public const SPECIFIC_ASSOCIATED_ITEMS = 'specific_associated_items';

    /**
     * @param array<AssociatedItemsFieldStrategy> $strategies
     * @param array<int> $specific_question_ids
     * @param array<CommonITILObject> $specific_associated_items
     */
    public function __construct(
        private array $strategies,
        private array $specific_question_ids = [],
        private array $specific_associated_items = [],
    ) {}

    #[Override]
    public static function jsonDeserialize(array $data): self
    {
        $strategies = array_map(
            fn(string $strategy) => AssociatedItemsFieldStrategy::tryFrom($strategy),
            $data[self::STRATEGIES] ?? []
        );
        if ($strategies === []) {
            $strategies = [AssociatedItemsFieldStrategy::ALL_VALID_ANSWERS];
        }

        return new self(
            strategies: $strategies,
            specific_question_ids: $data[self::SPECIFIC_QUESTION_IDS] ?? [],
            specific_associated_items: $data[self::SPECIFIC_ASSOCIATED_ITEMS] ?? [],
        );
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            self::STRATEGIES                => array_map(
                fn(AssociatedItemsFieldStrategy $strategy) => $strategy->value,
                $this->strategies
            ),
            self::SPECIFIC_QUESTION_IDS     => $this->specific_question_ids,
            self::SPECIFIC_ASSOCIATED_ITEMS => $this->specific_associated_items,
        ];
    }

    #[Override]
    public static function getStrategiesInputName(): string
    {
        return self::STRATEGIES;
    }

    /**
     * @return array<AssociatedItemsFieldStrategy>
     */
    public function getStrategies(): array
    {
        return $this->strategies;
    }

    public function getSpecificQuestionIds(): array
    {
        return $this->specific_question_ids;
    }

    public function getSpecificAssociatedItems(): array
    {
        return $this->specific_associated_items;
    }
}
