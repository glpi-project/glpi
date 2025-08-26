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

namespace Glpi\Asset;

use CommonType;
use RuntimeException;
use Toolbox;

abstract class AssetType extends CommonType
{
    /**
     * Asset definition system name.
     *
     * Must be defined here to make PHPStan happy (see https://github.com/phpstan/phpstan/issues/8808).
     * Must be defined by child class too to ensure that assigning a value to this property will affect
     * each child classe independently.
     */
    protected static string $definition_system_name;

    /**
     * Get the asset definition related to concrete class.
     *
     * @return AssetDefinition
     */
    public static function getDefinition(): AssetDefinition
    {
        $definition = AssetDefinitionManager::getInstance()->getDefinition(static::$definition_system_name);
        if (!($definition instanceof AssetDefinition)) {
            throw new RuntimeException('Asset definition is expected to be defined in concrete class.');
        }

        return $definition;
    }

    public static function getTypeName($nb = 0)
    {
        return sprintf(_n('%s type', '%s types', $nb), static::getDefinition()->getTranslatedName());
    }

    public static function getIcon()
    {
        return static::getDefinition()->getCustomObjectIcon();
    }

    public static function getTable($classname = null)
    {
        if (is_a($classname ?? static::class, self::class, true)) {
            return parent::getTable(self::class);
        }
        return parent::getTable($classname);
    }

    public static function getSearchURL($full = true)
    {
        return Toolbox::getItemTypeSearchURL(self::class, $full) . '?class=' . static::getDefinition()->fields['system_name'];
    }

    public static function getFormURL($full = true)
    {
        return Toolbox::getItemTypeFormURL(self::class, $full) . '?class=' . static::getDefinition()->fields['system_name'];
    }

    /**
     * Retrieve an item from the database
     *
     * @param int|null $id ID of the item to get
     *
     * @return self|false
     */

    public static function getById(?int $id)
    {
        if ($id === null) {
            return false;
        }

        // Load the asset definition corresponding to given asset type ID
        $definition_request = [
            'INNER JOIN' => [
                self::getTable()  => [
                    'ON'  => [
                        self::getTable()            => AssetDefinition::getForeignKeyField(),
                        AssetDefinition::getTable() => AssetDefinition::getIndexName(),
                    ],
                ],
            ],
            'WHERE' => [
                self::getTableField(self::getIndexName()) => $id,
            ],
        ];
        $definition = new AssetDefinition();
        if (!$definition->getFromDBByRequest($definition_request)) {
            return false;
        }

        // Instanciate concrete class
        $asset_type = $definition->getAssetTypeClassInstance();
        if (!$asset_type->getFromDB($id)) {
            return false;
        }

        return $asset_type;
    }

    public static function getSystemSQLCriteria(?string $tablename = null): array
    {
        return static::getDefinition()->getSystemSQLCriteriaForConcreteClass($tablename);
    }

    public function prepareInputForAdd($input)
    {
        return $this->prepareDefinitionInput($input);
    }

    public function prepareInputForUpdate($input)
    {
        return $this->prepareDefinitionInput($input);
    }

    /**
     * Ensure definition input corresponds to the current concrete class.
     *
     * @param array $input
     * @return array
     */
    private function prepareDefinitionInput(array $input): array
    {
        $definition_fkey = AssetDefinition::getForeignKeyField();
        $definition_id   = static::getDefinition()->getID();

        if (
            array_key_exists($definition_fkey, $input)
            && (int) $input[$definition_fkey] !== $definition_id
        ) {
            throw new RuntimeException('Definition does not match the current concrete class.');
        }

        if (
            !$this->isNewItem()
            && (int) $this->fields[$definition_fkey] !== $definition_id
        ) {
            throw new RuntimeException('Definition cannot be changed.');
        }

        $input[$definition_fkey] = $definition_id;

        return $input;
    }

    public function rawSearchOptions()
    {
        // Get parent search options, but skip the ones from the immediate CommonDCModelDropdown parent
        $options = parent::rawSearchOptions();

        foreach ($options as &$option) {
            if (
                is_array($option)
                && array_key_exists('table', $option)
                && $option['table'] === static::getTable()
            ) {
                // Search class could not be able to retrieve the concrete class when using `getItemTypeForTable()`,
                // so we have to define an `itemtype` here.
                $option['itemtype'] = static::class;
            }
        }

        return $options;
    }
}
