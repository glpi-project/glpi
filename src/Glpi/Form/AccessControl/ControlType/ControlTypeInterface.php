<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\AccessControl\AccessVote;
use Glpi\Form\AccessControl\FormAccessControl;
use Glpi\Form\AccessControl\FormAccessParameters;
use Glpi\Form\Export\Context\DatabaseMapper;
use Glpi\Form\Export\Serializer\DynamicExportDataField;
use Glpi\Form\Form;

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
     * Get a new instance of the config object for this control type.
     *
     * @return JsonFieldInterface
     */
    public function getConfig(): JsonFieldInterface;

    /**
     * Get the warnings for the given form.
     *
     * @param  Form $form
     * @return string[]
     */
    public function getWarnings(Form $form): array;

    /**
     * Render the configuration form of this control type.
     *
     * @param FormAccessControl $access_control
     *
     * @return string Rendered content
     */
    public function renderConfigForm(FormAccessControl $access_control): string;

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
     * @return JsonFieldInterface
     */
    public function createConfigFromUserInput(array $input): JsonFieldInterface;


    /**
     * Check if the current user can answer the given form.
     */
    public function canAnswer(
        Form $form,
        JsonFieldInterface $config,
        FormAccessParameters $parameters
    ): AccessVote;

    /**
     * Define if an unauthenticated user can view the form.
     *
     * @param JsonFieldInterface $config
     * @return bool
     */
    public function allowUnauthenticated(JsonFieldInterface $config): bool;

    public function exportDynamicConfig(
        JsonFieldInterface $config
    ): DynamicExportDataField;

    public static function prepareDynamicConfigDataForImport(
        array $config,
        DatabaseMapper $mapper,
    ): array;
}
