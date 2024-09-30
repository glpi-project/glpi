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

namespace Glpi\Asset;

use Change_Item;
use CommonGLPI;
use DirectoryIterator;
use Dropdown;
use Glpi\Asset\Capacity\CapacityInterface;
use Glpi\Asset\CustomFieldType\TypeInterface;
use Glpi\CustomObject\AbstractDefinition;
use Glpi\CustomObject\AbstractDefinitionManager;
use Item_Problem;
use Item_Ticket;
use ReflectionClass;
use Session;

/**
 * @extends AbstractDefinitionManager<AssetDefinition>
 */
final class AssetDefinitionManager extends AbstractDefinitionManager
{
    /**
     * Singleton instance
     */
    private static ?AssetDefinitionManager $instance = null;

    /**
     * List of available capacities.
     * @var CapacityInterface[]
     */
    private array $capacities = [];

    /**
     * Dropdown itemtypes allowed for custom field definitions.
     * @var array<string, array<class-string<\CommonDBTM>, string>>
     * @see self::getAllowedDropdownItemtypes()
     */
    private ?array $allowed_dropdown_itemtypes = null;

    /**
     * @var class-string<TypeInterface>[]|null Custom field types
     */
    private ?array $custom_field_types = null;

    /**
     * Singleton constructor
     */
    private function __construct()
    {
        // Automatically build core capacities list.
        // Would be better to do it with a DI auto-discovery feature, but it is not possible yet.
        $directory_iterator = new DirectoryIterator(__DIR__ . '/Capacity');
        /** @var \SplFileObject $file */
        foreach ($directory_iterator as $file) {
            $classname = $file->getExtension() === 'php'
                ? 'Glpi\\Asset\\Capacity\\' . $file->getBasename('.php')
                : null;
            if (
                $classname !== null
                && class_exists($classname)
                && is_subclass_of($classname, CapacityInterface::class)
                && (new ReflectionClass($classname))->isAbstract() === false
            ) {
                $this->capacities[$classname] = new $classname();
            }
        }

        $directory_iterator = new DirectoryIterator(__DIR__ . '/CustomFieldType');

        if ($this->custom_field_types === null) {
            $this->custom_field_types = [];
            /** @var \SplFileObject $file */
            foreach ($directory_iterator as $file) {
                // Compute class name with the expected namespace
                $classname = $file->getExtension() === 'php'
                    ? 'Glpi\\Asset\\CustomFieldType\\' . $file->getBasename('.php')
                    : null;

                // Validate that the class is a valid question type
                if (
                    $classname !== null
                    && class_exists($classname)
                    && is_subclass_of($classname, TypeInterface::class)
                    && (new ReflectionClass($classname))->isAbstract() === false
                ) {
                    $this->custom_field_types[] = $classname;
                }
            }
        }
    }

    /**
     * Get singleton instance
     *
     * @return AssetDefinitionManager
     */
    public static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Unset the singleton instance
     *
     * @return void
     */
    public static function unsetInstance(): void
    {
        self::$instance = null;
    }

    public static function getDefinitionClass(): string
    {
        return AssetDefinition::class;
    }

    public function getReservedSystemNames(): array
    {
        $names = [
            'Computer',
            'Monitor',
            'Software',
            'NetworkEquipment',
            'Peripheral',
            'Printer',
            'Cartridge',
            'Consumable',
            'Phone',
            'Rack',
            'Enclosure',
            'PDU',
            'PassiveDCEquipment',
            'Unmanaged',
            'Cable',
        ];

        sort($names);

        return $names;
    }

    protected function boostrapConcreteClass(AbstractDefinition $definition): void
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $capacities = $this->getAvailableCapacities();

        $asset_class_name = $definition->getCustomObjectClassName();

        // Register asset into configuration entries related to the capacities that cannot be disabled
        $config_keys = [
            'asset_types',
            'assignable_types',
            'location_types',
            'state_types',
            'ticket_types',
            'unicity_types'
        ];
        foreach ($config_keys as $config_key) {
            $CFG_GLPI[$config_key][] = $asset_class_name;
        }

        // Add type and model to dictionnary config entry
        $CFG_GLPI['dictionnary_types'][] = $definition->getAssetTypeClassName();
        $CFG_GLPI['dictionnary_types'][] = $definition->getAssetModelClassName();

        // Bootstrap capacities
        foreach ($capacities as $capacity) {
            if ($definition->hasCapacityEnabled($capacity)) {
                $capacity->onClassBootstrap($asset_class_name);
            }
        }

