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

namespace Glpi\Form\Destination\CommonITILField;

use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\AnswersSet;
use Glpi\Form\Destination\AbstractConfigField;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\AbstractQuestionTypeActors;
use InvalidArgumentException;
use Override;

abstract class ITILActorField extends AbstractConfigField
{
    abstract public function getAllowedQuestionType(): string;
    abstract public function getActorType(): string;

    #[Override]
    public function getConfigClass(): string
    {
        return ITILActorFieldConfig::class;
    }

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

            // Main config field
            'main_config_field' => [
                'label'           => $this->getLabel(),
                'value'           => $config->getStrategy()->value,
                'input_name'      => $input_name . "[" . ITILActorFieldConfig::STRATEGY . "]",
                'possible_values' => $this->getMainConfigurationValuesforDropdown(),
            ],

            // Specific additional config for SPECIFIC_VALUES strategy
            'specific_value_extra_field' => [
                'aria_label'      => __("Select actors..."),
                'values'          => $specific_actors,
                'input_name'      => $input_name . "[" . ITILActorFieldConfig::ITILACTORS_IDS . "]",
                'allowed_types'   => $this->getAllowedActorTypes(),
            ],

            // Specific additional config for SPECIFIC_ANSWERS strategy
            'specific_answer_extra_field' => [
                'aria_label'      => __("Select questions..."),
                'values'          => $config->getSpecificQuestionIds() ?? [],
                'input_name'      => $input_name . "[" . ITILActorFieldConfig::QUESTION_IDS . "]",
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

        // Compute value according to strategy
        $itilactors_ids = $config->getStrategy()->getITILActorsIDs(
            $this,
            $config,
            $answers_set
        );

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

        return $input;
    }

    #[Override]
    public function getDefaultConfig(Form $form): ITILActorFieldConfig
    {
        return new ITILActorFieldConfig(
            ITILActorFieldStrategy::FROM_TEMPLATE,
        );
    }

    #[Override]
    public function prepareInput(array $input): array
    {
        $input = parent::prepareInput($input);

        // Ensure that itilactors_ids is an array
        if (!is_array($input[$this->getKey()][ITILActorFieldConfig::ITILACTORS_IDS] ?? null)) {
            unset($input[$this->getKey()][ITILActorFieldConfig::ITILACTORS_IDS]);
        } else {
            $input[$this->getKey()][ITILActorFieldConfig::ITILACTORS_IDS] = array_reduce(
                $input[$this->getKey()][ITILActorFieldConfig::ITILACTORS_IDS],
                function ($carry, $value) {
                    $parts = explode("-", $value);
                    $carry[getItemtypeForForeignKeyField($parts[0])][] = (int) $parts[1];
                    return $carry;
                },
                []
            );
        }

        // Ensure that question_ids is an array
        if (!is_array($input[$this->getKey()][ITILActorFieldConfig::QUESTION_IDS] ?? null)) {
            unset($input[$this->getKey()][ITILActorFieldConfig::QUESTION_IDS]);
        }

        return $input;
    }

    private function getMainConfigurationValuesforDropdown(): array
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
}
