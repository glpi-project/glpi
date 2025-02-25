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
use ConsumableItem;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Form\Migration\FormQuestionDataConverterInterface;
use Glpi\Form\Question;
use Line;
use Override;
use PassiveDCEquipment;
use PDU;
use Session;
use Software;
use TicketRecurrent;

class QuestionTypeItem extends AbstractQuestionType implements FormQuestionDataConverterInterface
{
    protected string $itemtype_aria_label;
    protected string $items_id_aria_label;

    #[Override]
    public function __construct()
    {
        parent::__construct();

        $this->itemtype_aria_label = __('Select an itemtype');
        $this->items_id_aria_label = __('Select an item');
    }

    #[Override]
    public function formatDefaultValueForDB(mixed $value): ?string
    {
        if (!is_numeric($value)) {
            return null;
        }

        return json_encode((new QuestionTypeItemDefaultValueConfig((int) $value))->jsonSerialize());
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
            'aria_label'       => $this->items_id_aria_label,
            'sub_types'        => $this->getSubTypes(),
        ]);
    }

    #[Override]
    public function formatRawAnswer(mixed $answer): string
    {
        $item = $answer['itemtype']::getById($answer['items_id']);
        if (!$item) {
            return '';
        }

        return $item->fields['name'];
    }

    #[Override]
    public function getCategory(): QuestionTypeCategory
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
}
