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

namespace Glpi\Asset\Capacity;

use Glpi\Asset\Asset;

interface CapacityInterface
{
    /**
     * Get the capacity label.
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * Get the capacity helptext.
     *
     * This text may be defined to help explain what the capacity is
     * doing if the label is not precise enough by itself.
     *
     * @return string
     */
    public function getHelpText(): string;

    /**
     * Get configuration messages related to the capacity.
     *
     * These messages are displayed on the asset definition configuration page
     * in order to give feedeback to the user about possible extra
     * configuration steps related to external components (e.g. profiles).
     *
     * @param string $classname
     * @return array An array of messages. A message is an array with the
     *                following keys:
     *                - type: The message type (INFO, WARNING or ERROR constants)
     *                - text: The message content
     *                - link: An optional link used to wrap the message
     */
    public function getConfigurationMessages(string $classname): array;

    /**
     * Get the search options related to the capacity.
     *
     * @param string $classname
     * @return array
     */
    public function getSearchOptions(string $classname): array;

    /**
     * Get the specific rights related to the capacity.
     *
     * @param string $classname
     * @return int[]
     */
    public function getSpecificRights(): array;

    /**
     * Method executed during asset classes bootstraping.
     *
     * @param string $classname
     * @return void
     */
    public function onClassBootstrap(string $classname): void;

    /**
     * Method executed when capacity is disabled on given asset class.
     *
     * @param string $classname
     * @return void
     */
    public function onCapacityDisabled(string $classname): void;

    /**
     * Method executed during creation of an object instance (i.e. during `__construct()` method execution).
     *
     * @param Asset $object
     * @return void
     */
    public function onObjectInstanciation(Asset $object): void;
}
