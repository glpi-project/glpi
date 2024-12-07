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
 * Represents a section of configuration options within a configuration scope.
 * For example, the "Global" configuration has a tab for "System" options,
 * and then that tab has sections for "Maintenance mode", "Proxy configuration", etc.
 * In this case, System, Maintenance mode, Proxy configuration, etc. are all ConfigSections with Maintenance mode and Proxy configuration being children of System.
 */
final class ConfigSection
{
    /**
     * @param string $key The key of the section
     * @param string $label The label of the section
     * @param string|null $icon The CSS class icon of the section, if any
     * @param ConfigSection|null $parent The parent section, if any
     */
    public function __construct(
        private readonly string $key,
        private readonly string $label,
        private readonly ?string $icon = null,
        private readonly ?ConfigSection $parent = null
    ) {
    }

    /**
     * @return string The key of the section
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return string The label of the section
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return string|null The CSS class icon of the section, if any
     */
    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /**
     * @return ConfigSection|null The parent section, if any
     */
    public function getParent(): ?ConfigSection
    {
        return $this->parent;
    }

    /**
     * @return string The full key of the section, including all its parent sections
     */
    public function getFullName(): string
    {
        $sections = [];
        $section = $this;
        while ($section !== null) {
            $sections[] = $section;
            $section = $section->getParent();
        }
        return implode('.', array_reverse(array_map(static fn(ConfigSection $section) => $section->getKey(), $sections)));
    }

    /**
     * @return string The label of the section and all its parent sections
     * @used-by 'templates/pages/setup/advconfig/config_table.html.twig'
     */
    public function getBreadcrumbLabel(): string
    {
        $sections = [];
        $section = $this;
        while ($section !== null) {
            $sections[] = $section;
            $section = $section->getParent();
        }
        return implode(' > ', array_reverse(array_map(static fn(ConfigSection $section) => $section->getLabel(), $sections)));
    }
}
