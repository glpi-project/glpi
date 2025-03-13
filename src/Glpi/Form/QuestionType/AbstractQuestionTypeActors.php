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

use Exception;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Form\Migration\FormQuestionDataConverterInterface;
use Glpi\Form\Question;
use Group;
use Override;
use Supplier;
use User;

/**
 * "Actors" questions represent an input field for actors (requesters, ...)
 */
abstract class AbstractQuestionTypeActors extends AbstractQuestionType implements FormQuestionDataConverterInterface
{
    /**
     * Retrieve the allowed actor types
     *
     * @return array
     */
    abstract public function getAllowedActorTypes(): array;

    /**
     * Retrieve the right to use to retrieve users
     *
     * @return string
     */
    public function getRightForUsers(): string
    {
        return 'all';
    }

    /**
     * Retrieve the group conditions
     *
     * @return array
     */
    abstract public function getGroupConditions(): array;

    #[Override]
    public function formatDefaultValueForDB(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        $actors_ids = [];
        foreach ($value as $actor) {
            $actor_parts = explode('-', $actor);
            $actors_ids[getItemtypeForForeignKeyField($actor_parts[0])][] = (int) $actor_parts[1];
        }

        // Wrap the array in a config object to serialize it
        $config = new QuestionTypeActorsDefaultValueConfig(
            users_ids: $actors_ids['User'] ?? [],
            groups_ids: $actors_ids['Group'] ?? [],
            suppliers_ids: $actors_ids['Supplier'] ?? []
        );

        return json_encode($config->jsonSerialize());
    }

    #[Override]
    public function validateExtraDataInput(array $input): bool
    {
        // Only one key is allowed and optional: 'is_multiple_actors'.
        // This key must be a valid boolean.
        return (
            isset($input['is_multiple_actors'])
            && count($input) === 1
            && filter_var($input['is_multiple_actors'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== null
        ) || empty($input);
    }

    #[Override]
    public function prepareEndUserAnswer(Question $question, mixed $answer): mixed
    {
        if (empty($answer)) {
            return [];
        }

        if (!is_array($answer)) {
            $answer = [$answer];
        }

        $actors = [];
        foreach ($answer as $actor) {
            // The "0" value can occur when the empty label is selected.
            if (empty($actor)) {
                continue;
            }

            $actor_parts = explode('-', $actor);
            $itemtype = getItemtypeForForeignKeyField($actor_parts[0]);
            $item_id = $actor_parts[1];

            // Check if the itemtype is allowed
            if (!in_array($itemtype, $this->getAllowedActorTypes())) {
                throw new Exception("Invalid actor type: $itemtype");
            }

            // Check if the item exists
            if ($itemtype::getById($item_id) === false) {
                throw new Exception("Invalid actor ID: $item_id");
            }

            $actors[] = [
                'itemtype' => $itemtype,
                'items_id' => $item_id
            ];
        }

        if (!$this->isMultipleActors($question) && count($actors) > 1) {
            throw new Exception("Multiple actors are not allowed");
        }

        return $actors;
    }

    #[Override]
    public function convertDefaultValue(array $rawData): mixed
    {
        return array_map(
            fn ($actor_id) => sprintf('%s-%d', User::getForeignKeyField(), $actor_id),
            json_decode($rawData['default_values']) ?? []
        );
    }

    #[Override]
    public function convertExtraData(array $rawData): mixed
    {
        // Actors question type was always multiple in FormCreator
        return (new QuestionTypeActorsExtraDataConfig(
            is_multiple_actors: true
        ))->jsonSerialize();
    }

    /**
     * Check if the question allows multiple actors
     *
     * @param ?Question $question
     * @return bool
     */
    public function isMultipleActors(?Question $question): bool
    {
        if ($question === null) {
            return false;
        }

        /** @var ?QuestionTypeActorsExtraDataConfig $config */
        $config = $this->getExtraDataConfig(json_decode($question->fields['extra_data'], true) ?? []);
        if ($config === null) {
            return false;
        }

        return $config->isMultipleActors();
    }

    /**
     * Retrieve the default value
     *
     * @param ?Question $question
     * @param bool $multiple
     * @return array
     */
    public function getDefaultValue(?Question $question, bool $multiple = false): array
    {
        // If the question is not set or the default value is empty, we return 0 (default option for dropdowns)
        if (
            $question === null
            || empty($question->fields['default_value'])
        ) {
            return [];
        }

        $config = new QuestionTypeActorsDefaultValueConfig();
        $config = $config->jsonDeserialize(json_decode($question->fields['default_value'], true));

        $default_values = [
            getForeignKeyFieldForItemType(User::class) => $config->getUsersIds(),
            getForeignKeyFieldForItemType(Group::class) => $config->getGroupsIds(),
            getForeignKeyFieldForItemType(Supplier::class) => $config->getSuppliersIds()
        ];

        if ($multiple) {
            return $default_values;
        }

        return [key($default_values) => current($default_values)];
    }

    #[Override]
    public function renderAdministrationTemplate(?Question $question): string
    {
        $template = <<<TWIG
        {% import 'components/form/fields_macros.html.twig' as fields %}

        {% set actors_dropdown = call('Glpi\\\\Form\\\\Dropdown\\\\FormActorsDropdown::show', [
            'default_value',
            values,
            {
                'form_id'         : question.getForm().getId(),
                'multiple'        : false,
                'init'            : init,
                'allowed_types'   : allowed_types,
                'right_for_users' : right_for_users,
                'group_conditions': group_conditions,
                'aria_label'      : aria_label,
                'specific_tags'   : is_multiple_actors ? {
                    'disabled': 'disabled'
                } : {}
            }
        ]) %}
        {% set actors_dropdown_multiple = call('Glpi\\\\Form\\\\Dropdown\\\\FormActorsDropdown::show', [
            'default_value',
            values,
            {
                'form_id'         : question.getForm().getId(),
                'multiple'        : true,
                'init'            : init,
                'allowed_types'   : allowed_types,
                'right_for_users' : right_for_users,
                'group_conditions': group_conditions,
                'aria_label'      : aria_label,
                'specific_tags'   : not is_multiple_actors ? {
                    'disabled': 'disabled'
                } : {}
            }
        ]) %}

        {{ fields.htmlField(
            'default_value',
            actors_dropdown,
            '',
            {
                'disabled'     : is_multiple_actors,
                'no_label'     : true,
                'mb'           : '',
                'wrapper_class': '',
                'field_class': [
                    'actors-dropdown',
                    'col-12',
                    'col-sm-6',
                    not is_multiple_actors ? '' : 'd-none'
                ]|join(' '),
            }
        ) }}
        {{ fields.htmlField(
            'default_value',
            actors_dropdown_multiple,
            '',
            {
                'no_label'     : true,
                'wrapper_class': '',
                'mb'           : '',
                'field_class'  : [
                    'actors-dropdown',
                    'col-12',
                    'col-sm-6',
                    is_multiple_actors ? '' : 'd-none'
                ]|join(' '),
            }
        ) }}
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'init'               => $question != null,
            'question'           => $question,
            'values'             => $this->getDefaultValue($question, $this->isMultipleActors($question)),
            'allowed_types'      => $this->getAllowedActorTypes(),
            'is_multiple_actors' => $this->isMultipleActors($question),
            'aria_label'         => __('Select an actor...'),
            'right_for_users'    => $this->getRightForUsers(),
            'group_conditions'   => $this->getGroupConditions()
        ]);
    }


