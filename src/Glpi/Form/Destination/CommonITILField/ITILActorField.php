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

namespace Glpi\Form\Destination\CommonITILField;

use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\AnswersSet;
use Glpi\Form\Destination\AbstractConfigField;
use Glpi\Form\Form;
use InvalidArgumentException;
use Override;

abstract class ITILActorField extends AbstractConfigField
{
    abstract public function getAllowedQuestionType(): string;
    abstract public function getActorType(): string;

    public function getAllowedActorTypes(): array
    {
        return (new ($this->getAllowedQuestionType())())->getAllowedActorTypes();
    }

    #[Override]
    public function renderConfigForm(
        Form $form,
        JsonFieldInterface $config,
        string $input_name,
        array $display_options
    ): string {
        if (!$config instanceof ITILActorFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        $specific_actors = [];
        foreach ($config->getSpecificITILActorsIDs() ?? [] as $itemtype => $ids) {
            $specific_actors[getForeignKeyFieldForItemType($itemtype)] = $ids;
        }

        $twig = TemplateRenderer::getInstance();
        return $twig->render('pages/admin/form/itil_config_fields/itilactor.html.twig', [
            // Possible configuration constant that will be used to to hide/show additional fields
            'CONFIG_SPECIFIC_VALUE'  => ITILActorFieldStrategy::SPECIFIC_VALUES->value,
            'CONFIG_SPECIFIC_ANSWER' => ITILActorFieldStrategy::SPECIFIC_ANSWERS->value,

            // General display options
            'options' => $display_options,

            // Specific additional config for SPECIFIC_VALUES strategy
            'specific_value_extra_field' => [
                'aria_label'      => __("Select actors..."),
                'values'          => $specific_actors,
                'input_name'      => $input_name . "[" . ITILActorFieldConfig::SPECIFIC_ITILACTORS_IDS . "]",
                'allowed_types'   => $this->getAllowedActorTypes(),
            ],

            // Specific additional config for SPECIFIC_ANSWERS strategy
            'specific_answer_extra_field' => [
                'aria_label'      => __("Select questions..."),
                'values'          => $config->getSpecificQuestionIds() ?? [],
                'input_name'      => $input_name . "[" . ITILActorFieldConfig::SPECIFIC_QUESTION_IDS . "]",
                'possible_values' => $this->getITILActorQuestionsValuesForDropdown($form),
            ],
        ]);
    }

    #[Override]
    public function applyConfiguratedValueToInputUsingAnswers(
        JsonFieldInterface $config,
        array $input,
        AnswersSet $answers_set
    ): array {
        if (!$config instanceof ITILActorFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        // Compute value according to strategies
        foreach ($config->getStrategies() as $strategy) {
            $itilactors_ids = $strategy->getITILActorsIDs($this, $config, $answers_set);

            if (!empty($itilactors_ids)) {
                foreach ($itilactors_ids as $itemtype => $ids) {
                    foreach ($ids as $id) {
                        $input['_actors'][$this->getActorType()][] = [
                            'itemtype' => $itemtype,
                            'items_id' => $id,
                        ];
                    }
                }
            }
        }

        return $input;
    }

    #[Override]
    public function prepareInput(array $input): array
    {
        $input = parent::prepareInput($input);

        if (!isset($input[$this->getKey()][ITILActorFieldConfig::STRATEGIES])) {
            return $input;
        }

        // Ensure that itilactors_ids is an array
        if (!is_array($input[$this->getKey()][ITILActorFieldConfig::SPECIFIC_ITILACTORS_IDS] ?? null)) {
            $input[$this->getKey()][ITILActorFieldConfig::SPECIFIC_ITILACTORS_IDS] = null;
        } else {
            $input[$this->getKey()][ITILActorFieldConfig::SPECIFIC_ITILACTORS_IDS] = array_reduce(
                $input[$this->getKey()][ITILActorFieldConfig::SPECIFIC_ITILACTORS_IDS],
                function ($carry, $value) {
                    $parts = explode("-", $value);
                    $carry[getItemtypeForForeignKeyField($parts[0])][] = (int) $parts[1];
                    return $carry;
                },
                []
            );
        }

        // Ensure that question_ids is an array
        if (!is_array($input[$this->getKey()][ITILActorFieldConfig::SPECIFIC_QUESTION_IDS] ?? null)) {
            $input[$this->getKey()][ITILActorFieldConfig::SPECIFIC_QUESTION_IDS] = null;
        }

        return $input;
    }

    public function getStrategiesForDropdown(): array
    {
        $values = [];
        foreach (ITILActorFieldStrategy::cases() as $strategies) {
            $values[$strategies->value] = $strategies->getLabel($this->getLabel());
        }
        return $values;
    }

    private function getITILActorQuestionsValuesForDropdown(Form $form): array
    {
        return array_reduce(
            $form->getQuestionsByType($this->getAllowedQuestionType()),
            function ($carry, $question) {
                $carry[$question->getId()] = $question->fields['name'];
                return $carry;
            },
            []
        );
    }

    #[Override]
    public function canHaveMultipleStrategies(): bool
    {
        return true;
    }
}
