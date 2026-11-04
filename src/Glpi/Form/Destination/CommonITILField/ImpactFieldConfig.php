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

namespace Glpi\Form\Destination\CommonITILField;

use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\Destination\ConfigFieldWithStrategiesInterface;
use Glpi\Form\Destination\HasFieldWithQuestionId;
use Override;

#[HasFieldWithQuestionId(self::SPECIFIC_QUESTION_ID)]
final class ImpactFieldConfig implements
    JsonFieldInterface,
    ConfigFieldWithStrategiesInterface
{
    // Unique reference to hardcoded names used for serialization and forms input names
    public const STRATEGY = 'strategy';
    public const SPECIFIC_QUESTION_ID = 'specific_question_id';
    public const SPECIFIC_IMPACT_VALUE = 'specific_impact_value';

    public function __construct(
        private ImpactFieldStrategy $strategy,
        private ?int $specific_question_id = null,
        private ?int $specific_impact_value = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    #[Override]
    public static function jsonDeserialize(array $data): self
    {
        $strategy = ImpactFieldStrategy::tryFrom($data[self::STRATEGY] ?? "");
        if ($strategy === null) {
            $strategy = ImpactFieldStrategy::LAST_VALID_ANSWER;
        }

        return new self(
            strategy: $strategy,
            specific_question_id: $data[self::SPECIFIC_QUESTION_ID] ?? null,
            specific_impact_value: $data[self::SPECIFIC_IMPACT_VALUE] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function jsonSerialize(): array
    {
        return [
            self::STRATEGY => $this->strategy->value,
            self::SPECIFIC_QUESTION_ID => $this->specific_question_id,
            self::SPECIFIC_IMPACT_VALUE => $this->specific_impact_value,
        ];
    }

    #[Override]
    public static function getStrategiesInputName(): string
    {
        return self::STRATEGY;
    }

    /**
     * @return non-empty-list<ImpactFieldStrategy>
     */
    #[Override]
    public function getStrategies(): array
    {
        return [$this->strategy];
    }

    public function getSpecificImpact(): ?int
    {
        return $this->specific_impact_value;
    }

    public function getSpecificQuestionId(): ?int
    {
        return $this->specific_question_id;
    }
}
