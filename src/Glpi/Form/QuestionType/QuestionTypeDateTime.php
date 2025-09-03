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

use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\Condition\ConditionHandler\DateAndTimeConditionHandler;
use Glpi\Form\Condition\ConditionHandler\DateConditionHandler;
use Glpi\Form\Condition\ConditionHandler\TimeConditionHandler;
use Glpi\Form\Condition\UsedAsCriteriaInterface;
use Glpi\Form\Migration\FormQuestionDataConverterInterface;
use Glpi\Form\Question;
use InvalidArgumentException;
use Override;
use RuntimeException;
use Safe\DateTime;
use Safe\Exceptions\JsonException;

use function Safe\json_decode;
use function Safe\preg_match;

/**
 * Short answers are single line inputs used to answer simple questions.
 */
class QuestionTypeDateTime extends AbstractQuestionType implements FormQuestionDataConverterInterface, UsedAsCriteriaInterface
{
    #[Override]
    public function getCategory(): QuestionTypeCategoryInterface
    {
        return QuestionTypeCategory::DATE_AND_TIME;
    }

    #[Override]
    public function convertDefaultValue(array $rawData): mixed
    {
        return $rawData['default_values'] ?? null;
    }

    #[Override]
    public function convertExtraData(array $rawData): mixed
    {
        return (new QuestionTypeDateTimeExtraDataConfig(
            is_default_value_current_time: false,
            is_date_enabled: $rawData['fieldtype'] !== 'time',
            is_time_enabled: $rawData['fieldtype'] !== 'date',
        ))->jsonSerialize();
    }

    public function getInputType(?Question $question, bool $ignoreDefaultValueIsCurrentTime = false): string
    {
        if (
            $question !== null
            && $this->isDefaultValueCurrentTime($question)
            && !$ignoreDefaultValueIsCurrentTime
        ) {
            return 'text';
        }

        if (
            $question === null
            || !$this->isTimeEnabled($question)
        ) {
            return 'date';
        }

        if ($this->isDateEnabled($question)) {
            return 'datetime-local';
        }

        return 'time';
    }

    public function getDefaultValue(?Question $question): ?string
    {
        $value = '';
        if ($question !== null) {
            if ($this->isDefaultValueCurrentTime($question)) {
                switch ($this->getInputType($question, true)) {
                    case 'date':
                        $value = (new DateTime())->format('Y-m-d');
                        break;
                    case 'time':
                        $value = (new DateTime())->format('H:i');
                        break;
                    case 'datetime-local':
                        $value = (new DateTime())->format('Y-m-d\TH:i');
                        break;
                }
            } else {
                $value = $question->fields['default_value'];
            }
        }

        return $value;
    }

    public function getPlaceholders(): array
    {
        return [
            'input'         => [
                'date'            => _n('Date', 'Dates', 1),
                'time'            => _n('Time', 'Times', 1),
                'datetime-local'  => __('Date and time'),
            ],
            'default_value' => [
                'date'            => __('Current date'),
                'time'            => __('Current time'),
                'datetime-local'  => __('Current date and time'),
            ],
        ];
    }

    public function formatAnswer(string $answer): string
    {
        if (str_contains($answer, 'T')) {
            return (new DateTime($answer))->format('Y-m-d H:i');
        }

        return $answer;
    }

    public function isDefaultValueCurrentTime(?Question $question): bool
    {
        if ($question === null) {
            return false;
        }

        try {
            /** @var ?QuestionTypeDateTimeExtraDataConfig $config */
            $config = $this->getExtraDataConfig(json_decode($question->fields['extra_data'], true) ?? []);
            if ($config === null) {
                return false;
            }
            return $config->isDefaultValueCurrentTime();
        } catch (JsonException $e) {
            return false;
        }
    }

    public function isDateEnabled(?Question $question): bool
    {
        if ($question === null) {
            return true;
        }

        try {
            /** @var ?QuestionTypeDateTimeExtraDataConfig $config */
            $config = $this->getExtraDataConfig(json_decode($question->fields['extra_data'], true) ?? []);
            if ($config === null) {
                return true;
            }
            return $config->isDateEnabled();
        } catch (JsonException $e) {
            return true;
        }
    }

    public function isTimeEnabled(?Question $question): bool
    {
        if ($question === null) {
            return false;
        }

        try {
            /** @var ?QuestionTypeDateTimeExtraDataConfig $config */
            $config = $this->getExtraDataConfig(json_decode($question->fields['extra_data'], true) ?? []);
            if ($config === null) {
                return false;
            }
            return $config->isTimeEnabled();
        } catch (JsonException $e) {
            return false;
        }
    }

    #[Override]
    public function validateExtraDataInput(array $input): bool
    {
        $allowed_keys = [
            'is_default_value_current_time',
            'is_date_enabled',
            'is_time_enabled',
        ];

        return empty(array_diff(array_keys($input), $allowed_keys))
            && array_reduce(
                $input,
                fn($carry, $value) => $carry && (is_bool($value) || preg_match('/^[01]$/', $value)),
                true
            );
    }

