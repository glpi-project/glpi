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
use CommonTreeDropdown;
use ConsumableItem;
use DbUtils;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\Condition\ConditionHandler\ItemAsTextConditionHandler;
use Glpi\Form\Condition\ConditionHandler\ItemConditionHandler;
use Glpi\Form\Condition\ConditionValueTransformerInterface;
use Glpi\Form\Condition\UsedAsCriteriaInterface;
use Glpi\Form\Export\Context\DatabaseMapper;
use Glpi\Form\Export\Serializer\DynamicExportDataField;
use Glpi\Form\Export\Specification\DataRequirementSpecification;
use Glpi\Form\Migration\FormQuestionDataConverterInterface;
use Glpi\Form\Question;
use InvalidArgumentException;
use Line;
use LogicException;
use Override;
use PassiveDCEquipment;
use PDU;
use Session;
use Software;
use TicketRecurrent;

use function Safe\json_decode;
use function Safe\json_encode;

class QuestionTypeItem extends AbstractQuestionType implements
    FormQuestionDataConverterInterface,
    UsedAsCriteriaInterface,
    ConditionValueTransformerInterface
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
        // Decode JSON string to array
        $values = [];
        if (!empty($rawData['values']) && is_string($rawData['values'])) {
            $values = json_decode($rawData['values'], true);
        }

        $root_items_id = 0;
        if (isset($values['show_tree_root']) && is_numeric($values['show_tree_root'])) {
            $root_items_id = (int) $values['show_tree_root'];
        }

        $subtree_depth = 0;
        if (
            isset($values['show_tree_depth'])
            && is_numeric($values['show_tree_depth'])
            && $values['show_tree_depth'] > 0
        ) {
            $subtree_depth = (int) $values['show_tree_depth'];
        }

        $selectable_tree_root = false;
        if (
            isset($values['selectable_tree_root'])
            && is_numeric($values['selectable_tree_root'])
        ) {
            $selectable_tree_root = (bool) $values['selectable_tree_root'];
        }

        return (new QuestionTypeItemExtraDataConfig(
            itemtype: $rawData['itemtype'] ?? null,
            root_items_id: $root_items_id,
            subtree_depth: $subtree_depth,
            selectable_tree_root: $selectable_tree_root
        ))->jsonSerialize();
    }

    /**
     * Retrieve the allowed item types
     *
     * @return array
     */
    public function getAllowedItemtypes(): array
    {
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
            __('Administration') => $CFG_GLPI['admin_types'],
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

        // Check if root_items_id is set and is numeric
        if (
            !isset($input['root_items_id'])
            || !is_numeric($input['root_items_id'])
        ) {
            return false;
        }

        // Check if subtree_depth is set and is numeric
        if (
            !isset($input['subtree_depth'])
            || !is_numeric($input['subtree_depth'])
        ) {
            return false;
        }

        // Check if selectable_tree_root is set and is boolean (optional field)
        if (
            !isset($input['selectable_tree_root'])
            || !(is_numeric($input['selectable_tree_root']) || is_bool($input['selectable_tree_root']))
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
        $twig = TemplateRenderer::getInstance();
        return $twig->render(
            'pages/admin/form/question_type/item/administration_template.html.twig',
            [
                'init'             => $question != null,
                'question'         => $question,
                'question_type'    => $this::class,
                'default_itemtype' => $this->getDefaultValueItemtype($question),
                'default_items_id' => $this->getDefaultValueItemId($question),
                'itemtypes'        => $this->getAllowedItemtypes(),
                'aria_label'       => $this->items_id_aria_label,
                'advanced_config'  => $this->renderAdvancedConfigurationTemplate($question),
            ]
        );
    }

    public function renderAdvancedConfigurationTemplate(?Question $question): string
    {
        $itemtype = $this->getDefaultValueItemtype($question);
        if ($itemtype === null) {
            // Retrieve first allowed itemtype if none is set
            $itemtype = current(current($this->getAllowedItemtypes()));
        }

        $common_tree_dropdowns = [];
        foreach ($this->getAllowedItemtypes() as $types) {
            foreach ($types as $type) {
                if (is_a($type, CommonTreeDropdown::class, true)) {
                    $common_tree_dropdowns[$type] = $type;
                }
            }
        }

        $twig = TemplateRenderer::getInstance();
        return $twig->render(
            'pages/admin/form/question_type/item/advanced_configuration.html.twig',
            [
                'question'              => $question,
                'itemtype'              => $itemtype,
                'root_items_id'         => $this->getRootItemsId($question),
                'subtree_depth'         => $this->getSubtreeDepth($question),
                'selectable_tree_root'  => $this->isSelectableTreeRoot($question),
                'common_tree_dropdowns' => $common_tree_dropdowns,
            ]
        );
    }

    #[Override]
    public function renderEndUserTemplate(Question $question): string
    {
        $twig = TemplateRenderer::getInstance();
        return $twig->render(
            'pages/admin/form/question_type/item/end_user_template.html.twig',
            [
                'question'                    => $question,
                'itemtype'                    => $this->getDefaultValueItemtype($question) ?? '0',
                'default_items_id'            => $this->getDefaultValueItemId($question),
                'aria_label'                  => $question->fields['name'],
                'sub_types'                   => $this->getSubTypes(),
                'dropdown_restriction_params' => $this->getDropdownRestrictionParams($question),
            ]
        );
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

        if (!$question_config->getItemtype()) {
            return parent::getConditionHandlers($question_config);
        }

        return array_merge(
            parent::getConditionHandlers($question_config),
            [
                new ItemConditionHandler($question_config->getItemtype()),
                new ItemAsTextConditionHandler($question_config->getItemtype()),
            ],
        );
    }

    #[Override]
    public function transformConditionValueForComparisons(mixed $value, ?JsonFieldInterface $question_config): string
    {
        // Handle empty cases first
        if (empty($value)) {
            return '';
        }

        // If it's a JSON string (from database), decode it
        if (is_string($value) && json_validate($value)) {
            $value = json_decode($value, true);
        }

        // Check the extra data config
        if (!($question_config instanceof QuestionTypeItemExtraDataConfig)) {
            throw new LogicException(
                'Expected QuestionTypeItemExtraDataConfig, got ' . ($question_config !== null ? get_class($question_config) : self::class)
            );
        }

        // Get the default value config
        $config = $this->getDefaultValueConfig($value);
        if (!($config instanceof QuestionTypeItemDefaultValueConfig)) {
            throw new LogicException(
                'Expected QuestionTypeItemDefaultValueConfig, got ' . ($config !== null ? get_class($config) : self::class)
            );
        }

        // Check if items_id is set and valid
        if ($config->getItemsId() < 0) {
            // If items_id is not >= 0, consider it empty
            return '';
        }

        $item = getItemForItemtype($question_config->getItemtype());
        if ($item && $item->getfromDB($config->getItemsId())) {
            return $item->getName();
        }

        return '';
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
        /** @var class-string<CommonDBTM> $itemtype */
        $itemtype = $extra_data_config->getItemtype();
        $item = $itemtype::getById(
            $default_value_config->getItemsId()
        );
        if (!$item) {
            // Invalid item, return empty data with no requirements
            return new DynamicExportDataField(null, []);
        }

        // Replace id and register requirement
        $key = QuestionTypeItemDefaultValueConfig::KEY_ITEMS_ID;
        $requirement = DataRequirementSpecification::fromItem($item);
        $requirements[] = $requirement;
        $default_value_data[$key] = $requirement->name;

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

    /**
     * Get parameters for dropdown restrictions based on the question
     *
     * @param Question|null $question The question to retrieve the parameters for
     * @return array
     */
    public function getDropdownRestrictionParams(?Question $question): array
    {
        $params   = [];
        $itemtype = $this->getDefaultValueItemtype($question);
        $item     = getItemForItemtype($itemtype);
        if ($question === null || $itemtype === null) {
            return $params;
        }

        if ($item->maybeActive()) {
            // Ensure only active items are shown
            $params[$itemtype::getTableField('is_active')] = 1;
        }

        if (is_a($itemtype, CommonTreeDropdown::class, true)) {
            // Apply specific root
            $root_items_id = $this->getRootItemsId($question);
            if ($root_items_id > 0) {
                $sons = (new DbUtils())->getSonsOf(
                    $itemtype::getTable(),
                    $root_items_id
                );

                if (!$this->isSelectableTreeRoot($question)) {
                    unset($sons[$root_items_id]);
                }

                $params[$itemtype::getTableField('id')] = $sons;
                $root_item = $item;
                if ($root_item->getFromDB($root_items_id)) {
                    $root_item_level = $root_item->fields['level'];
                }
            }

            // Apply max level restriction if subtree depth is set
            $subtree_depth = $this->getSubtreeDepth($question);
            if ($subtree_depth > 0) {
                $params[$itemtype::getTableField('level')] = ['<=', $subtree_depth + ($root_item_level ?? 0)];
            }
        }

        return ['WHERE' => $params];
    }

    #[Override]
    public function getTargetQuestionType(array $rawData): string
    {
        return static::class;
    }


    #[Override]
    public function beforeConversion(array $rawData): void {}

    /**
     * Retrieve root items ID for the item question type
     *
     * @param Question|null $question The question to retrieve the root items ID for
     * @return int
     */
    public function getRootItemsId(?Question $question): int
    {
        if ($question === null) {
            return 0;
        }

        /** @var ?QuestionTypeItemDropdownExtraDataConfig $config */
        $config = $this->getExtraDataConfig(json_decode($question->fields['extra_data'], true) ?? []);
        if ($config === null) {
            return 0;
        }

        return $config->getRootItemsId();
    }

    /**
     * Retrieve subtree depth for the item question type
     *
     * @param Question|null $question The question to retrieve the subtree depth for
     * @return int
     */
    public function getSubtreeDepth(?Question $question): int
    {
        if ($question === null) {
            return 0;
        }

        /** @var ?QuestionTypeItemDropdownExtraDataConfig $config */
        $config = $this->getExtraDataConfig(json_decode($question->fields['extra_data'], true) ?? []);
        if ($config === null) {
            return 0;
        }

        return $config->getSubtreeDepth();
    }

    /**
     * Check if tree root is selectable for the item question type
     *
     * @param Question|null $question The question to check
     * @return bool
     */
    public function isSelectableTreeRoot(?Question $question): bool
    {
        if ($question === null) {
            return false;
        }

        /** @var ?QuestionTypeItemExtraDataConfig $config */
        $config = $this->getExtraDataConfig(json_decode($question->fields['extra_data'], true) ?? []);
        if ($config === null) {
            return false;
        }

        return $config->isSelectableTreeRoot();
    }

    #[Override]
    public function exportDynamicExtraData(
        ?array $extra_data_config,
    ): DynamicExportDataField {
        $fallback = parent::exportDynamicExtraData($extra_data_config);

        // Stop here if value is invalid or empty
        $itemtype = $extra_data_config[QuestionTypeItemExtraDataConfig::ITEMTYPE] ?? "";
        $root_id = $extra_data_config[QuestionTypeItemDropdownExtraDataConfig::ROOT_ITEMS_ID] ?? 0;
        if ($root_id <= 0 || !is_a($itemtype, CommonDBTM::class, true)) {
            return $fallback;
        }

        // Load item
        $item = $itemtype::getById($root_id);

        // Replace id and register requirement
        $requirement = DataRequirementSpecification::fromItem($item);
        $extra_data_config[QuestionTypeItemDropdownExtraDataConfig::ROOT_ITEMS_ID] = $requirement->name;

        return new DynamicExportDataField($extra_data_config, [$requirement]);
    }

    #[Override]
    public static function prepareDynamicExtraDataForImport(
        ?array $extra_data,
        DatabaseMapper $mapper,
    ): ?array {
        $fallback = parent::prepareDynamicExtraDataForImport(
            $extra_data,
            $mapper,
        );
        if ($extra_data == null) {
            return $fallback;
        }

        // Validate config values
        $itemtype = $extra_data[QuestionTypeItemExtraDataConfig::ITEMTYPE] ?? "";
        $name = $extra_data[QuestionTypeItemDropdownExtraDataConfig::ROOT_ITEMS_ID] ?? "";
        if (
            !(getItemForItemtype($itemtype) instanceof CommonDBTM)
            || empty($name)
            // Both these values represent the root entity, no need to map it
            || $name == 0
            || $name == -1
        ) {
            return $fallback;
        }

        // Find item id
        $id = $mapper->getItemId($itemtype, $name);
        $extra_data[QuestionTypeItemDropdownExtraDataConfig::ROOT_ITEMS_ID] = $id;

        return $extra_data;
    }
}
