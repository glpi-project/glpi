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

use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\AnswersSet;
use Glpi\Form\Destination\AbstractCommonITILFormDestination;
use Glpi\Form\Destination\AbstractConfigField;
use Glpi\Form\Destination\FormDestination;
use Glpi\Form\Export\Context\DatabaseMapper;
use Glpi\Form\Form;
use Glpi\Form\Migration\DestinationFieldConverterInterface;
use Glpi\Form\Migration\FormMigration;
use Glpi\Form\Question;
use Glpi\Form\QuestionType\QuestionTypeRequestType;
use InvalidArgumentException;
use Override;
use Ticket;

final class RequestTypeField extends AbstractConfigField implements DestinationFieldConverterInterface
{
    #[Override]
    public function getLabel(): string
    {
        return __("Request type");
    }

    #[Override]
    public function getConfigClass(): string
    {
        return RequestTypeFieldConfig::class;
    }

    #[Override]
    public function renderConfigForm(
        Form $form,
        FormDestination $destination,
        JsonFieldInterface $config,
        string $input_name,
        array $display_options
    ): string {
        if (!$config instanceof RequestTypeFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        $twig = TemplateRenderer::getInstance();
        return $twig->render('pages/admin/form/itil_config_fields/request_type.html.twig', [
            // Possible configuration constant that will be used to to hide/show additional fields
            'CONFIG_SPECIFIC_VALUE'  => RequestTypeFieldStrategy::SPECIFIC_VALUE->value,
            'CONFIG_SPECIFIC_ANSWER' => RequestTypeFieldStrategy::SPECIFIC_ANSWER->value,

            // General display options
            'options' => $display_options,

            // Specific additional config for SPECIFIC_ANSWER strategy
            'specific_value_extra_field' => [
                'empty_label'     => __("Select a request type..."),
                'value'           => $config->getSpecificRequestType(),
                'input_name'      => $input_name . "[" . RequestTypeFieldConfig::SPECIFIC_REQUEST_TYPE . "]",
                'possible_values' => Ticket::getTypes(),
            ],

            // Specific additional config for SPECIFIC_VALUE strategy
            'specific_answer_extra_field' => [
                'empty_label'     => __("Select a question..."),
                'value'           => $config->getSpecificQuestionId(),
                'input_name'      => $input_name . "[" . RequestTypeFieldConfig::SPECIFIC_QUESTION_ID . "]",
                'possible_values' => $this->getRequestTypeQuestionsValuesForDropdown($form),
            ],
        ]);
    }

    #[Override]
    public function applyConfiguratedValueToInputUsingAnswers(
        JsonFieldInterface $config,
        array $input,
        AnswersSet $answers_set
    ): array {
        if (!$config instanceof RequestTypeFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        // Only one strategy is allowed
        $strategy = current($config->getStrategies());

        // Compute value according to strategy
        $request_type = $strategy->getRequestType($config, $answers_set);

        // Do not edit input if invalid value was found
        $valid_values = [Ticket::INCIDENT_TYPE, Ticket::DEMAND_TYPE];
        if (!in_array($request_type, $valid_values)) {
            return $input;
        }

        // Apply value
        $input['type'] = $request_type;
        return $input;
    }

    #[Override]
    public function getDefaultConfig(Form $form): RequestTypeFieldConfig
    {
        return new RequestTypeFieldConfig(
            RequestTypeFieldStrategy::LAST_VALID_ANSWER
        );
    }

    #[Override]
    public function convertFieldConfig(FormMigration $migration, Form $form, array $rawData): JsonFieldInterface
    {
        switch ($rawData['type_rule']) {
            case 0: // PluginFormcreatorAbstractItilTarget::REQUESTTYPE_NONE
                return new RequestTypeFieldConfig(
                    strategy: RequestTypeFieldStrategy::FROM_TEMPLATE
                );
            case 1: // PluginFormcreatorAbstractItilTarget::REQUESTTYPE_SPECIFIC
                return new RequestTypeFieldConfig(
                    strategy: RequestTypeFieldStrategy::SPECIFIC_VALUE,
                    specific_request_type: $rawData['type_question']
                );
            case 2: // PluginFormcreatorAbstractItilTarget::REQUESTTYPE_ANSWER
                $mapped_item = $migration->getMappedItemTarget(
                    'PluginFormcreatorQuestion',
                    $rawData['type_question']
                );

                if ($mapped_item === null) {
                    throw new InvalidArgumentException("Question '{$rawData['type_question']}' not found in a target form");
                }

                return new RequestTypeFieldConfig(
                    strategy: RequestTypeFieldStrategy::SPECIFIC_ANSWER,
                    specific_question_id: $mapped_item['items_id']
                );
        }

        return $this->getDefaultConfig($form);
    }

    public function getStrategiesForDropdown(): array
    {
        $values = [];
        foreach (RequestTypeFieldStrategy::cases() as $strategies) {
            $values[$strategies->value] = $strategies->getLabel();
        }
        return $values;
    }

    private function getRequestTypeQuestionsValuesForDropdown(Form $form): array
    {
        $values = [];
        $questions = $form->getQuestionsByType(QuestionTypeRequestType::class);

        foreach ($questions as $question) {
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
    public function getCategory(): Category
    {
        return Category::PROPERTIES;
    }

    #[Override]
    public static function prepareDynamicConfigDataForImport(
        array $config,
        AbstractCommonITILFormDestination $destination,
        DatabaseMapper $mapper,
    ): array {
        // Check if a specific question is defined
        if (isset($config[RequestTypeFieldConfig::SPECIFIC_QUESTION_ID])) {
            // Insert id
            $config[RequestTypeFieldConfig::SPECIFIC_QUESTION_ID] = $mapper->getItemId(
                Question::class,
                $config[RequestTypeFieldConfig::SPECIFIC_QUESTION_ID],
            );
        }

        return $config;
    }
}
