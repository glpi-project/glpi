<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace Glpi\Dropdown;

use Glpi\CustomObject\AbstractDefinition;
use Glpi\CustomObject\AbstractDefinitionManager;
use ReflectionClass;

/**
 * @extends AbstractDefinitionManager<DropdownDefinition>
 */
final class DropdownDefinitionManager extends AbstractDefinitionManager
{
    /**
     * Singleton instance
     * @return static|null
     */
    private static ?DropdownDefinitionManager $instance = null;

    /**
     * Definitions cache.
     * @var DropdownDefinition[]|null
     */
    protected ?array $definitions_data;

    /**
     * Get singleton instance
     *
     * @return DropdownDefinitionManager
     */
    public static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function getDefinitionClass(): string
    {
        return DropdownDefinition::class;
    }

    public function getReservedSystemNames(): array
    {
        $standard_dropdowns = \Dropdown::getStandardDropdownItemTypes();
        $core_dropdowns = [];
        foreach ($standard_dropdowns as $optgroup) {
            foreach ($optgroup as $c => $n) {
                if (!is_subclass_of($c, Dropdown::class) && !isPluginItemType($c)) {
                    // Dropdown is not a custom one or from a plugin
                    $core_dropdowns[] = $c;
                }
            }
        }

        // Remove namespaces from class names (Not sure how they would map in the future if all dropdowns moved to use the generic dropdown classes)
        return array_map(static fn($c) => substr($c, strrpos($c, '\\') + 1), $core_dropdowns);
    }

    protected function loadConcreteClass(AbstractDefinition $definition): void
    {
        $rightname = $definition->getCustomObjectRightname();
        $parent_class = $definition->fields['is_tree'] ? 'TreeDropdown' : 'Dropdown';

        // Static properties must be defined in each concrete class otherwise they will be shared
        // accross all concrete classes, and so would be overriden by the values from the last loaded class.
        eval(<<<PHP
namespace Glpi\\CustomDropdown;

use Glpi\\Dropdown\\{$parent_class};
use Glpi\\Dropdown\\DropdownDefinition;

final class {$definition->getCustomObjectClassName(false)} extends {$parent_class} {
    protected static DropdownDefinition \$definition;
    public static \$rightname = '{$rightname}';
}
PHP
        );

        // Set the definition of the concrete class using reflection API.
        // It permits to directly store a pointer to the definition on the object without having
        // to make the property publicly writable.
        $reflected_class = new ReflectionClass($definition->getCustomObjectClassName());
        $reflected_class->setStaticPropertyValue('definition', $definition);
    }
}
