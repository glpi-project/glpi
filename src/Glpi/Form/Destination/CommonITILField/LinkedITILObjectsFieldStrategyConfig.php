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
use Override;

/**
 * Configuration for a single linked itilobject strategy
 */
final class LinkedITILObjectsFieldStrategyConfig implements JsonFieldInterface
{
    // Unique reference to hardcoded names used for serialization and forms input names
    public const STRATEGY = 'strategy';
    public const LINKTYPE = 'linktype';
    public const SPECIFIC_DESTINATION_IDS = 'specific_destination_ids';
    public const SPECIFIC_QUESTION_IDS = 'specific_question_ids';
    public const SPECIFIC_ITILOBJECT = 'specific_itilobject';

    /**+
     * @param array<int> $specific_destination_ids
     * @param array<int> $specific_question_ids
     * @param array<string, string|int> $specific_itilobject
     */
    public function __construct(
        private ?LinkedITILObjectsFieldStrategy $strategy = null,
        private string $linktype = '',
        private array $specific_destination_ids = [],
        private array $specific_question_ids = [],
        private array $specific_itilobject = []
    ) {}

    #[Override]
    public static function jsonDeserialize(array $data): self
    {
        $strategy = LinkedITILObjectsFieldStrategy::tryFrom($data[self::STRATEGY] ?? "");

        return new self(
            strategy: $strategy,
            linktype: $data[self::LINKTYPE] ?? '',
            specific_destination_ids: $data[self::SPECIFIC_DESTINATION_IDS] ?? [],
            specific_question_ids: $data[self::SPECIFIC_QUESTION_IDS] ?? [],
            specific_itilobject: $data[self::SPECIFIC_ITILOBJECT] ?? []
        );
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            self::STRATEGY => $this->strategy?->value,
            self::LINKTYPE => $this->linktype,
            self::SPECIFIC_DESTINATION_IDS => $this->specific_destination_ids,
            self::SPECIFIC_QUESTION_IDS => $this->specific_question_ids,
            self::SPECIFIC_ITILOBJECT => $this->specific_itilobject,
        ];
    }

    public function getStrategy(): ?LinkedITILObjectsFieldStrategy
    {
        return $this->strategy;
    }

    public function getLinktype(): string
    {
        return $this->linktype;
    }

    public function getSpecificQuestionIds(): array
    {
        return $this->specific_question_ids;
    }

    public function getSpecificDestinationIds(): array
    {
        return $this->specific_destination_ids;
    }

    public function getSpecificItilObject(): array
    {
        return $this->specific_itilobject;
    }

    /** @return class-string<CommonITILObject>|null */
    public function getSpecificItilObjectItemtype(): ?string
    {
        return $this->specific_itilobject['itemtype'] ?? null;
    }

    public function getSpecificItilObjectItemsId(): ?int
    {
        return $this->specific_itilobject['items_id'] ?? null;
    }
}
