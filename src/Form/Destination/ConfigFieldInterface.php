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

namespace Glpi\Form\Destination;

interface ConfigFieldInterface
{
    /**
     * Get the unique key used to set/get this field configuration in the
     * destination JSON configuration.
     *
     * @return string
     */
    public function getKey(): string;

    /**
     * Label to be displayed when configuring this field.
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * Render the input field for this configuration.
     *
     * @param array|null $config
     *
     * @return string
     */
    public function renderConfigForm(?array $config): string;

    /**
     * Apply configurated value to the given input.
     *
     * @param array      $input
     * @param array|null $config May be null if there is no configuration for
     *                           this field.
     *
     * @return array
     */
    public function applyConfiguratedValue(array $input, ?array $config): array;
}
