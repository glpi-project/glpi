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

namespace Glpi\Form\QuestionType;

use CartridgeItem;
use CommonDBTM;
use ConsumableItem;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\Export\Context\DatabaseMapper;
use Glpi\Form\Export\Serializer\DynamicExportDataField;
use Glpi\Form\Export\Specification\DataRequirementSpecification;
use Glpi\Form\Migration\FormQuestionDataConverterInterface;
use Glpi\Form\Condition\ConditionHandler\ConditionHandlerInterface;
use Glpi\Form\Condition\ConditionHandler\ItemConditionHandler;
use Glpi\Form\Condition\UsedAsCriteriaInterface;
use Glpi\Form\Question;
use InvalidArgumentException;
use Line;
use Override;
use PassiveDCEquipment;
use PDU;
use Session;
use Software;
use TicketRecurrent;

class QuestionTypeItem extends AbstractQuestionType implements FormQuestionDataConverterInterface, UsedAsCriteriaInterface
{
    protected string $itemtype_aria_label;
    protected string $items_id_aria_label;

    public function __construct()
    {
        parent::__construct();

        $this->itemtype_aria_label = __('Select an itemtype');
        $this->items_id_aria_label = __('Select an item');
    }

    #[Override]
    public function formatDefaultValueForDB(mixed $value): ?string
    {
        if (is_array($value) && isset($value['items_id'])) {
            $value = $value['items_id'];
        }

        if (!is_numeric($value)) {
            return null;
        }

        return json_encode(new QuestionTypeItemDefaultValueConfig((int) $value));
    }

    #[Override]
    public function convertDefaultValue(array $rawData): mixed
    {
        return $rawData['default_values'] ?? null;
    }

    #[Override]
    public function convertExtraData(array $rawData): mixed
    {
        return (new QuestionTypeItemExtraDataConfig(
            itemtype: $rawData['itemtype'] ?? null
        ))->jsonSerialize();
    }