    #[Override]
    public function renderAdministrationOptionsTemplate(?Question $question): string
    {
        $template = <<<TWIG
            {% set rand = random() %}

            <div id="is_multiple_actors_{{ rand }}" class="d-flex gap-2">
                <label class="form-check form-switch mb-0">
                    <input type="hidden" name="is_multiple_actors" value="0"
                    data-glpi-form-editor-specific-question-extra-data>
                    <input class="form-check-input" type="checkbox" name="is_multiple_actors"
                        value="1" {{ is_multiple_actors ? 'checked' : '' }}
                        onchange="handleMultipleActorsCheckbox_{{ rand }}(this)"
                        data-glpi-form-editor-specific-question-extra-data>
                    <span class="form-check-label">{{ is_multiple_actors_label }}</span>
                </label>
            </div>

            <script>
                function handleMultipleActorsCheckbox_{{ rand }}(input) {
                    const is_checked = $(input).is(':checked');
                    const selects = $(input).closest('section[data-glpi-form-editor-question]')
                        .find('div .actors-dropdown');

                    {# Disable all selects and toggle their visibility, then enable the right ones #}
                    selects.toggleClass('d-none').find('select').prop('disabled', is_checked)
                        .filter('[multiple]').prop('disabled', !is_checked);

                    {# Handle hidden input for multiple actors #}
                    selects.find('input[type="hidden"]').prop('disabled', !is_checked);
                }
            </script>
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'is_multiple_actors' => $this->isMultipleActors($question),
            'is_multiple_actors_label' => __('Allow multiple actors')
        ]);
    }

    #[Override]
    public function formatRawAnswer(mixed $answer): string
    {
        $formatted_actors = [];
        foreach ($answer as $actor) {
            foreach ($this->getAllowedActorTypes() as $type) {
                if ($actor['itemtype'] === $type) {
                    $item = $type::getById($actor['items_id']);
                    if ($item !== false) {
                        $formatted_actors[] = $item->getName();
                    }
                }
            }
        }

        return implode(', ', $formatted_actors);
    }

    #[Override]
    public function renderEndUserTemplate(Question $question): string
    {
        $template = <<<TWIG
        {% import 'components/form/fields_macros.html.twig' as fields %}

        {% set actors_dropdown = call('Glpi\\\\Form\\\\Dropdown\\\\FormActorsDropdown::show', [
            question.getEndUserInputName(),
            value,
            {
                'form_id'      : question.getForm().getId(),
                'multiple'     : is_multiple_actors,
                'allowed_types': allowed_types,
                'aria_label'   : aria_label,
                'mb'           : ''
            }
        ]) %}

        {{ fields.htmlField(
            question.getEndUserInputName(),
            actors_dropdown,
            '',
            {
                'no_label'     : true,
                'wrapper_class': '',
                'mb'           : '',
                'field_class'  : [
                    'col-12',
                    'col-sm-6',
                ]|join(' '),
            }
        ) }}
TWIG;

        $is_multiple_actors = $this->isMultipleActors($question);
        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'value'              => $this->getDefaultValue($question, $is_multiple_actors),
            'question'           => $question,
            'allowed_types'      => $this->getAllowedActorTypes(),
            'is_multiple_actors' => $is_multiple_actors,
            'aria_label'         => $question->fields['name']
        ]);
    }

    #[Override]
    public function getCategory(): QuestionTypeCategory
    {
        return QuestionTypeCategory::ACTORS;
    }

    #[Override]
    public function isAllowedForUnauthenticatedAccess(): bool
    {
        return false;
    }

    #[Override]
    public function getExtraDataConfigClass(): ?string
    {
        return QuestionTypeActorsExtraDataConfig::class;
    }

    public function getDefaultValueConfigClass(): ?string
    {
        return QuestionTypeActorsDefaultValueConfig::class;
    }
}
