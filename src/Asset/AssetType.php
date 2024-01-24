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

use CommonType;
use Toolbox;

abstract class AssetType extends CommonType
{
    /**
     * Get the asset definition related to concrete class.
     *
     * @return AssetDefinition
     */
    public static function getDefinition(): AssetDefinition
    {
        $definition = static::$definition ?? null;

        if (!($definition instanceof AssetDefinition)) {
            throw new \RuntimeException('Asset definition is expected to be defined in concrete class.');
        }

        return $definition;
    }

    public static function getTypeName($nb = 0)
    {
        return sprintf(_n('%s type', '%s types', $nb), static::getDefinition()->getTranslatedName());
    }

    public static function getIcon()
    {
        return static::getDefinition()->getAssetsIcon();
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
        return Toolbox::getItemTypeSearchURL(self::class, $full) . '?class=' . static::getDefinition()->getAssetClassName(false);
    }

    public static function getFormURL($full = true)
    {
        return Toolbox::getItemTypeFormURL(self::class, $full) . '?class=' . static::getDefinition()->getAssetClassName(false);
    }

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
                    ]
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
        $asset_type_class = $definition->getAssetTypeClassName(true);
        $asset_type = new $asset_type_class();
        if (!$asset_type->getFromDB($id)) {
            return false;
        }

        return $asset_type;
    }

    public static function getSystemSQLCriteria(?string $tablename = null): array
    {
        $table_prefix = $tablename !== null
            ? $tablename . '.'
            : '';

        // Keep only items from current definition must be shown.
        $criteria = [
            $table_prefix . AssetDefinition::getForeignKeyField() => static::getDefinition()->getID(),
        ];

        // Add another layer to the array to prevent losing duplicates keys if the
        // result of the function is merged with another array.
        $criteria = [crc32(serialize($criteria)) => $criteria];

        return $criteria;
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
            && (int)$input[$definition_fkey] !== $definition_id
        ) {
            throw new \RuntimeException('Asset definition does not match the current concrete class.');
        }

        if (
            !$this->isNewItem()
            && (int)$this->fields[$definition_fkey] !== $definition_id
        ) {
            throw new \RuntimeException('Asset definition cannot be changed.');
        }

        $input[$definition_fkey] = $definition_id;

        return $input;
    }
}
