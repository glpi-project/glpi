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

namespace Glpi\Api\HL\GraphQL\Type;

use GraphQL\Type\Definition\StringType;

use function Safe\strtotime;

/**
 * GraphQL type for RFC 3339 date-time strings.
 */
class DateTimeType extends StringType
{
    public string $name = 'RFC3339DateTime';

    private static ?DateTimeType $instance = null;

    public function serialize($value): string
    {
        $value = parent::serialize($value);
        return date(DATE_RFC3339, strtotime($value));
    }

    public static function dateTime(): self
    {
        if (self::$instance === null) {
            self::$instance = new DateTimeType();
        }
        return self::$instance;
    }
}
