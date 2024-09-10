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

namespace Glpi\Form\QuestionType;

use Glpi\Application\View\TemplateRenderer;
use Glpi\Form\Question;
use Override;

/**
 * "Actors" questions represent an input field for actors (requesters, ...)
 */
abstract class AbstractQuestionTypeActors extends AbstractQuestionType
{
    /**
     * Retrieve the allowed actor types
     *
     * @return array
     */
    abstract public function getAllowedActorTypes(): array;

    #[Override]
    public function formatDefaultValueForDB(mixed $value): ?string
    {
        if (is_array($value)) {
            return implode(',', $value);
        }

        return $value;
    }

    #[Override]
    public function validateExtraDataInput(array $input): bool
    {
        $allowed_keys = [
            'is_multiple_actors'
        ];

        return empty(array_diff(array_keys($input), $allowed_keys))
            && array_reduce($input, fn($carry, $value) => $carry && preg_match('/^[01]$/', $value), true);
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

        return $question->getExtraDatas()['is_multiple_actors'] ?? false;
    }

    /**
     * Retrieve the default value
     *
     * @param ?Question $question
     * @param bool $multiple
     * @return int
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

        $default_values = [];
        $raw_default_values = explode(',', $question->fields['default_value']);
        foreach ($raw_default_values as $raw_default_value) {
            $entry = explode('-', $raw_default_value);
            $default_values[$entry[0]][] = $entry[1];
        }

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
                'multiple': false,
                'init': init,
                'allowed_types': allowed_types
            }
        ]) %}
        {% set actors_dropdown_multiple = call('Glpi\\\\Form\\\\Dropdown\\\\FormActorsDropdown::show', [
            'default_value',
            values,
            {
                'multiple': true,
                'init': init,
                'allowed_types': allowed_types
            }
        ]) %}

        {{ fields.htmlField(
            'default_value',
            actors_dropdown,
            '',
            {
                'disabled': is_multiple_actors,
                'no_label': true,
                'field_class': [
                    'actors-dropdown',
                    'col-12',
                    'col-sm-6',
                    not is_multiple_actors ? '' : 'd-none'
                ]|join(' ')
            }
        ) }}
        {{ fields.htmlField(
            'default_value',
            actors_dropdown_multiple,
            '',
            {
                'no_label': true,
                'field_class': [
                    'actors-dropdown',
                    'col-12',
                    'col-sm-6',
                    is_multiple_actors ? '' : 'd-none'
                ]|join(' ')
            }
        ) }}
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'init'               => $question != null,
            'values'             => $this->getDefaultValue($question, $this->isMultipleActors($question)),
            'allowed_types'      => $this->getAllowedActorTypes(),
            'is_multiple_actors' => $this->isMultipleActors($question)
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
                    const selects = $(input).closest('div[data-glpi-form-editor-question]')
                        .find('div .actors-dropdown');

                    {# Disable all selects and toggle their visibility, then enable the right ones #}
                    selects.toggleClass('d-none').find('select').prop('disabled', is_checked)
                        .filter('[multiple]').prop('disabled', !is_checked);
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
    public function renderAnswerTemplate(mixed $answer): string
    {
        $template = <<<TWIG
            <div class="form-control-plaintext">
                {% for itemtype, actors_id in actors %}
                    {% for actor_id in actors_id %}
                        {{ get_item_link(itemtype, actor_id) }}
                    {% endfor %}
                {% endfor %}
            </div>
TWIG;

        $actors = [];
        foreach ($answer as $actor) {
            foreach ($this->getAllowedActorTypes() as $type) {
                if (strpos($actor, $type::getForeignKeyField()) === 0) {
                    $actors[$type][] = (int)substr($actor, strlen($type::getForeignKeyField()) + 1);
                    break;
                }
            }
        }

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'actors' => $actors
        ]);
    }

    #[Override]
    public function formatRawAnswer(mixed $answer): string
    {
        $formatted_actors = [];
        foreach ($answer as $actor) {
            foreach ($this->getAllowedActorTypes() as $type) {
                if (strpos($actor, $type::getForeignKeyField()) === 0) {
                    $items_id = (int)substr($actor, strlen($type::getForeignKeyField()) + 1);
                    $item     = $type::getById($items_id);

                    if ($item !== null) {
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
                'multiple': is_multiple_actors,
                'allowed_types': allowed_types
            }
        ]) %}

        {{ fields.htmlField(
            question.getEndUserInputName(),
            actors_dropdown,
            '',
            {
                'no_label': true,
                'field_class': [
                    'col-12',
                    'col-sm-6',
                ]|join(' ')
            }
        ) }}
TWIG;

        $is_multiple_actors = $this->isMultipleActors($question);
        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'value'              => $this->getDefaultValue($question, $is_multiple_actors),
            'question'           => $question,
            'allowed_types'      => $this->getAllowedActorTypes(),
            'is_multiple_actors' => $is_multiple_actors
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
}
