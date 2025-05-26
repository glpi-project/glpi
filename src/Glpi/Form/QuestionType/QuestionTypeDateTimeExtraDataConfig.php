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

use Glpi\DBAL\JsonFieldInterface;
use Override;

final class QuestionTypeDateTimeExtraDataConfig implements JsonFieldInterface
{
    // Unique reference to hardcoded name used for serialization
    public const IS_DEFAULT_VALUE_CURRENT_TIME = "is_default_value_current_time";
    public const IS_DATE_ENABLED               = "is_date_enabled";
    public const IS_TIME_ENABLED               = "is_time_enabled";

    public function __construct(
        private bool $is_default_value_current_time = false,
        private bool $is_date_enabled = true,
        private bool $is_time_enabled = false,
    ) {}

    #[Override]
    public static function jsonDeserialize(array $data): self
    {
        return new self(
            is_default_value_current_time: $data[self::IS_DEFAULT_VALUE_CURRENT_TIME] ?? false,
            is_date_enabled              : $data[self::IS_DATE_ENABLED] ?? true,
            is_time_enabled              : $data[self::IS_TIME_ENABLED] ?? false,
        );
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            self::IS_DEFAULT_VALUE_CURRENT_TIME => (int) $this->is_default_value_current_time,
            self::IS_DATE_ENABLED               => (int) $this->is_date_enabled,
            self::IS_TIME_ENABLED               => (int) $this->is_time_enabled,
        ];
    }

    public function isDefaultValueCurrentTime(): bool
    {
        return $this->is_default_value_current_time;
    }

    public function isDateEnabled(): bool
    {
        return $this->is_date_enabled;
    }

    public function isTimeEnabled(): bool
    {
        return $this->is_time_enabled;
    }
}