        // Register IITL tabs, which will only be displayed if some condition
        // are met (see the shouldDisplayTabForAsset method in
        // CommonItilObject_Item).
        CommonGLPI::registerStandardTab(
            $asset_class_name,
            Item_Ticket::class,
            51
        );
        CommonGLPI::registerStandardTab(
            $asset_class_name,
            Item_Problem::class,
            52
        );
        CommonGLPI::registerStandardTab(
            $asset_class_name,
            Change_Item::class,
            53
        );
    }

    /**
     * Autoload asset class, if requested class is a generic asset class.
     *
     * @param string $classname
     * @return void
     */
    public function autoloadClass(string $classname): void
    {
        $ns = self::getDefinitionClass()::getCustomObjectNamespace() . '\\';
        $patterns = [
            '/^' . preg_quote($ns, '/') . 'RuleDictionary([A-Za-z]+)ModelCollection$/' => 'loadConcreteModelDictionaryCollectionClass',
            '/^' . preg_quote($ns, '/') . 'RuleDictionary([A-Za-z]+)TypeCollection$/' => 'loadConcreteTypeDictionaryCollectionClass',
            '/^' . preg_quote($ns, '/') . 'RuleDictionary([A-Za-z]+)Model$/' => 'loadConcreteModelDictionaryClass',
            '/^' . preg_quote($ns, '/') . 'RuleDictionary([A-Za-z]+)Type$/' => 'loadConcreteTypeDictionaryClass',
            '/^' . preg_quote($ns, '/') . '([A-Za-z]+)Model$/' => 'loadConcreteModelClass',
            '/^' . preg_quote($ns, '/') . '([A-Za-z]+)Type$/' => 'loadConcreteTypeClass',
            '/^' . preg_quote($ns, '/') . '([A-Za-z]+)$/' => 'loadConcreteClass',
        ];

        foreach ($patterns as $pattern => $load_function) {
            if (preg_match($pattern, $classname) === 1) {
                $system_name = preg_replace($pattern, '$1', $classname);
                $definition  = $this->getDefinition($system_name);

                if ($definition === null) {
                    return;
                }

                $this->$load_function($definition);
                break;
            }
        }
    }

    /**
     * Get the classes names of all assets models concrete classes.
     *
     * @param bool $with_namespace
     * @return array
     */
    public function getAssetModelsClassesNames(bool $with_namespace = true): array
    {
        $classes = [];

        foreach ($this->getDefinitions() as $definition) {
            if (!$definition->isActive()) {
                continue;
            }
            $classes[] = $definition->getAssetModelClassName($with_namespace);
        }

        return $classes;
    }

    /**
     * Get the classes names of all assets types concrete classes.
     *
     * @param bool $with_namespace
     * @return array
     */
    public function getAssetTypesClassesNames(bool $with_namespace = true): array
    {
        $classes = [];

        foreach ($this->getDefinitions() as $definition) {
            if (!$definition->isActive()) {
                continue;
            }
            $classes[] = $definition->getAssetTypeClassName($with_namespace);
        }

        return $classes;
    }

    /**
     * Returns available capacities instances.
     *
     * @return CapacityInterface[]
     */
    public function getAvailableCapacities(): array
    {
        return $this->capacities;
    }

    /**
     * Returns the dropdown itemtypes allowed for custom field definitions.
     * @param bool $flatten If true, returns a flat array of itemtypes rather than separated by category.
     * @return array<string, array<class-string<\CommonDBTM>, string>>
     */
    public function getAllowedDropdownItemtypes($flatten = false): array
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if ($this->allowed_dropdown_itemtypes === null) {
            $this->allowed_dropdown_itemtypes = [
                _n('Asset', "Assets", Session::getPluralNumber()) => array_combine(
                    $CFG_GLPI['asset_types'],
                    array_map(static fn($t) => $t::getTypeName(1), $CFG_GLPI['asset_types'])
                ),
            ];
            $this->allowed_dropdown_itemtypes = array_merge_recursive($this->allowed_dropdown_itemtypes, Dropdown::getStandardDropdownItemTypes());
        }

        if ($flatten) {
            $itemtypes = [];
            foreach ($this->allowed_dropdown_itemtypes as $category) {
                $itemtypes = array_merge($itemtypes, $category);
            }
            return $itemtypes;
        }

        return $this->allowed_dropdown_itemtypes;
    }

    /**
     * Get the list of field types available
     * @return class-string<TypeInterface>[]
     */
    public function getCustomFieldTypes(): array
    {
        return $this->custom_field_types;
    }

    /**
     * Return capacity instance.
     *
     * @param string $classname
     * @return CapacityInterface|null
     */
    public function getCapacity(string $classname): ?CapacityInterface
    {
        return $this->capacities[$classname] ?? null;
    }

    private function loadConcreteClass(AssetDefinition $definition): void
    {
        $rightname = $definition->getCustomObjectRightname();

        // Static properties must be defined in each concrete class otherwise they will be shared
        // accross all concrete classes, and so would be overriden by the values from the last loaded class.
        eval(<<<PHP
namespace Glpi\\CustomAsset;

use Glpi\\Asset\\Asset;
use Glpi\\Asset\\AssetDefinition;

final class {$definition->getAssetClassName(false)} extends Asset {
    protected static AssetDefinition \$definition;
    public static \$rightname = '{$rightname}';
}
PHP
        );

        // Set the definition of the concrete class using reflection API.
        // It permits to directly store a pointer to the definition on the object without having
        // to make the property publicly writable.
        $reflected_class = new ReflectionClass($definition->getAssetClassName());
        $reflected_class->setStaticPropertyValue('definition', $definition);
    }

    /**
     * Load asset model concrete class.
     *
     * @param AssetDefinition $definition
     * @return void
     * @used-by self::autoloadClass()
     */
    private function loadConcreteModelClass(AssetDefinition $definition): void
    {
        eval(<<<PHP
namespace Glpi\\CustomAsset;

use Glpi\\Asset\\AssetModel;
use Glpi\\Asset\\AssetDefinition;

final class {$definition->getAssetModelClassName(false)} extends AssetModel {
    protected static AssetDefinition \$definition;
}
PHP
        );

        $reflected_class = new ReflectionClass($definition->getAssetModelClassName());
        $reflected_class->setStaticPropertyValue('definition', $definition);
    }

    /**
     * Load asset type concrete class.
     *
     * @param AssetDefinition $definition
     * @return void
     * @used-by self::autoloadClass()
     */
    private function loadConcreteTypeClass(AssetDefinition $definition): void
    {
        eval(<<<PHP
namespace Glpi\\CustomAsset;

use Glpi\\Asset\\AssetType;
use Glpi\\Asset\\AssetDefinition;

final class {$definition->getAssetTypeClassName(false)} extends AssetType {
    protected static AssetDefinition \$definition;
}
PHP
        );

        $reflected_class = new ReflectionClass($definition->getAssetTypeClassName());
        $reflected_class->setStaticPropertyValue('definition', $definition);
    }

    private function loadConcreteModelDictionaryClass(AssetDefinition $definition): void
    {
        eval(<<<PHP
namespace Glpi\\CustomAsset;

use Glpi\\Asset\\AssetDefinition;
use Glpi\\Asset\\RuleDictionaryModel;

final class {$definition->getAssetModelDictionaryClassName(false)} extends RuleDictionaryModel
{
    protected static AssetDefinition \$definition;
}
PHP
        );

        $reflected_class = new ReflectionClass($definition->getAssetModelDictionaryClassName());
        $reflected_class->setStaticPropertyValue('definition', $definition);
    }

    private function loadConcreteTypeDictionaryClass(AssetDefinition $definition): void
    {
        eval(<<<PHP
namespace Glpi\\CustomAsset;

use Glpi\\Asset\\AssetDefinition;
use Glpi\\Asset\\RuleDictionaryType;

final class {$definition->getAssetTypeDictionaryClassName(false)} extends RuleDictionaryType
{
    protected static AssetDefinition \$definition;
}
PHP
        );

        $reflected_class = new ReflectionClass($definition->getAssetTypeDictionaryClassName());
        $reflected_class->setStaticPropertyValue('definition', $definition);
    }

    private function loadConcreteModelDictionaryCollectionClass(AssetDefinition $definition): void
    {
        eval(<<<PHP
namespace Glpi\\CustomAsset;

use Glpi\\Asset\\AssetDefinition;
use Glpi\\Asset\\RuleDictionaryModelCollection;

final class {$definition->getAssetModelDictionaryCollectionClassName(false)} extends RuleDictionaryModelCollection
{
    protected static AssetDefinition \$definition;
}
PHP
        );

        $reflected_class = new ReflectionClass($definition->getAssetModelDictionaryCollectionClassName());
        $reflected_class->setStaticPropertyValue('definition', $definition);
    }

    private function loadConcreteTypeDictionaryCollectionClass(AssetDefinition $definition): void
    {
        eval(<<<PHP
namespace Glpi\\CustomAsset;

use Glpi\\Asset\\AssetDefinition;
use Glpi\\Asset\\RuleDictionaryTypeCollection;

final class {$definition->getAssetTypeDictionaryCollectionClassName(false)} extends RuleDictionaryTypeCollection
{
    protected static AssetDefinition \$definition;
}
PHP
        );

        $reflected_class = new ReflectionClass($definition->getAssetTypeDictionaryCollectionClassName());
        $reflected_class->setStaticPropertyValue('definition', $definition);
    }
}