    #[Override]
    public function renderAdministrationTemplate(?Question $question): string
    {
        $template = <<<TWIG
            {% set rand = random() %}

            <div class="row g-2">
                <div class="col-6">
                    <input
                        class="form-control"
                        type="{{ input_type }}"
                        id="date_input_{{ rand }}"
                        name="default_value"
                        placeholder="{{ placeholders.input[input_type_ignore_text] }}"
                        value="{{ default_value }}"
                        aria-label="{{ aria_label }}"
                        {{ is_default_value_current_time ? 'disabled' : '' }}
                    />
                </div>
                <div data-glpi-form-editor-question-extra-details class="col-auto d-flex align-items-center ms-1 mt-0">
                    <label class="form-check form-switch m-0 d-flex align-items-center gap-2">
                        <input type="hidden" name="is_default_value_current_time" value="0"
                            data-glpi-form-editor-specific-question-extra-data>
                        <input id="is_default_value_current_time_{{ rand }}" name="is_default_value_current_time" class="form-check-input"
                            type="checkbox" value="1" {{ is_default_value_current_time ? 'checked' : '' }}
                            data-glpi-form-editor-specific-question-extra-data>
                        <span>{{ placeholders.default_value[input_type_ignore_text] }}</span>
                    </label>
                </div>
            </div>

            {% if question == null %}
                <script>
                    import("/js/modules/Forms/QuestionDateTime.js").then((m) => {
                        new m.GlpiFormQuestionTypeDateTime({{ placeholders|json_encode|raw }});
                    });
                </script>
            {% endif %}
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'question'          => $question,
            'question_type'     => $this::class,
            'default_value'     => $this->isDefaultValueCurrentTime($question)
                ? '' : $question->fields['default_value'] ?? '',
            'input_type'        => $this->getInputType($question),
            'input_type_ignore_text'        => $this->getInputType($question, true),
            'is_default_value_current_time' => $this->isDefaultValueCurrentTime($question),
            'placeholders'      => $this->getPlaceholders(),
            'aria_label'        =>  __('Default value'),
        ]);
    }

    #[Override]
    public function renderAdministrationOptionsTemplate(?Question $question): string
    {
        $template = <<<TWIG
            {% set rand = random() %}

            <div class="d-flex gap-2">
                <label class="form-check form-switch mb-0">
                    <input type="hidden" name="is_date_enabled" value="0"
                    data-glpi-form-editor-specific-question-extra-data>
                    <input class="form-check-input" type="checkbox" name="is_date_enabled"
                        id="is_date_enabled_{{ rand }}"
                        value="1" {{ is_date_enabled ? 'checked' : '' }}
                        data-glpi-form-editor-specific-question-extra-data>
                    <span class="form-check-label">{{ labels.date }}</span>
                </label>
                <label class="form-check form-switch mb-0">
                    <input type="hidden" name="is_time_enabled" value="0"
                    data-glpi-form-editor-specific-question-extra-data>
                    <input class="form-check-input" type="checkbox" name="is_time_enabled"
                        id="is_time_enabled_{{ rand }}"
                        value="1" {{ is_time_enabled ? 'checked' : '' }}
                        data-glpi-form-editor-specific-question-extra-data>
                    <span class="form-check-label">{{ labels.time }}</span>
                </label>
            </div>
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'question' => $question,
            'is_date_enabled' => $this->isDateEnabled($question),
            'is_time_enabled' => $this->isTimeEnabled($question),
            'labels' => [
                'date' => _n('Date', 'Dates', 1),
                'time' => _n('Time', 'Times', 1),
            ],
        ]);
    }

    #[Override]
    public function renderEndUserTemplate(
        Question $question,
    ): string {
        $template = <<<TWIG
            <input
                type="{{ input_type }}"
                class="form-control w-50"
                name="{{ question.getEndUserInputName() }}"
                value="{{ default_value }}"
                {{ question.fields.is_mandatory ? 'required' : '' }}
            >
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'question'      => $question,
            'input_type'    => $this->getInputType($question, true),
            'default_value' => $this->getDefaultValue($question) ?? '',
        ]);
    }

    #[Override]
    public function formatRawAnswer(mixed $answer, Question $question): string
    {
        return $this->formatAnswer($answer);
    }

    #[Override]
    public function isAllowedForUnauthenticatedAccess(): bool
    {
        return true;
    }

    #[Override]
    public function getExtraDataConfigClass(): ?string
    {
        return QuestionTypeDateTimeExtraDataConfig::class;
    }

    #[Override]
    public function getConditionHandlers(
        ?JsonFieldInterface $question_config
    ): array {
        if (!$question_config instanceof QuestionTypeDateTimeExtraDataConfig) {
            throw new InvalidArgumentException();
        }

        $use_date = $question_config->isDateEnabled();
        $use_time = $question_config->isTimeEnabled();
        if ($use_date && !$use_time) {
            return array_merge(parent::getConditionHandlers($question_config), [new DateConditionHandler()]);
        } elseif (!$use_date && $use_time) {
            return array_merge(parent::getConditionHandlers($question_config), [new TimeConditionHandler()]);
        } elseif ($use_date && $use_time) {
            return array_merge(parent::getConditionHandlers($question_config), [new DateAndTimeConditionHandler()]);
        } else {
            // Impossible, should never happen.
            throw new RuntimeException();
        }
    }

    #[Override]
    public function getTargetQuestionType(array $rawData): string
    {
        return static::class;
    }


    #[Override]
    public function beforeConversion(array $rawData): void {}
}
