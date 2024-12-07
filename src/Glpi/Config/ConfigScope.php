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

namespace Glpi\Config;

/**
 * Enumeration of the different scopes of configuration options provided by GLPI.
 */
enum ConfigScope
{
    case GLOBAL;
    case ENTITY;
    case USER;

    /**
     * @return string The internal identifier of the scope
     */
    public function getKey(): string
    {
        return match ($this) {
            self::GLOBAL => 'global',
            self::ENTITY => 'entity',
            self::USER => 'user',
        };
    }

    /**
     * @param string $key The internal identifier of the scope
     * @return self The scope corresponding to the given key
     */
    public static function fromKey(string $key): self
    {
        return match ($key) {
            'global' => self::GLOBAL,
            'entity' => self::ENTITY,
            'user' => self::USER,
            default => throw new \InvalidArgumentException("Invalid config scope key: $key"),
        };
    }

    /**
     * @return string The label of the scope
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::GLOBAL => __('Global'),
            self::ENTITY => \Entity::getTypeName(1),
            self::USER => \User::getTypeName(1),
        };
    }

    /**
     * @return array<string,string> The labels of all the scopes with the keys being the internal identifiers
     */
    public static function getLabels(): array
    {
        return array_combine(
            array_map(static fn(ConfigScope $scope) => $scope->getKey(), self::cases()),
            array_map(static fn(ConfigScope $scope) => $scope->getLabel(), self::cases())
        );
    }

    /**
     * @return positive-int The weight of the scope which determines how a final value is calculated from multiple scopes.
     * The higher the weight, the more specific/higher precedence the scope has.
     */
    public function getWeight(): int
    {
        // Room is left between the current scopes in case new scopes are added that need to fit between them.
        // For example, if groups can have config options in the future it would probably between ENTITY and USER.
        return match ($this) {
            self::GLOBAL => 0,
            self::ENTITY => 10,
            self::USER => 20,
        };
    }
}
