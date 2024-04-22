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

namespace Glpi\Form\AccessControl\ControlType;

use JsonConfigInterface;
use Glpi\Form\Form;
use Glpi\Session\SessionInfo;

interface ControlTypeInterface
{
    /**
     * Get the label of this control type.
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * Icon to display for this control type (css classes).
     *
     * @return string
     */
    public function getIcon(): string;

    /**
     * Get the free json config config class name for this object.
     *
     * @return string Class name which implements JsonConfigInterface
     */
    public function getConfigClass(): string;

    /**
     * Render the configuration form of this control type.
     *
     * @param JsonConfigInterface $config
     *
     * @return string Rendered content
     */
    public function renderConfigForm(JsonConfigInterface $config): string;

    /**
     * Get weight of this control type (used to sort controls types).
     *
     * @return int
     */
    public function getWeight(): int;

    /**
     * Create a new config object from input data.
     *
     * @param array $input
     *
     * @return JsonConfigInterface
     */
    public function createConfigFromUserInput(array $input): JsonConfigInterface;

    /**
     * Check if unauthenticated users are allowed to answer the given form.
     *
     * @param JsonConfigInterface $config
     *
     * @return bool
     */
    public function allowUnauthenticatedUsers(JsonConfigInterface $config): bool;

    /**
     * Check if the current user can answer the given form.
     *
     * @param JsonConfigInterface $config
     * @param SessionInfo             $session
     *
     * @return bool
     */
    public function canAnswer(
        JsonConfigInterface $config,
        \Glpi\Session\SessionInfo $session
    ): bool;
}
