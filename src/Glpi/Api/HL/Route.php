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

namespace Glpi\Api\HL;

use Attribute;
use Glpi\Api\HL\Middleware\AbstractMiddleware;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class Route
{
    /**
     * Access to the route is allowed without any authentication.
     */
    public const SECURITY_NONE = 0;

    /**
     * Access to the route is allowed only if the user is logged in (valid Glpi-Session-Token header).
     */
    public const SECURITY_AUTHENTICATED = 1;

    public const DEFAULT_PRIORITY = 10;

    public function __construct(
        public string $path,
        /** @var string[] $methods */
        public array $methods = [],
        /** @var array<string, string|array> $requirements */
        public array $requirements = [],
        public int $priority = self::DEFAULT_PRIORITY,
        public int $security_level = self::SECURITY_AUTHENTICATED,
        /** @var string[] */
        public array $tags = [],
        /** @var class-string<AbstractMiddleware>[] */
        public array $middlewares = [],
    ) {}
}
