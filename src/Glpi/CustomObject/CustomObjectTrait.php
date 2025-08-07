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

use LogicException;
use RuntimeException;
use Toolbox;

trait CustomObjectTrait
{
    public static function getTypeName($nb = 0)
    {
        return static::getDefinition()->getTranslatedName($nb);
    }

    /**
     * @see \CommonGLPI::getSearchURL()
     */
    public static function getSearchURL($full = true)
    {
        return Toolbox::getItemTypeSearchURL(static::getDefinition()->getCustomObjectBaseClass(), $full)
            . '?class=' . static::getDefinition()->fields['system_name'];
    }

    /**
     * @see \CommonGLPI::getFormURL()
     */
    public static function getFormURL($full = true)
    {
        return Toolbox::getItemTypeFormURL(static::getDefinition()->getCustomObjectBaseClass(), $full)
            . '?class=' . static::getDefinition()->fields['system_name'];
    }

    /**
     * @see \CommonDBTM:: getIcon()
     */
    public static function getIcon()
    {
        return static::getDefinition()->getCustomObjectIcon();
    }

    /**
     * @see \CommonDBTM:: getTable()
     */
    public static function getTable($classname = null)
    {
        if (is_a($classname ?? static::class, self::class, true)) {
            return parent::getTable(self::class);
        }
        return parent::getTable($classname);
    }

    /**
     * @see \CommonDBTM:: getById()
     */
    public static function getById(?int $id)
    {
        if ($id === null) {
            return false;
        }

        $base_class        = static::class;
        $definition_object = self::getDefinitionClassInstance();

        // Load the asset definition corresponding to given asset ID
        $definition_request = [
            'INNER JOIN' => [
                $base_class::getTable() => [
                    'ON'  => [
                        $base_class::getTable()       => $definition_object::getForeignKeyField(),
                        $definition_object::getTable() => $definition_object::getIndexName(),
                    ],
                ],
            ],
            'WHERE' => [
                $base_class::getTableField($base_class::getIndexName()) => $id,
            ],
        ];

        $definition = new $definition_object();
        if (!$definition->getFromDBByRequest($definition_request)) {
            return false;
        }

        // Instanciate concrete class
        $instance = $definition->getCustomObjectClassInstance();

        if (!is_a($instance, static::class, true)) {
            throw new LogicException(); // To make PHPStan happy
        }

        if (!$instance->getFromDB($id)) {
            return false;
        }

        return $instance;
    }

    /**
     * @see \CommonDBTM:: getSystemSQLCriteria()
     */
    public static function getSystemSQLCriteria(?string $tablename = null): array
    {
        $table_prefix = $tablename !== null
            ? $tablename . '.'
            : '';

        // Keep only items from current definition must be shown.
        $criteria = [
            $table_prefix . static::getDefinition()::getForeignKeyField() => static::getDefinition()->getID(),
        ];

        // Add another layer to the array to prevent losing duplicates keys if the
        // result of the function is merged with another array.
        $criteria = [crc32(serialize($criteria)) => $criteria];

        return $criteria;
    }

    /**
     * Ensure definition input corresponds to the current concrete class.
     *
     * @param array $input
     * @return array
     */
    protected function prepareDefinitionInput(array $input): array
    {
        $definition_fkey = static::getDefinition()::getForeignKeyField();
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

    /**
     * Amend the class search options to define their concrete `itemtype`.
     *
     * @param array $search_options
     *
     * @return array
     */
    protected function amendSearchOptions(array $search_options): array
    {
        // Ensure the search engine can handle search options when multiple classes map to the same table
        foreach ($search_options as &$search_option) {
            if (
                is_array($search_option)
                && array_key_exists('table', $search_option)
                && $search_option['table'] === static::getTable()
            ) {
                // Search class could not be able to retrieve the concrete class when using `getItemTypeForTable()`,
                // so we have to define an `itemtype` here.
                $search_option['itemtype'] = static::class;
            }
        }

        return $search_options;
    }
}
