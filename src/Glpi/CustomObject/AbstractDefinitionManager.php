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

namespace Glpi\CustomObject;

/**
 * Abstract class for custom object definition managers
 * @template ConcreteDefinition of AbstractDefinition
 */
abstract class AbstractDefinitionManager
{
    /**
     * Definitions cache.
     * @var array
     * @phpstan-var array<class-string<ConcreteDefinition>, array<int, ConcreteDefinition>>
     */
    private array $definitions_data = [];

    abstract public static function getInstance(): self;

    /**
     * @return class-string<AbstractDefinition>
     * @phpstan-return class-string<ConcreteDefinition>
     */
    abstract public static function getDefinitionClass(): string;

    /**
     * Returns the list of reserved system names
     * @return array
     */
    abstract public function getReservedSystemNames(): array;

    /**
     * Register the class autoload function.
     * @return void
     */
    final public function registerAutoload(): void
    {
        spl_autoload_register([$this, 'autoloadClass']);
    }

    /**
     * Autoload custom object class, if requested class is managed by this definition manager.
     *
     * @param string $classname
     * @return void
     */
    abstract public function autoloadClass(string $classname): void;

    /**
     * Boostrap all the active definitions.
     */
    final public function bootstrapDefinitions(): void
    {
        foreach ($this->getDefinitions(true) as $definition) {
            $this->bootstrapDefinition($definition);
        }
    }

    /**
     * Bootstrap the definition.
     * @param AbstractDefinition $definition
     * @phpstan-param ConcreteDefinition $definition
     * @return void
     */
    public function bootstrapDefinition(AbstractDefinition $definition)
    {
    }

    final public function getCustomObjectClassNames(bool $with_namespace = true): array
    {
        $classes = [];

        foreach ($this->getDefinitions() as $definition) {
            if (!$definition->isActive()) {
                continue;
            }
            $classes[] = $definition->getCustomObjectClassName($with_namespace);
        }

        return $classes;
    }

    /**
     * Get the definition corresponding to given system name.
     *
     * @param string $system_name
     * @phpstan-return ConcreteDefinition|null
     */
    final public function getDefinition(string $system_name): ?AbstractDefinition
    {
        return $this->getDefinitions()[$system_name] ?? null;
    }

    /**
     * Clear the definitions cache.
     */
    final public function clearDefinitionsCache(): void
    {
        $definition_class = static::getDefinitionClass();
        unset($this->definitions_data[$definition_class]);
    }

    /**
     * Get all the definitions.
     *
     * @param bool $only_active
     * @return AbstractDefinition[]
     * @phpstan-return ConcreteDefinition[]
     */
    final public function getDefinitions(bool $only_active = false): array
    {
        $definition_class = static::getDefinitionClass();
        if (!array_key_exists($definition_class, $this->definitions_data)) {
            $this->definitions_data[$definition_class] = getAllDataFromTable($definition_class::getTable());
        }

        $definitions = [];
        foreach ($this->definitions_data[$definition_class] as $definition_data) {
            if ($only_active && (bool) $definition_data['is_active'] !== true) {
                continue;
            }

            $system_name = $definition_data['system_name'];
            $definition = new $definition_class();
            $definition->getFromResultSet($definition_data);
            $definitions[$system_name] = $definition;
        }

        return $definitions;
    }
}
