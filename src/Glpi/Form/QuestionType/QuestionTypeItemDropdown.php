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

use DbUtils;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Form\Question;
use ITILCategory;
use Override;
use Session;

use function Safe\json_decode;

final class QuestionTypeItemDropdown extends QuestionTypeItem
{
    public function __construct()
    {
        parent::__construct();

        $this->itemtype_aria_label = __('Select a dropdown type');
        $this->items_id_aria_label = __('Select a dropdown item');
    }

    public function renderAdvancedConfigurationTemplate(?Question $question): string
    {
        $twig = TemplateRenderer::getInstance();
        return $twig->render(
            'pages/admin/form/question_type/item_dropdown/advanced_configuration.html.twig',
            [
                'question'          => $question,
                'categories_filter' => $this->getCategoriesFilter($question),
                'root_items_id'     => $this->getRootItemsId($question),
                'subtree_depth'     => $this->getSubtreeDepth($question),
            ]
        );
    }

    public function getAllowedItemtypes(): array
    {
        $dropdown_itemtypes = Dropdown::getStandardDropdownItemTypes(check_rights: false);

        /**
         * It is necessary to replace the values with their corresponding keys
         * because the values returned by getStandardDropdownItemTypes() are
         * translations and not item type keys.
         * The array_keys() function is not used because it does not work for nested arrays.
         */
        array_walk_recursive($dropdown_itemtypes, function (&$value, $key) {
            $value = $key;
        });

        return $dropdown_itemtypes;
    }

    #[Override]
    public function getName(): string
    {
        return _n('Dropdown', 'Dropdowns', Session::getPluralNumber());
    }

    #[Override]
    public function getIcon(): string
    {
        return 'ti ti-edit';
    }

    #[Override]
    public function getWeight(): int
    {
        return 30;
    }

    #[Override]
    public function getExtraDataConfigClass(): string
    {
        return QuestionTypeItemDropdownExtraDataConfig::class;

    }

    #[Override]
    public function convertExtraData(array $rawData): mixed
    {
        $values = json_decode($rawData['values'] ?? '', true) ?? [];
        $categories_filter = [];
        if (isset($values['show_ticket_categories'])) {
            $categories_filter = match ($values['show_ticket_categories']) {
                'request' => ['request'],
                'incident' => ['incident'],
                'both' => ['request', 'incident'],
                'change' => ['change'],
                'all' => ['request', 'incident', 'change'],
                default => []
            };
        }

        return (new QuestionTypeItemDropdownExtraDataConfig(
            itemtype: $rawData['itemtype'] ?? null,
            categories_filter: $categories_filter,
            root_items_id: $values['show_tree_root'] ?? 0,
            subtree_depth: $values['show_tree_depth'] ?? 0
        ))->jsonSerialize();
    }

    #[Override]
    public function prepareExtraData(array $input): array
    {
        $input = parent::prepareExtraData($input);

        // Categories filter may be empty, so we ensure it's an array
        if (isset($input['categories_filter']) && empty($input['categories_filter'])) {
            $input['categories_filter'] = [];
        }

        return $input;
    }

    #[Override]
    public function validateExtraDataInput(array $input): bool
    {
        // Check if the categories_filter is set and is an array
        if (
            !isset($input['categories_filter'])
            || (!is_array($input['categories_filter']) && !empty($input['categories_filter']))
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

        if (!parent::validateExtraDataInput($input)) {
            return false;
        }

        return true;
    }

    /**
     * Retrieve filter ticket categories for the item question type
     *
     * @param Question|null $question The question to retrieve the filter for
     * @return ?array
     */
    public function getCategoriesFilter(?Question $question): ?array
    {
        if ($question === null) {
            return ['request', 'incident', 'problem', 'change'];
        }

        /** @var ?QuestionTypeItemDropdownExtraDataConfig $config */
        $config = $this->getExtraDataConfig(json_decode($question->fields['extra_data'], true) ?? []);
        if ($config === null) {
            return null;
        }

        return $config->getCategoriesFilter();
    }

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

    public function getDropdownRestrictionParams(?Question $question): array
    {
        $params   = [];
        $itemtype = $this->getDefaultValueItemtype($question);
        $item     = getItemForItemtype($itemtype);
        if ($question === null || $itemtype === null) {
            return $params;
        }

        if (is_a($itemtype, ITILCategory::class, true)) {
            // Ensure only visible categories are shown
            if (Session::getCurrentInterface() == "helpdesk") {
                $params['is_helpdeskvisible'] = 1;
            }

            // Apply categories filter if itemtype is an ITILCategory
            $categories_filter = $this->getCategoriesFilter($question);
            if (is_array($categories_filter) && count($categories_filter) > 0) {
                $type_params = [];
                foreach ($categories_filter as $category) {
                    $key = match ($category) {
                        'request'  => 'is_request',
                        'incident' => 'is_incident',
                        'problem'  => 'is_problem',
                        'change'   => 'is_change',
                        default    => throw new \InvalidArgumentException(
                            sprintf('Unknown category filter: %s', $category)
                        ),
                    };

                    $type_params[$itemtype::getTableField($key)] = 1;
                }

                $params[] = ['OR' => $type_params];
            }
        }

        if ($item->maybeActive()) {
            // Ensure only active items are shown
            $params[$itemtype::getTableField('is_active')] = 1;
        }

        // Apply specific root
        $root_items_id = $this->getRootItemsId($question);
        if ($root_items_id > 0) {
            $sons = (new DbUtils())->getSonsOf(
                $itemtype::getTable(),
                $root_items_id
            );

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

        return ['WHERE' => $params];
    }
}
