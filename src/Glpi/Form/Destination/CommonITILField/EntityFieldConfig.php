<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace Glpi\Form\Destination\CommonITILField;

use Glpi\DBAL\JsonFieldInterface;
use Override;

final class EntityFieldConfig implements JsonFieldInterface
{
    // Unique reference to hardcoded names used for serialization and forms input names
    public const STRATEGY = 'strategy';
    public const QUESTION_ID = 'question_id';
    public const ENTITY_ID = 'entity_id';

    public function __construct(
        private EntityFieldStrategy $strategy,
        private ?int $specific_question_id = null,
        private ?int $specific_entity_id = null,
    ) {
    }

    #[Override]
    public static function jsonDeserialize(array $data): self
    {
        $strategy = EntityFieldStrategy::tryFrom($data[self::STRATEGY] ?? "");
        if ($strategy === null) {
            $strategy = EntityFieldStrategy::LAST_VALID_ANSWER;
        }

        return new self(
            strategy: $strategy,
            specific_question_id: $data[self::QUESTION_ID],
            specific_entity_id: $data[self::ENTITY_ID],
        );
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            self::STRATEGY => $this->strategy->value,
            self::QUESTION_ID => $this->specific_question_id,
            self::ENTITY_ID => $this->specific_entity_id,
        ];
    }

    public function getStrategy(): EntityFieldStrategy
    {
        return $this->strategy;
    }

    public function getSpecificQuestionId(): ?int
    {
        return $this->specific_question_id;
    }

    public function getSpecificEntityId(): ?int
    {
        return $this->specific_entity_id;
    }
}
