<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

namespace Glpi\UI\Component;

/**
 * Static cache adapter for components
 *
 * Store rendered components into a static variable that can be reused when the
 * same parameters are supplied to the render function.
 */
class StaticCachedComponent implements ComponentInterface
{
    /**
     * Component
     * @var ComponentInterface
     */
    protected ComponentInterface $component;

    /**
     * Static cache
     * @var array $cache
     */
    protected static array $cache = [];

    /**
     * Register target component
     */
    public function __construct(ComponentInterface $component)
    {
        $this->component = $component;
    }

    public function render(array $params): string
    {
        // Cache key built on component name + hash of parameters
        $cache_key = get_class($this->component) . "_" . md5(serialize($params));

        // Try to render from cache
        if (isset(static::$cache[$cache_key])) {
            return static::$cache[$cache_key];
        }

        // Render compontent
        $content = $this->component->render($params);

        // Save cache and return content
        static::$cache[$cache_key] = $content;
        return $content;
    }
}
