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

use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\Destination\ConfigFieldWithStrategiesInterface;
use Override;

final class ValidationFieldConfig implements
    JsonFieldInterface,
    ConfigFieldWithStrategiesInterface
{
    // Unique reference to hardcoded names used for serialization and forms input names
    public const STRATEGIES            = 'strategies';
    public const SPECIFIC_QUESTION_IDS = 'specific_question_ids';
    public const SPECIFIC_ACTORS       = 'specific_actors';

    /**
     * @param array<ValidationFieldStrategy> $strategies
     * @param array<int>                     $specific_question_ids
     * @param array<int>                     $specific_actors
     */
    public function __construct(
        private array $strategies,
        private array $specific_question_ids = [],
        private array $specific_actors = [],
    ) {}

    #[Override]
    public static function jsonDeserialize(array $data): self
    {
        $strategies = array_map(
            fn(string $strategy) => ValidationFieldStrategy::tryFrom($strategy),
            $data[self::STRATEGIES] ?? []
        );
        if ($strategies === []) {
            $strategies = [ValidationFieldStrategy::NO_VALIDATION];
        }

        return new self(
            strategies: $strategies,
            specific_question_ids: $data[self::SPECIFIC_QUESTION_IDS] ?? [],
            specific_actors: $data[self::SPECIFIC_ACTORS] ?? [],
        );
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            self::STRATEGIES              => array_map(
                fn(ValidationFieldStrategy $strategy) => $strategy->value,
                $this->strategies
            ),
            self::SPECIFIC_QUESTION_IDS => $this->specific_question_ids,
            self::SPECIFIC_ACTORS => $this->specific_actors,
        ];
    }

    #[Override]
    public static function getStrategiesInputName(): string
    {
        return self::STRATEGIES;
    }

    /**
     * @return array<ValidationFieldStrategy>
     */
    public function getStrategies(): array
    {
        return $this->strategies;
    }

    public function getSpecificQuestionIds(): array
    {
        return $this->specific_question_ids;
    }

    public function getSpecificActors(): array
    {
        return $this->specific_actors;
    }
}
