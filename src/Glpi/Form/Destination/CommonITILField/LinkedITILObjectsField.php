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

use Change;
use CommonITILObject;
use CommonITILObject_CommonITILObject;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\AnswersSet;
use Glpi\Form\Destination\AbstractCommonITILFormDestination;
use Glpi\Form\Destination\AbstractConfigField;
use Glpi\Form\Destination\FormDestination;
use Glpi\Form\Export\Context\DatabaseMapper;
use Glpi\Form\Export\Serializer\DynamicExportDataField;
use Glpi\Form\Export\Specification\DataRequirementSpecification;
use Glpi\Form\Form;
use Glpi\Form\Migration\DestinationFieldConverterInterface;
use Glpi\Form\Migration\FormMigration;
use Glpi\Form\Question;
use Glpi\Form\QuestionType\QuestionTypeItem;
use InvalidArgumentException;
use Override;
use Problem;
use Ticket;

use function Safe\json_decode;

final class LinkedITILObjectsField extends AbstractConfigField implements DestinationFieldConverterInterface
{
    #[Override]
    public function getLabel(): string
    {
        return __("Link to assistance objects");
    }

    #[Override]
    public function getConfigClass(): string
    {
        return LinkedITILObjectsFieldConfig::class;
    }

    #[Override]
    public function renderConfigForm(
        Form $form,
        FormDestination $destination,
        JsonFieldInterface $config,
        string $input_name,
        array $display_options,
        string|int $strategy_index = 0
    ): string {
        if (!$config instanceof LinkedITILObjectsFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        $configs = $config->getStrategyConfigs();
        if ($strategy_index === '__INDEX__') {
            $configs = $this->getDefaultConfig($form)->getStrategyConfigs();
        }
        $twig = TemplateRenderer::getInstance();
        return $twig->render('pages/admin/form/itil_config_fields/linked_itilobjects.html.twig', [
            // Possible configuration constant that will be used to to hide/show additional fields
            'CONFIG_SPECIFIC_DESTINATIONS' => LinkedITILObjectsFieldStrategy::SPECIFIC_DESTINATIONS->value,
            'CONFIG_SPECIFIC_VALUES'       => LinkedITILObjectsFieldStrategy::SPECIFIC_VALUES->value,
            'CONFIG_SPECIFIC_ANSWERS'      => LinkedITILObjectsFieldStrategy::SPECIFIC_ANSWERS->value,

            'configs'         => $configs,
            'is_for_template' => $strategy_index === '__INDEX__',
            'input_name'      => $input_name,
            'options'         => $display_options,

            // Config for linktypes
            'dropdown_linktypes' => [
                'input_name_suffix' => LinkedITILObjectsFieldStrategyConfig::LINKTYPE,
                'aria_label'        => __("Select the link type..."),
                'values'            => array_combine(
                    array_keys(CommonITILObject_CommonITILObject::getITILLinkTypes()),
                    array_column(CommonITILObject_CommonITILObject::getITILLinkTypes(), 'name'),
                ),
            ],

            // Config for strategies
            'dropdown_strategies' => [
                'input_name_suffix' => LinkedITILObjectsFieldStrategyConfig::STRATEGY,
                'aria_label'        => __("Select the strategy..."),
                'values'            => $this->getStrategiesForDropdown(),
            ],

            // Specific additional config for SPECIFIC_VALUES strategy
            'specific_values_extra_field' => [
                'itemtype_aria_label' => __("Select assistance object type..."),
                'items_id_aria_label' => __("Select assistance object..."),
                'input_name_suffix'   => LinkedITILObjectsFieldStrategyConfig::SPECIFIC_ITILOBJECT,
                'itemtypes'           => [
                    Ticket::class,
                    Change::class,
                    Problem::class,
                ],
            ],

            // Specific additional config for SPECIFIC_DESTINATIONS strategy
            'specific_destinations_extra_field' => [
                'aria_label'        => __("Select destination..."),
                'input_name_suffix' => LinkedITILObjectsFieldStrategyConfig::SPECIFIC_DESTINATION_IDS,
                'possible_values'   => $this->getITILDestinationsValuesForDropdown($form, $destination),
            ],

            // Specific additional config for SPECIFIC_ANSWERS strategy
            'specific_answer_extra_field' => [
                'aria_label'        => __("Select questions..."),
                'input_name_suffix' => LinkedITILObjectsFieldStrategyConfig::SPECIFIC_QUESTION_IDS,
                'possible_values'   => $this->getITILObjectQuestionsValuesForDropdown($form),
            ],
        ]);
    }

    #[Override]
    public function applyConfiguratedValueAfterDestinationCreation(
        FormDestination $destination,
        JsonFieldInterface $config,
        AnswersSet $answers_set,
        array $created_objects
    ): void {
        if (!$config instanceof LinkedITILObjectsFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        // Process strategies that need post-creation processing
        $linked_itilobjects = [];
        foreach ($config->getStrategyConfigs() as $strategy_config) {
            if ($strategy_config->getStrategy() === null) {
                continue; // Skip if strategy is not set
            }

            $linked_itilobjects = array_merge(
                $linked_itilobjects,
                $strategy_config->getStrategy()->getLinkedITILObjects(
                    $strategy_config,
                    $answers_set,
                    $created_objects,
                ) ?? []
            );
        }

        // Apply the linked ITIL objects to the created objects
        foreach ($linked_itilobjects as $linked_itilobject) {
            if ($linked_itilobject === null) {
                continue;
            }

            if (!isset($linked_itilobject['itemtype'], $linked_itilobject['items_id'], $linked_itilobject['linktype'])) {
                continue;
            }

            $source = $created_objects[$destination->getID()][0] ?? null;
            if ($source === null || !($source instanceof CommonITILObject)) {
                continue;
            }

            $target = getItemForItemtype($linked_itilobject['itemtype']);
            if (!($target instanceof CommonITILObject) || !$target->getFromDB($linked_itilobject['items_id'])) {
                continue;
            }

            $this->createITILLink($source, $target, $linked_itilobject['linktype']);
        }
    }

    private function createITILLink(
        CommonITILObject $source,
        CommonITILObject $target,
        string $linktype
    ): void {
        $link_class = CommonITILObject_CommonITILObject::getLinkClass($source::class, $target::class);
        if ($link_class === null) {
            throw new InvalidArgumentException("Link class not found for " . $source::class . " and " . $target::class);
        }

        /** @var CommonITILObject_CommonITILObject $link */
        $link = getItemForItemtype($link_class);
        $input = $link->normalizeInput([
            'itemtype_1' => $source::class,
            'items_id_1' => $source->getID(),
            'itemtype_2' => $target::class,
            'items_id_2' => $target->getID(),
            'link'             => $linktype,
        ]);
        $link->add($input);
    }

    #[Override]
    public function getDefaultConfig(Form $form): LinkedITILObjectsFieldConfig
    {
        return new LinkedITILObjectsFieldConfig();
    }

    #[Override]
    public function getWeight(): int
    {
        return 300;
    }

    #[Override]
    public function prepareInput(array $input): array
    {
        $input = parent::prepareInput($input);

        // If the input already has the strategy configs, return it as is
        if (isset($input[$this->getKey()][LinkedITILObjectsFieldConfig::STRATEGY_CONFIGS])) {
            return $input;
        }

        $strategy_configs = [];
        foreach ($input[$this->getKey()] ?? [] as $config_data) {
            // Handle strategy
            $strategy = LinkedITILObjectsFieldStrategy::tryFrom($config_data[LinkedITILObjectsFieldStrategyConfig::STRATEGY] ?? '');

            // Handle linktype
            $linktype = $config_data[LinkedITILObjectsFieldStrategyConfig::LINKTYPE] ?? '';
            if (!in_array($linktype, array_keys(CommonITILObject_CommonITILObject::getITILLinkTypes()))) {
                $linktype = current(CommonITILObject_CommonITILObject::getITILLinkTypes()); // Default linktype
            }

            // Handle specific destination ids
            $specific_destination_ids = [];
            if (isset($config_data[LinkedITILObjectsFieldStrategyConfig::SPECIFIC_DESTINATION_IDS])) {
                $destination_ids = $config_data[LinkedITILObjectsFieldStrategyConfig::SPECIFIC_DESTINATION_IDS];
                if (is_array($destination_ids)) {
                    $specific_destination_ids = array_filter($destination_ids, 'is_numeric');
                }
            }

            // Handle specific question IDs
            $specific_question_ids = [];
            if (isset($config_data[LinkedITILObjectsFieldStrategyConfig::SPECIFIC_QUESTION_IDS])) {
                $question_ids = $config_data[LinkedITILObjectsFieldStrategyConfig::SPECIFIC_QUESTION_IDS];
                if (is_array($question_ids)) {
                    $specific_question_ids = array_filter($question_ids, 'is_numeric');
                }
            }

            // Handle specific ITIL objects
            $specific_itilobject = [];
            if (isset($config_data[LinkedITILObjectsFieldStrategyConfig::SPECIFIC_ITILOBJECT])) {
                $itemtype = $config_data[LinkedITILObjectsFieldStrategyConfig::SPECIFIC_ITILOBJECT]['itemtype'] ?? '';
                $items_id = $config_data[LinkedITILObjectsFieldStrategyConfig::SPECIFIC_ITILOBJECT]['items_id'] ?? '';

                if (!is_numeric($items_id) || $items_id < 0) {
                    $items_id = 0;
                } elseif (!is_string($itemtype) || !is_a($itemtype, CommonITILObject::class, true)) {
                    $itemtype = 0;
                }

                $specific_itilobject = [
                    'itemtype' => $itemtype,
                    'items_id' => $items_id,
                ];
            }

            $strategy_configs[] = new LinkedITILObjectsFieldStrategyConfig(
                strategy: $strategy,
                linktype: $linktype,
                specific_destination_ids: $specific_destination_ids,
                specific_question_ids: $specific_question_ids,
                specific_itilobject: $specific_itilobject
            );
        }

        // Replace the input with the new config structure
        $input[$this->getKey()] = new LinkedITILObjectsFieldConfig($strategy_configs);

        return $input;
    }

    #[Override]
    public function canHaveMultipleStrategies(): bool
    {
        return true;
    }

    public function getReusableStrategies(): array
    {
        return [
            LinkedITILObjectsFieldStrategy::SPECIFIC_DESTINATIONS->value,
            LinkedITILObjectsFieldStrategy::SPECIFIC_VALUES->value,
            LinkedITILObjectsFieldStrategy::SPECIFIC_ANSWERS->value,
        ];
    }

    #[Override]
    public function getCategory(): Category
    {
        return Category::ASSOCIATED_ITEMS;
    }

    #[Override]
    public function convertFieldConfig(FormMigration $migration, Form $form, array $rawData): JsonFieldInterface
    {
        if (isset($rawData['linked_itilobjects'])) {
            $strategy_configs = [];
            $linked_itilobjects = json_decode($rawData['linked_itilobjects'], true) ?? [];
            foreach ($linked_itilobjects as $linked_itilobject) {
                if (!isset($linked_itilobject['itemtype'], $linked_itilobject['items_id'], $linked_itilobject['linktype'])) {
                    continue; // Skip if any required field is missing
                }

                $strategy = null;
                $specific_destination_ids = [];
                $specific_question_ids = [];
                $specific_itilobject = [];
                if (in_array($linked_itilobject['itemtype'], ['Ticket', 'Change', 'Problem'])) {
                    $strategy = LinkedITILObjectsFieldStrategy::SPECIFIC_VALUES;
                    $specific_itilobject = [
                        'itemtype' => $linked_itilobject['itemtype'],
                        'items_id' => $linked_itilobject['items_id'],
                    ];
                } elseif (in_array($linked_itilobject['itemtype'], [
                    'PluginFormcreatorTargetTicket',
                    'PluginFormcreatorTargetChange',
                    'PluginFormcreatorTargetProblem',
                ])) {
                    $mapped_item = $migration->getMappedItemTarget(
                        $linked_itilobject['itemtype'],
                        $linked_itilobject['items_id']
                    );

                    if ($mapped_item === null) {
                        throw new InvalidArgumentException(
                            "Mapped item not found for {$linked_itilobject['itemtype']} with ID {$linked_itilobject['items_id']}"
                        );
                    }

                    $strategy = LinkedITILObjectsFieldStrategy::SPECIFIC_DESTINATIONS;
                    $specific_destination_ids = [$mapped_item['items_id']];
                } elseif ($linked_itilobject['itemtype'] === 'PluginFormcreatorQuestion') {
                    $mapped_item = $migration->getMappedItemTarget(
                        $linked_itilobject['itemtype'],
                        $linked_itilobject['items_id']
                    );

                    if ($mapped_item === null) {
                        throw new InvalidArgumentException(
                            "Mapped item not found for {$linked_itilobject['itemtype']} with ID {$linked_itilobject['items_id']}"
                        );
                    }

                    $strategy = LinkedITILObjectsFieldStrategy::SPECIFIC_ANSWERS;
                    $specific_question_ids = [$mapped_item['items_id']];
                }

                $strategy_configs[] = new LinkedITILObjectsFieldStrategyConfig(
                    strategy: $strategy,
                    linktype: $linked_itilobject['linktype'],
                    specific_destination_ids: $specific_destination_ids,
                    specific_question_ids: $specific_question_ids,
                    specific_itilobject: $specific_itilobject
                );
            }

            return new LinkedITILObjectsFieldConfig($strategy_configs);
        }

        return $this->getDefaultConfig($form);
    }

    #[Override]
    public function exportDynamicConfig(
        array $config,
        AbstractCommonITILFormDestination $destination,
    ): DynamicExportDataField {
        $requirements = [];

        if (!isset($config[LinkedITILObjectsFieldConfig::STRATEGY_CONFIGS])) {
            return parent::exportDynamicConfig($config, $destination);
        }

        $strategy_configs = $config[LinkedITILObjectsFieldConfig::STRATEGY_CONFIGS];
        foreach ($strategy_configs as &$strategy_config) {
            if (isset($strategy_config[LinkedITILObjectsFieldStrategyConfig::SPECIFIC_ITILOBJECT])) {
                $specific_object = $strategy_config[LinkedITILObjectsFieldStrategyConfig::SPECIFIC_ITILOBJECT];
                if (isset($specific_object['itemtype'], $specific_object['items_id'])) {
                    $item = getItemForItemtype($specific_object['itemtype']);
                    if ($item && $item->getFromDB($specific_object['items_id'])) {
                        // Insert name instead of id and register requirement
                        $requirement = DataRequirementSpecification::fromItem($item);
                        $requirements[] = $requirement;
                        $strategy_config[LinkedITILObjectsFieldStrategyConfig::SPECIFIC_ITILOBJECT]['items_id'] = $requirement->name;
                    }
                }
            }
        }

        $config[LinkedITILObjectsFieldConfig::STRATEGY_CONFIGS] = $strategy_configs;
        return new DynamicExportDataField($config, $requirements);
    }

    #[Override]
    public static function prepareDynamicConfigDataForImport(
        array $config,
        AbstractCommonITILFormDestination $destination,
        DatabaseMapper $mapper,
    ): array {
        if (isset($config[LinkedITILObjectsFieldConfig::STRATEGY_CONFIGS])) {
            $strategy_configs = $config[LinkedITILObjectsFieldConfig::STRATEGY_CONFIGS];
            foreach ($strategy_configs as &$strategy_config) {
                if (isset($strategy_config[LinkedITILObjectsFieldStrategyConfig::SPECIFIC_ITILOBJECT])) {
                    $specific_object = $strategy_config[LinkedITILObjectsFieldStrategyConfig::SPECIFIC_ITILOBJECT];
                    if (isset($specific_object['itemtype'], $specific_object['items_id'])) {
                        $id = $mapper->getItemId($specific_object['itemtype'], $specific_object['items_id']);
                        $strategy_config[LinkedITILObjectsFieldStrategyConfig::SPECIFIC_ITILOBJECT]['items_id'] = $id;
                    }
                }

                // Handle specific questions
                if (isset($strategy_config[LinkedITILObjectsFieldStrategyConfig::SPECIFIC_QUESTION_IDS])) {
                    $questions = $strategy_config[LinkedITILObjectsFieldStrategyConfig::SPECIFIC_QUESTION_IDS];
                    foreach ($questions as $i => $question) {
                        $id = $mapper->getItemId(Question::class, $question);
                        $questions[$i] = $id;
                    }
                    $strategy_config[LinkedITILObjectsFieldStrategyConfig::SPECIFIC_QUESTION_IDS] = $questions;
                }

                // Handle specific destinations
                if (isset($strategy_config[LinkedITILObjectsFieldStrategyConfig::SPECIFIC_DESTINATION_IDS])) {
                    $destinations = $strategy_config[LinkedITILObjectsFieldStrategyConfig::SPECIFIC_DESTINATION_IDS];
                    foreach ($destinations as $i => $destination) {
                        $id = $mapper->getItemId(FormDestination::class, $destination);
                        $destinations[$i] = $id;
                    }
                    $strategy_config[LinkedITILObjectsFieldStrategyConfig::SPECIFIC_DESTINATION_IDS] = $destinations;
                }
            }
            $config[LinkedITILObjectsFieldConfig::STRATEGY_CONFIGS] = $strategy_configs;
        }

        return $config;
    }

    public function getStrategiesForDropdown(): array
    {
        $values = [];
        foreach (LinkedITILObjectsFieldStrategy::cases() as $strategies) {
            $values[$strategies->value] = $strategies->getLabel();
        }
        return $values;
    }

    private function getITILDestinationsValuesForDropdown(
        Form $form,
        FormDestination $current_destination
    ): array {
        $values = [];
        $destinations = $form->getDestinations();
        foreach ($destinations as $destination) {
            if (
                $destination->getConcreteDestinationItem() instanceof AbstractCommonITILFormDestination
                && $destination->getID() !== $current_destination->getID()
            ) {
                $values[$destination->getID()] = $destination->getName();
            }
        }

        return $values;
    }

    private function getITILObjectQuestionsValuesForDropdown(Form $form): array
    {
        $values = [];
        $questions = $this->getValidItemQuestions($form);

        foreach ($questions as $question) {
            $values[$question->getId()] = $question->fields['name'];
        }

        return $values;
    }

    private function getValidItemQuestions(Form $form): array
    {
        $questions = $form->getQuestionsByType(QuestionTypeItem::class);
        $valid_itemtypes = [Ticket::class, Change::class, Problem::class];

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
}
