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

namespace Glpi\Asset\Capacity;

use CommonDBTM;
use Glpi\Asset\Asset;
use Glpi\Asset\CapacityConfig;

interface CapacityInterface
{
    /**
     * Get the capacity label.
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * Get the description of the capacity.
     * This description is used in the capacity management interface.
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Get the icon of the capacity.
     * This icon is displayed in the capacity management interface along the label.
     *
     * @return string
     */
    public function getIcon(): string;

    /**
     * Get the capacity configuration form.
     *
     * @param string $fieldname_prefix  The field name prefix to add to the fields (`name="{$fieldname_prefix}[my_config_key]"`).
     *
     * @return string|null The configuration form in HTML format, or `null` if there is no configuration form.
     */
    public function getConfigurationForm(string $fieldname_prefix, ?CapacityConfig $current_config): ?string;

    /**
     * Get the search options related to the capacity.
     *
     * @param class-string<Asset> $classname
     * @return array
     */
    public function getSearchOptions(string $classname): array;

    /**
     * Get the specific rights related to the capacity.
     *
     * @return int[]
     */
    public function getSpecificRights(): array;

    /**
     * Get array of classes related to the capacity which should be cloned when the asset is cloned.
     * @return array
     * @phpstan-return class-string<CommonDBTM>[]
     */
    public function getCloneRelations(): array;

    /**
     * Indicates whether the capacity is used by given asset class.
     *
     * @param class-string<Asset> $classname
     * @return bool
     */
    public function isUsed(string $classname): bool;

    /**
     * Get the capacity usage description for given asset class.
     *
     * @param class-string<Asset> $classname
     * @return string
     */
    public function getCapacityUsageDescription(string $classname): string;

    /**
     * Method executed during asset classes bootstraping.
     *
     * @param class-string<Asset> $classname
     * @param CapacityConfig $config
     * @return void
     */
    public function onClassBootstrap(string $classname, CapacityConfig $config): void;

    /**
     * Method executed when capacity is enabled on given asset class.
     *
     * @param class-string<Asset> $classname
     * @param CapacityConfig $config
     * @return void
     */
    public function onCapacityEnabled(string $classname, CapacityConfig $config): void;

    /**
     * Method executed when capacity is disabled on given asset class.
     *
     * @param class-string<Asset> $classname
     * @param CapacityConfig $config
     * @return void
     */
    public function onCapacityDisabled(string $classname, CapacityConfig $config): void;

    /**
     * Method executed when capacity is updated on given asset class.
     *
     * @param class-string<Asset> $classname
     * @param CapacityConfig $old_config
     * @param CapacityConfig $new_config
     * @return void
     */
    public function onCapacityUpdated(string $classname, CapacityConfig $old_config, CapacityConfig $new_config): void;

    /**
     * Method executed during creation of an object instance (i.e. during `__construct()` method execution).
     *
     * @param Asset $object
     * @param CapacityConfig $config
     * @return void
     */
    public function onObjectInstanciation(Asset $object, CapacityConfig $config): void;
}
