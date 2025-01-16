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

namespace Glpi\Dropdown;

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
            foreach (array_keys($optgroup) as $classname) {
                if (
                    !is_subclass_of($classname, Dropdown::class)
                    && !isPluginItemType($classname)
                    && preg_match('/(Model|Type)$/i', $classname) !== 1 // `*Model` and `*Type` patterns are already blacklisted
                ) {
                    // Dropdown is not a custom one or from a plugin
                    $core_dropdowns[] = $classname;
                }
            }
        }

        return $core_dropdowns;
    }

    public function autoloadClass(string $classname): void
    {
        $ns = static::getDefinitionClass()::getCustomObjectNamespace() . '\\';

        if (!\str_starts_with($classname, $ns)) {
            return;
        }

        $pattern = '/^' . preg_quote($ns, '/') . '([A-Za-z]+)$/';

        if (preg_match($pattern, $classname) === 1) {
            $system_name = preg_replace($pattern, '$1', $classname);
            $definition  = $this->getDefinition($system_name);

            if ($definition === null) {
                return;
            }

            $this->loadConcreteClass($definition);
        }
    }

    private function loadConcreteClass(DropdownDefinition $definition): void
    {
        $rightname = $definition->getCustomObjectRightname();

        // Static properties must be defined in each concrete class otherwise they will be shared
        // accross all concrete classes, and so would be overriden by the values from the last loaded class.
        eval(<<<PHP
namespace Glpi\\CustomDropdown;

use Glpi\\Dropdown\\Dropdown;

final class {$definition->getDropdownClassName(false)} extends Dropdown {
    protected static string \$definition_system_name = '{$definition->fields['system_name']}';
    public static \$rightname = '{$rightname}';
}
PHP
        );
    }
}
