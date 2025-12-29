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

namespace Glpi\Form\Destination\CommonITILField;

use Glpi\Form\AnswersSet;
use Glpi\Form\Form;

/**
 * Interface for SLM field strategies.
 *
 * This interface allows plugins to implement custom strategies for SLA/OLA fields
 * by registering them through FormDestinationManager::registerPluginSLMFieldStrategy().
 */
interface SLMFieldStrategyInterface
{
    /**
     * Get the unique key identifying this strategy.
     *
     * @return string
     */
    public function getKey(): string;

    /**
     * Get the human-readable label for this strategy.
     *
     * @param SLMField $field The SLM field instance
     * @return string
     */
    public function getLabel(SLMField $field): string;

    /**
     * Apply this strategy to the input array.
     *
     * This is the main method that strategies must implement to modify the ITIL object input.
     * Strategies have full control over how they modify the input array.
     *
     * @param SLMField $field The SLM field instance
     * @param SLMFieldConfig $config The field configuration
     * @param array<string, mixed> $input The current input array
     * @param AnswersSet $answers_set The form answers
     * @return array<string, mixed> The modified input array
     */
    public function applyStrategyToInput(
        SLMField $field,
        SLMFieldConfig $config,
        array $input,
        AnswersSet $answers_set
    ): array;

    /**
     * Render the extra configuration fields for this strategy.
     *
     * The rendered HTML will be shown/hidden based on the selected strategy
     * using the data-glpi-itildestination-field-config-display-condition attribute.
     *
     * @param Form $form The form being configured
     * @param SLMField $field The SLM field instance
     * @param SLMFieldConfig $config The current configuration
     * @param string $input_name The base input name for form fields
     * @param array<string, mixed> $display_options Display options for rendering
     * @return string The rendered HTML
     */
    public function renderExtraConfigFields(
        Form $form,
        SLMField $field,
        SLMFieldConfig $config,
        string $input_name,
        array $display_options
    ): string;

    /**
     * Get the configuration keys used by this strategy for extra data storage.
     *
     * These keys will be extracted from form input and stored in the config's extra_data.
     *
     * @return array<string>
     */
    public function getExtraConfigKeys(): array;

    /**
     * Get the weight of this strategy for ordering in dropdowns.
     *
     * Lower values appear first.
     *
     * @return int
     */
    public function getWeight(): int;
}
