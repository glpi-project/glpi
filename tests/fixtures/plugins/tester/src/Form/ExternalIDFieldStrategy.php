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

namespace GlpiPlugin\Tester\Form;

use Glpi\Form\AnswersSet;
use GlpiPlugin\Tester\Form\ExternalIDFieldConfig;

enum ExternalIDFieldStrategy: string
{
    case NO_EXTERNAL_ID  = 'no_external_id';
    case SPECIFIC_VALUE  = 'specific_value';
    case SPECIFIC_ANSWER = 'specific_answer';

    public function getLabel(): string
    {
        return match ($this) {
            self::NO_EXTERNAL_ID  => __("No external ID"),
            self::SPECIFIC_VALUE   => __("Specific external ID"),
            self::SPECIFIC_ANSWER  => __("From a specific question"),
        };
    }

    public function getExternalID(
        ExternalIDFieldConfig $config,
        AnswersSet $answers_set,
    ): ?string {
        return match ($this) {
            self::NO_EXTERNAL_ID => null,
            self::SPECIFIC_VALUE   => $config->getSpecificExternalID(),
            self::SPECIFIC_ANSWER  => $this->getExternalIDForSpecificAnswer(
                $config->getSpecificQuestionID(),
                $answers_set
            ),
        };
    }

    private function getExternalIDForSpecificAnswer(
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

        return $answer->getRawAnswer();
    }
}
