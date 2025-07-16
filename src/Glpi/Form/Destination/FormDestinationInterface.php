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

namespace Glpi\Form\Destination;

use CommonDBTM;
use Exception;
use Glpi\Form\AnswersSet;
use Glpi\Form\Export\Context\DatabaseMapper;
use Glpi\Form\Export\Serializer\DynamicExportDataField;
use Glpi\Form\Form;

interface FormDestinationInterface
{
    /**
     * Create one or multiple items for a given form and its answers
     *
     * @param Form       $form
     * @param AnswersSet $answers_set
     * @param array      $config
     *
     * @return CommonDBTM[]
     *
     * @throws Exception Must be thrown if the item can't be created
     */
    public function createDestinationItems(
        Form $form,
        AnswersSet $answers_set,
        array $config,
    ): array;

    /**
     * Post creation processing for destination items.
     *
     * This method is called after all destination items have been created.
     *
     * @param Form                     $form
     * @param AnswersSet               $answers_set
     * @param FormDestination          $destination
     * @param array<int, CommonDBTM[]> $created_items Array of created items, indexed by destination ID.
     *
     * @return void
     */
    public function postCreateDestinationItems(
        Form $form,
        AnswersSet $answers_set,
        FormDestination $destination,
        array $created_items,
    ): void;


    /**
     * Render the configuration form for this destination type.
     *
     * @param Form  $form
     * @param FormDestination $destination
     * @param array $config
     * @return string The rendered HTML content
     */
    public function renderConfigForm(
        Form $form,
        FormDestination $destination,
        array $config
    ): string;

    /**
     * If true, the config form will be populated with a complete layout that
     * contains the actions buttons and some preset margins.
     *
     * If false, the layout will be empty and renderConfigForm() will have the
     * full responsability of including the actions buttons.
     */
    public function useDefaultConfigLayout(): bool;

    /**
     * Used to ordered items (lowest = first, highest = last)
     */
    public function getWeight(): int;

    public function getLabel(): string;

    /**
     * @return string Fully qualified tabler icon name (e.g. ti ti-user)
     */
    public function getIcon(): string;

    public function exportDynamicConfig(array $config): DynamicExportDataField;

    public static function prepareDynamicConfigDataForImport(
        array $config,
        DatabaseMapper $mapper,
    ): array;
}
