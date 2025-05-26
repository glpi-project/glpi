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

final class ITILFollowupFieldConfig implements
    JsonFieldInterface,
    ConfigFieldWithStrategiesInterface
{
    // Unique reference to hardcoded names used for serialization and forms input names
    public const STRATEGY = 'strategy';
    public const ITILFOLLOWUPTEMPLATE_IDS = 'itilfollowuptemplate_ids';

    public function __construct(
        private ITILFollowupFieldStrategy $strategy,
        private ?array $specific_itilfollowuptemplates_ids = null,
    ) {}

    #[Override]
    public static function jsonDeserialize(array $data): self
    {
        $strategy = ITILFollowupFieldStrategy::tryFrom($data[self::STRATEGY] ?? "");
        if ($strategy === null) {
            $strategy = ITILFollowupFieldStrategy::NO_FOLLOWUP;
        }

        return new self(
            strategy: $strategy,
            specific_itilfollowuptemplates_ids: $data[self::ITILFOLLOWUPTEMPLATE_IDS] ?? [],
        );
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            self::STRATEGY => $this->strategy->value,
            self::ITILFOLLOWUPTEMPLATE_IDS => $this->specific_itilfollowuptemplates_ids,
        ];
    }

    #[Override]
    public static function getStrategiesInputName(): string
    {
        return self::STRATEGY;
    }

    /**
     * @return array<ITILFollowupFieldStrategy>
     */
    public function getStrategies(): array
    {
        return [$this->strategy];
    }

    public function getSpecificITILFollowupTemplatesIds(): ?array
    {
        return $this->specific_itilfollowuptemplates_ids;
    }
}
