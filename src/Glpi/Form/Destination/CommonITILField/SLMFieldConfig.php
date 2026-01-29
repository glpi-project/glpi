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
use Override;

abstract class SLMFieldConfig implements
    JsonFieldInterface,
    ConfigFieldWithStrategiesInterface
{
    // Unique reference to hardcoded names used for serialization and forms input names
    public const STRATEGY        = 'strategy';
    public const SLM_ID          = 'slm_id';
    public const QUESTION_ID     = 'question_id';
    public const TIME_OFFSET     = 'time_offset';
    public const TIME_DEFINITION = 'time_definition';

    public function __construct(
        private SLMFieldStrategy $strategy,
        private ?int $specific_slm_id    = null,
        private ?int $question_id        = null,
        private ?int $time_offset        = null,
        private ?string $time_definition = null,
    ) {}

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            self::STRATEGY        => $this->strategy->value,
            self::SLM_ID          => $this->specific_slm_id,
            self::QUESTION_ID     => $this->question_id,
            self::TIME_OFFSET     => $this->time_offset,
            self::TIME_DEFINITION => $this->time_definition,
        ];
    }

    #[Override]
    public static function getStrategiesInputName(): string
    {
        return self::STRATEGY;
    }

    /**
     * @return array<SLMFieldStrategy>
     */
    public function getStrategies(): array
    {
        return [$this->strategy];
    }

    public function getSpecificSLMID(): ?int
    {
        return $this->specific_slm_id;
    }

    public function getQuestionId(): ?int
    {
        return $this->question_id;
    }

    public function getTimeOffset(): ?int
    {
        return $this->time_offset;
    }

    public function getTimeDefinition(): ?string
    {
        return $this->time_definition;
    }
}
