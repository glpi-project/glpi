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
use Glpi\Form\QuestionType\QuestionTypeAssignee;
use Glpi\Form\QuestionType\QuestionTypeItem;
use Glpi\Form\QuestionType\QuestionTypeObserver;
use Group;
use InvalidArgumentException;
use Override;
use User;

class ValidationField extends AbstractConfigField
{
    #[Override]
    public function getLabel(): string
    {
        return _n('Validation', 'Validations', 1);
    }

    #[Override]
    public function getConfigClass(): string
    {
        return ValidationFieldConfig::class;
    }

    #[Override]
    public function renderConfigForm(
        Form $form,
        JsonFieldInterface $config,
        string $input_name,
        array $display_options
    ): string {
        if (!$config instanceof ValidationFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        $twig = TemplateRenderer::getInstance();
        return $twig->render('pages/admin/form/itil_config_fields/validation.html.twig', [
            // Possible configuration constant that will be used to to hide/show additional fields
            'CONFIG_SPECIFIC_ACTORS'  => ValidationFieldStrategy::SPECIFIC_ACTORS->value,
            'CONFIG_SPECIFIC_ANSWERS' => ValidationFieldStrategy::SPECIFIC_ANSWERS->value,

            // General display options
            'options' => $display_options,

            // Main config field
            'main_config_field' => [
                'label'           => $this->getLabel(),
                'value'           => $config->getStrategy()->value,
                'input_name'      => $input_name . "[" . ValidationFieldConfig::STRATEGY . "]",
                'possible_values' => $this->getMainConfigurationValuesforDropdown(),
            ],

            // Specific additional config for SPECIFIC_ACTORS strategy
            'specific_values_extra_field' => [
                'values'        => $config->getSpecificActors() ?? [],
                'input_name'    => $input_name . "[" . ValidationFieldConfig::SPECIFIC_ACTORS . "]",
                'allowed_types' => [User::class, Group::class],
                'aria_label'    => __("Select actors..."),
            ],

            // Specific additional config for SPECIFIC_ANSWERS strategy
            'specific_answers_extra_field' => [
                'values'          => $config->getSpecificQuestionIds() ?? [],
                'input_name'      => $input_name . "[" . ValidationFieldConfig::QUESTION_IDS . "]",
                'possible_values' => $this->getActorsQuestionsValuesForDropdown($form),
                'aria_label'      => __("Select questions..."),
            ],
        ]);
    }

    #[Override]
    public function applyConfiguratedValueToInputUsingAnswers(
        JsonFieldInterface $config,
        array $input,
        AnswersSet $answers_set
    ): array {
        if (!$config instanceof ValidationFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        // Compute value according to strategy
        $validations = $config->getStrategy()->getValidation($config, $answers_set);

        if (!empty($validations)) {
            foreach ($validations as $validation) {
                $input['_add_validation'] = 0;
                $input['_validation_targets'][] = [
                    'validatortype'   => $validation['itemtype'],
                    'itemtype_target' => $validation['itemtype'],
                    'items_id_target' => $validation['items_id'],
                ];
            }
        }

        return $input;
    }

    #[Override]
    public function getDefaultConfig(Form $form): ValidationFieldConfig
    {
        return new ValidationFieldConfig(
            ValidationFieldStrategy::NO_VALIDATION
        );
    }

    private function getMainConfigurationValuesforDropdown(): array
    {
        $values = [];
        foreach (ValidationFieldStrategy::cases() as $strategies) {
            $values[$strategies->value] = $strategies->getLabel();
        }
        return $values;
    }

    private function getActorsQuestionsValuesForDropdown(Form $form): array
    {
        $values = [];
        $questions = array_merge(
            $form->getQuestionsByType(QuestionTypeItem::class),
            $form->getQuestionsByType(QuestionTypeObserver::class),
            $form->getQuestionsByType(QuestionTypeAssignee::class),
        );
        $allowed_itemtypes = [
            User::class,
            Group::class,
        ];

        foreach ($questions as $question) {
            // Only keep questions that are allowed
            if (
                !($question->getQuestionType() instanceof AbstractQuestionTypeActors)
                && !in_array((new QuestionTypeItem())->getDefaultValueItemtype($question), $allowed_itemtypes)
            ) {
                continue;
            }

            $values[$question->getId()] = $question->fields['name'];
        }

        return $values;
    }

    #[Override]
    public function getWeight(): int
    {
        return 30;
    }

    #[Override]
    public function prepareInput(array $input): array
    {
        $input = parent::prepareInput($input);

        // Ensure that question_ids is an array
        if (!is_array($input[$this->getKey()][ValidationFieldConfig::QUESTION_IDS] ?? null)) {
            unset($input[$this->getKey()][ValidationFieldConfig::QUESTION_IDS]);
        }

        // Ensure that specific_actors is an array
        if (!is_array($input[$this->getKey()][ValidationFieldConfig::SPECIFIC_ACTORS] ?? null)) {
            unset($input[$this->getKey()][ValidationFieldConfig::SPECIFIC_ACTORS]);
        }

        // Format specific_actors
        if (
            isset($input[$this->getKey()][ValidationFieldConfig::SPECIFIC_ACTORS])
            && is_array($input[$this->getKey()][ValidationFieldConfig::SPECIFIC_ACTORS])
        ) {
            $available_actor_types = [
                User::class => User::getForeignKeyField(),
                Group::class => Group::getForeignKeyField(),
            ];
            $actors = [];

            foreach ($input[$this->getKey()][ValidationFieldConfig::SPECIFIC_ACTORS] as $actor) {
                $actor_parts = explode('-', $actor);
                if (
                    count($actor_parts) !== 2
                    || !in_array($actor_parts[0], array_values($available_actor_types))
                    || !is_numeric($actor_parts[1])
                ) {
                    continue;
                }

                if (!isset($actors[$actor_parts[0]])) {
                    $actors[$actor_parts[0]] = [];
                }

                $actors[$actor_parts[0]][] = $actor_parts[1];
            }

            $input[$this->getKey()][ValidationFieldConfig::SPECIFIC_ACTORS] = $actors;
        }

        return $input;
    }
}
