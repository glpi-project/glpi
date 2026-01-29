<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

use DateInterval;
use DateMalformedStringException;
use Glpi\Form\AnswersSet;
use Safe\DateTime;

enum SLMFieldStrategy: string
{
    case FROM_TEMPLATE                           = 'from_template';
    case SPECIFIC_VALUE                          = 'specific_value';
    case SPECIFIC_DATE_ANSWER                    = 'specific_date_answer';
    case COMPUTED_DATE_FROM_FORM_SUBMISSION      = 'computed_date_from_form_submission';
    case COMPUTED_DATE_FROM_SPECIFIC_DATE_ANSWER = 'computed_date_from_specific_date_answer';

    public function getLabel(SLMField $field): string
    {
        return match ($this) {
            self::FROM_TEMPLATE                           => __("From template"),
            self::SPECIFIC_VALUE                          => sprintf(__("Specific %s"), $field->getSLM()->getTypeName(1)),
            self::SPECIFIC_DATE_ANSWER                    => __("Answer from a specific date question"),
            self::COMPUTED_DATE_FROM_FORM_SUBMISSION      => __("Computed date based on form submission date"),
            self::COMPUTED_DATE_FROM_SPECIFIC_DATE_ANSWER => __("Computed date based on a specific date question answer"),
        };
    }

    public function getSLMID(
        SLMFieldConfig $config,
    ): ?int {
        return match ($this) {
            self::FROM_TEMPLATE => null, // Let the template apply its default value by itself.
            self::SPECIFIC_VALUE => $config->getSpecificSLMID(),

            // The following strategies do not use a specific SLM ID.
            self::SPECIFIC_DATE_ANSWER,
            self::COMPUTED_DATE_FROM_FORM_SUBMISSION,
            self::COMPUTED_DATE_FROM_SPECIFIC_DATE_ANSWER => null,
        };
    }

    public function getDateSLM(
        SLMFieldConfig $config,
        AnswersSet $answers_set,
    ): ?string {
        return match ($this) {
            self::FROM_TEMPLATE,
            self::SPECIFIC_VALUE => null, // Let the template apply its default value by itself.

            self::SPECIFIC_DATE_ANSWER => $this->getDateTimeFromSpecificAnswer(
                $config->getQuestionId(),
                $answers_set
            ),
            self::COMPUTED_DATE_FROM_FORM_SUBMISSION => $this->computeDateFromOffset(
                $answers_set->fields['date_creation'] ?? null,
                $config->getTimeOffset(),
                $config->getTimeDefinition(),
            ),
            self::COMPUTED_DATE_FROM_SPECIFIC_DATE_ANSWER => $this->computeDateFromOffset(
                $this->getDateTimeFromSpecificAnswer(
                    $config->getQuestionId(),
                    $answers_set
                ),
                $config->getTimeOffset(),
                $config->getTimeDefinition(),
            ),
        };
    }

    private function getDateTimeFromSpecificAnswer(
        ?int $question_id,
        AnswersSet $answers_set,
    ): ?string {
        if ($question_id === null) {
            return null;
        }

        $answer = $answers_set->getAnswerByQuestionId($question_id);
        if ($answer === null) {
            return null;
        }

        // Validate that the answer is a string representing a date
        $value = $answer->getRawAnswer();
        if (!is_string($value)) {
            return null;
        }

        try {
            new DateTime($value);
        } catch (DateMalformedStringException) {
            return null;
        }

        return $value;
    }

    private function computeDateFromOffset(
        ?string $raw_date,
        ?int $time_offset,
        ?string $time_definition,
    ): ?string {
        if ($raw_date === null || $time_offset === null || $time_definition === null) {
            return null;
        }

        try {
            $date = new DateTime($raw_date);
        } catch (DateMalformedStringException) {
            return null;
        }

        $interval_spec = match ($time_definition) {
            'minute' => 'PT' . abs((int) $time_offset) . 'M', // Minutes
            'hour'   => 'PT' . abs((int) $time_offset) . 'H', // Hours
            'day'    => 'P' . abs((int) $time_offset) . 'D', // Days
            default  => 'P' . abs((int) $time_offset) . 'M', // Months
        };

        $interval = new DateInterval($interval_spec);
        if ((int) $time_offset < 0) {
            $date->sub($interval);
        } else {
            $date->add($interval);
        }

        return $date->format('Y-m-d H:i:s');
    }
}
