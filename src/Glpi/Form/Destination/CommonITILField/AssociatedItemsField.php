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

use CommonITILObject;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\AnswersSet;
use Glpi\Form\Destination\AbstractConfigField;
use Glpi\Form\Form;
use Glpi\Form\Migration\DestinationFieldConverterInterface;
use Glpi\Form\Migration\FormMigration;
use Glpi\Form\QuestionType\QuestionTypeItem;
use Glpi\Form\QuestionType\QuestionTypeUserDevice;
use InvalidArgumentException;
use Override;

class AssociatedItemsField extends AbstractConfigField implements DestinationFieldConverterInterface
{
    #[Override]
    public function getLabel(): string
    {
        return __("Associated items");
    }

    #[Override]
    public function getConfigClass(): string
    {
        return AssociatedItemsFieldConfig::class;
    }

    #[Override]
    public function renderConfigForm(
        Form $form,
        JsonFieldInterface $config,
        string $input_name,
        array $display_options
    ): string {
        if (!$config instanceof AssociatedItemsFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        $twig = TemplateRenderer::getInstance();
        return $twig->render('pages/admin/form/itil_config_fields/associated_items.html.twig', [
            // Possible configuration constant that will be used to to hide/show additional fields
            'CONFIG_SPECIFIC_VALUES'  => AssociatedItemsFieldStrategy::SPECIFIC_VALUES->value,
            'CONFIG_SPECIFIC_ANSWERS' => AssociatedItemsFieldStrategy::SPECIFIC_ANSWERS->value,

            // General display options
            'options' => $display_options,

            // Specific additional config for SPECIFIC_VALUES strategy
            'specific_values_extra_field' => [
                'empty_label'         => __("Select an itemtype..."),
                'itemtype_aria_label' => __("Select the itemtype of the item to associate..."),
                'items_id_aria_label' => __("Select the item to associate..."),
                'input_name'          => $input_name . "[" . AssociatedItemsFieldConfig::SPECIFIC_ASSOCIATED_ITEMS . "]",
                'itemtypes'           => array_keys(CommonITILObject::getAllTypesForHelpdesk()),
                'associated_items'    => $config->getSpecificAssociatedItems(),
            ],

            // Specific additional config for SPECIFIC_ANSWERS strategy
            'specific_answer_extra_field' => [
                'aria_label'      => __("Select questions..."),
                'values'          => $config->getSpecificQuestionIds(),
                'input_name'      => $input_name . "[" . AssociatedItemsFieldConfig::SPECIFIC_QUESTION_IDS . "]",
                'possible_values' => $this->getAssociatedItemsQuestionsValuesForDropdown($form),
            ],
        ]);
    }

    #[Override]
    public function applyConfiguratedValueToInputUsingAnswers(
        JsonFieldInterface $config,
        array $input,
        AnswersSet $answers_set
    ): array {
        if (!$config instanceof AssociatedItemsFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        // Compute value according to strategies
        foreach ($config->getStrategies() as $strategy) {
            $associated_items = $strategy->getAssociatedItems($config, $answers_set);

            if (!empty($associated_items)) {
                $valid_itemtypes = array_keys(CommonITILObject::getAllTypesForHelpdesk());
                foreach ($associated_items as $associated_item) {
                    // Do not edit input if invalid value was found
                    if (
                        !in_array($associated_item['itemtype'], $valid_itemtypes)
                        || !is_numeric($associated_item['items_id'])
                        || $associated_item['items_id'] <= 0
                    ) {
                        continue;
                    }

                    // Apply value
                    $input['items_id'][$associated_item['itemtype']][] = $associated_item['items_id'];
                }
            }
        }

        return $input;
    }

    #[Override]
    public function getDefaultConfig(Form $form): AssociatedItemsFieldConfig
    {
        return new AssociatedItemsFieldConfig(
            [AssociatedItemsFieldStrategy::LAST_VALID_ANSWER]
        );
    }

    public function getStrategiesForDropdown(): array
    {
        $values = [];
        foreach (AssociatedItemsFieldStrategy::cases() as $strategies) {
            $values[$strategies->value] = $strategies->getLabel();
        }
        return $values;
    }

    private function getAssociatedItemsQuestionsValuesForDropdown(Form $form): array
    {
        $values = [];
        $questions = array_merge(
            $this->getValidItemQuestions($form),
            $this->getUserDeviceQuestions($form)
        );

        foreach ($questions as $question) {
            $values[$question->getId()] = $question->fields['name'];
        }

        return $values;
    }

    private function getValidItemQuestions(Form $form): array
    {
        $questions = $form->getQuestionsByType(QuestionTypeItem::class);
        $valid_itemtypes = array_keys(CommonITILObject::getAllTypesForHelpdesk());

        $valid_questions = [];
        foreach ($questions as $question) {
            if (
                in_array(
                    (new QuestionTypeItem())->getDefaultValueItemtype($question),
                    $valid_itemtypes
                )
            ) {
                $valid_questions[] = $question;
            }
        }

        return $valid_questions;
    }

    private function getUserDeviceQuestions(Form $form): array
    {
        return $form->getQuestionsByType(QuestionTypeUserDevice::class);
    }

    #[Override]
    public function getWeight(): int
    {
        return 200;
    }

    #[Override]
    public function prepareInput(array $input): array
    {
        $input = parent::prepareInput($input);

        if (!isset($input[$this->getKey()][AssociatedItemsFieldConfig::STRATEGIES])) {
            return $input;
        }

        // Ensure that question_ids is an array
        if (!is_array($input[$this->getKey()][AssociatedItemsFieldConfig::SPECIFIC_QUESTION_IDS] ?? null)) {
            $input[$this->getKey()][AssociatedItemsFieldConfig::SPECIFIC_QUESTION_IDS] = null;
        }

        // Compute associated_items as an array of itemtype => [item_id, ...]
        if (
            isset($input[$this->getKey()][AssociatedItemsFieldConfig::SPECIFIC_ASSOCIATED_ITEMS])
            && isset($input[$this->getKey()][AssociatedItemsFieldConfig::SPECIFIC_ASSOCIATED_ITEMS]['itemtype'])
            && isset($input[$this->getKey()][AssociatedItemsFieldConfig::SPECIFIC_ASSOCIATED_ITEMS]['items_id'])
        ) {
            $itemtypes = $input[$this->getKey()][AssociatedItemsFieldConfig::SPECIFIC_ASSOCIATED_ITEMS]['itemtype'];
            $items_ids = $input[$this->getKey()][AssociatedItemsFieldConfig::SPECIFIC_ASSOCIATED_ITEMS]['items_id'];

            $result = [];

            foreach ($itemtypes as $index => $itemtype) {
                $item_id = $items_ids[$index];

                // Ensure that itemtype and item_id are valid
                if (
                    getItemForItemtype($itemtype) === false
                    || getItemForItemtype($itemtype)->getFromDB($item_id) === false
                ) {
                    continue;
                }

                if (!isset($result[$itemtype])) {
                    $result[$itemtype] = [];
                }

                $result[$itemtype][] = $item_id;
            }

            $input[$this->getKey()][AssociatedItemsFieldConfig::SPECIFIC_ASSOCIATED_ITEMS] = $result;
        }

        return $input;
    }

    #[Override]
    public function canHaveMultipleStrategies(): bool
    {
        return true;
    }

    #[Override]
    public function getCategory(): Category
    {
        return Category::PROPERTIES;
    }

    #[Override]
    public function convertFieldConfig(FormMigration $migration, Form $form, array $rawData): JsonFieldInterface
    {
        if (isset($rawData['associate_rule'])) {
            switch ($rawData['associate_rule']) {
                case 2: // PluginFormcreatorAbstractItilTarget::ASSOCIATE_RULE_SPECIFIC
                    return new AssociatedItemsFieldConfig(
                        strategies: [AssociatedItemsFieldStrategy::SPECIFIC_VALUES],
                        specific_associated_items: json_decode($rawData['associate_items'], true) ?? []
                    );
                case 3: // PluginFormcreatorAbstractItilTarget::ASSOCIATE_RULE_ANSWER
                    return new AssociatedItemsFieldConfig(
                        strategies: [AssociatedItemsFieldStrategy::SPECIFIC_ANSWERS],
                        specific_question_ids: [
                            $migration->getMappedItemTarget(
                                'PluginFormcreatorQuestion',
                                $rawData['associate_question']
                            )['items_id']
                        ],
                    );
                case 4: // PluginFormcreatorAbstractItilTarget::ASSOCIATE_RULE_LAST_ANSWER
                    return new AssociatedItemsFieldConfig(
                        [AssociatedItemsFieldStrategy::LAST_VALID_ANSWER]
                    );
            }
        }

        return $this->getDefaultConfig($form);
    }
}
