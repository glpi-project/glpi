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

namespace Glpi\Asset;

use RuleDictionnaryDropdownCollection;

abstract class RuleDictionaryTypeCollection extends RuleDictionnaryDropdownCollection
{
    /**
     * Asset definition.
     *
     * Must be defined here to make PHPStan happy (see https://github.com/phpstan/phpstan/issues/8808).
     * Must be defined by child class too to ensure that assigning a value to this property will affect
     * each child classe independently.
     */
    protected static AssetDefinition $definition;

    /**
     * Get the asset definition related to concrete class.
     *
     * @return AssetDefinition
     */
    public static function getDefinition(): AssetDefinition
    {
        if (!(static::$definition instanceof AssetDefinition)) {
            throw new \RuntimeException('Asset definition is expected to be defined in concrete class.');
        }

        return static::$definition;
    }

    public function __construct()
    {
        $this->item_table  = static::getDefinition()->getAssetTypeClassName()::getTable();
        $this->menu_option = sprintf('model.%s', static::getDefinition()->fields['system_name']);
    }

    public function getTitle()
    {
        return sprintf(__('Dictionary of %s'), static::getDefinition()->getAssetTypeClassName()::getTypeName());
    }
}