    /**
     * Retrieve the allowed item types
     *
     * @return array
     */
    public function getAllowedItemtypes(): array
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        return [
            __('Assets') => array_merge(
                $CFG_GLPI['asset_types'],
                [
                    Software::class,
                    CartridgeItem::class,
                    ConsumableItem::class,
                    Line::class,
                    PassiveDCEquipment::class,
                    PDU::class,
                ]
            ),
            __('Assistance') => array_merge(
                $CFG_GLPI['itil_types'],
                [
                    TicketRecurrent::class,
                ]
            ),
            __('Management') => $CFG_GLPI['management_types'],
            __('Tools') => $CFG_GLPI['tools_types'],
            __('Administration') => $CFG_GLPI['admin_types']
        ];
    }

    /**
     * Retrieve the default value for the item question type
     *
     * @param Question|null $question The question to retrieve the default value from
     * @return ?string
     */
    public function getDefaultValueItemtype(?Question $question): ?string
    {
        if ($question === null) {
            return null;
        }

        /** @var ?QuestionTypeItemExtraDataConfig $config */
        $config = $this->getExtraDataConfig(json_decode($question->fields['extra_data'], true) ?? []);
        if ($config === null) {
            return null;
        }

        return $config->getItemtype();
    }

    /**
     * Retrieve the default value for the item question type
     *
     * @param Question|null $question The question to retrieve the default value from
     * @return int
     */
    public function getDefaultValueItemId(?Question $question): int
    {
        if ($question === null) {
            return 0;
        }

        /** @var ?QuestionTypeItemDefaultValueConfig $config */
        $config = $this->getDefaultValueConfig(json_decode($question->fields['default_value'] ?? '[]', true));
        if ($config === null) {
            return 0;
        }

        return (int) $config->getItemsId();
    }

    #[Override]
    public function validateExtraDataInput(array $input): bool
    {
        // Check if the itemtype is set
        if (!isset($input['itemtype'])) {
            return false;
        }

        // Check if the itemtype is allowed
        if (
            $input['itemtype'] != '0'
            && !in_array($input['itemtype'], array_merge(...array_values($this->getAllowedItemtypes())))
        ) {
            return false;
        }

        return true;
    }

    #[Override]
    public function getSubTypes(): array
    {
        return Dropdown::buildItemtypesDropdownOptions($this->getAllowedItemtypes());
    }

    #[Override]
    public function getSubTypeFieldName(): string
    {
        return 'itemtype';
    }

    #[Override]
    public function getSubTypeFieldAriaLabel(): string
    {
        return $this->itemtype_aria_label;
    }

    #[Override]
    public function getSubTypeDefaultValue(?Question $question): ?string
    {
        return $this->getDefaultValueItemtype($question);
    }

    #[Override]
    public function renderAdministrationTemplate(?Question $question): string
    {
        $template = <<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}

            {% set rand = random() %}

            {{ fields.dropdownField(
                default_itemtype|default(itemtypes|first|first),
                'default_value',
                default_items_id,
                '',
                {
                    'init'               : init,
                    'no_label'           : true,
                    'display_emptychoice': true,
                    'width'              : '100%',
                    'container_css_class': 'mt-2',
                    'mb'                 : '',
                    'comments'           : false,
                    'addicon'            : false,
                    'aria_label'         : aria_label,
                }
            ) }}

            {% if question == null %}
                <script>
                    import("{{ js_path('js/modules/Forms/QuestionItem.js') }}").then((m) => {
                        new m.GlpiFormQuestionTypeItem({{ question_type|json_encode|raw }});
                    });
                </script>
            {% endif %}
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'init'             => $question != null,
            'question'         => $question,
            'question_type'    => $this::class,
            'default_itemtype' => $this->getDefaultValueItemtype($question),
            'default_items_id' => $this->getDefaultValueItemId($question),
            'itemtypes'        => $this->getAllowedItemtypes(),
            'aria_label'       => $this->items_id_aria_label,
        ]);
    }

    #[Override]
    public function renderEndUserTemplate(Question $question): string
    {
        $template = <<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}

            {{ fields.hiddenField(
                question.getEndUserInputName() ~ '[itemtype]',
                itemtype,
                '',
                {
                    'no_label': true,
                    'mb': ''
                }
            ) }}
            {{ fields.dropdownField(
                itemtype,
                question.getEndUserInputName() ~ '[items_id]',
                default_items_id,
                '',
                {
                    'no_label'           : true,
                    'display_emptychoice': true,
                    'right'              : 'all',
                    'aria_label'         : aria_label,
                    'mb'                 : '',
                    'addicon'            : false,
                    'comments'           : false,
                }
            ) }}
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'question'         => $question,
            'itemtype'         => $this->getDefaultValueItemtype($question) ?? '0',
            'default_items_id' => $this->getDefaultValueItemId($question),
            'aria_label'       => $question->fields['name'],
            'sub_types'        => $this->getSubTypes(),
        ]);
    }

    #[Override]
    public function formatRawAnswer(mixed $answer, Question $question): string
    {
        $item = $answer['itemtype']::getById($answer['items_id']);
        if (!$item) {
            return '';
        }

        return $item->fields['name'];
    }

    #[Override]
    public function getCategory(): QuestionTypeCategoryInterface
    {
        return QuestionTypeCategory::ITEM;
    }

    #[Override]
    public function getName(): string
    {
        return _n('GLPI Object', 'GLPI Objects', Session::getPluralNumber());
    }

    #[Override]
    public function getIcon(): string
    {
        return 'ti ti-link';
    }

    #[Override]
    public function getWeight(): int
    {
        return 10;
    }

    #[Override]
    public function getExtraDataConfigClass(): ?string
    {
        return QuestionTypeItemExtraDataConfig::class;
    }

    #[Override]
    public function getDefaultValueConfigClass(): ?string
    {
        return QuestionTypeItemDefaultValueConfig::class;
    }

    #[Override]
    public function getConditionHandlers(
        ?JsonFieldInterface $question_config
    ): array {
        if (!$question_config instanceof QuestionTypeItemExtraDataConfig) {
            throw new InvalidArgumentException();
        }

        return [new ItemConditionHandler($question_config->getItemtype())];
    }

    public function exportDynamicDefaultValue(
        ?JsonFieldInterface $extra_data_config,
        array|int|float|bool|string|null $default_value_config,
    ): DynamicExportDataField {
        $requirements = [];
        $fallback = parent::exportDynamicDefaultValue(
            $extra_data_config,
            $default_value_config
        );

        // Stop here if one of the parameters is empty or invalid
        if ($extra_data_config === null || !is_array($default_value_config)) {
            return $fallback;
        }

        // Validate configuration values
        $default_value_config = $this->getDefaultValueConfig($default_value_config);
        if (
            !$default_value_config instanceof QuestionTypeItemDefaultValueConfig
            || !$extra_data_config instanceof QuestionTypeItemExtraDataConfig
            || $extra_data_config->getItemtype() === null
            || !is_a($extra_data_config->getItemtype(), CommonDBTM::class, true)
            || empty($default_value_config->getItemsId())
        ) {
            return $fallback;
        }

        $default_value_data = $default_value_config->jsonSerialize();

        // Load linked item
        /** @var class-string<\CommonDBTM> $itemtype */
        $itemtype = $extra_data_config->getItemtype();
        $item = $itemtype::getById(
            $default_value_config->getItemsId()
        );

        // Replace id and register requirement
        $key = QuestionTypeItemDefaultValueConfig::KEY_ITEMS_ID;
        $default_value_data[$key] = $item->getName();
        $requirements[] = new DataRequirementSpecification(
            $itemtype,
            $item->getName(),
        );

        return new DynamicExportDataField($default_value_data, $requirements);
    }

    #[Override]
    public static function prepareDynamicDefaultValueForImport(
        ?array $extra_data,
        array|int|float|bool|string|null $default_value_data,
        DatabaseMapper $mapper,
    ): array|int|float|bool|string|null {
        $fallback = parent::prepareDynamicDefaultValueForImport(
            $extra_data,
            $default_value_data,
            $mapper,
        );

        // Validate we have two valid configs
        if ($extra_data == null || $default_value_data === null) {
            return $fallback;
        }

        // Validate config values
        $itemtype = $extra_data[QuestionTypeItemExtraDataConfig::ITEMTYPE] ?? "";
        $name = $default_value_data[QuestionTypeItemDefaultValueConfig::KEY_ITEMS_ID] ?? "";
        if (
            !(getItemForItemtype($itemtype) instanceof CommonDBTM)
            || empty($name)
        ) {
            return $fallback;
        }

        // Find item id
        $id = $mapper->getItemId($itemtype, $name);
        $default_value_data[QuestionTypeItemDefaultValueConfig::KEY_ITEMS_ID] = $id;

        return $default_value_data;
    }
}
