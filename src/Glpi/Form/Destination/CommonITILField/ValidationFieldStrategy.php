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

namespace Glpi\Form\Destination\CommonITILField;

use Glpi\Form\AnswersSet;
use ITILValidationTemplate;
use ITILValidationTemplate_Target;

enum ValidationFieldStrategy: string
{
    case NO_VALIDATION    = 'no_validation';
    case SPECIFIC_VALUES  = 'specific_values';
    case SPECIFIC_ACTORS  = 'specific_actors';
    case SPECIFIC_ANSWERS = 'specific_answers';

    public function getLabel(): string
    {
        return match ($this) {
            self::NO_VALIDATION    => __("No approval"),
            self::SPECIFIC_VALUES  => __("Specific Approval templates"),
            self::SPECIFIC_ACTORS  => __("Specific actors"),
            self::SPECIFIC_ANSWERS => __("Answer from specific questions"),
        };
    }

    public function getValidation(
        ValidationFieldStrategyConfig $config,
        AnswersSet $answers_set,
    ): ?array {
        return match ($this) {
            self::NO_VALIDATION    => null,
            self::SPECIFIC_VALUES  => $this->getActorsForSpecificValues(
                $config->getSpecificValidationTemplateIds()
            ),
            self::SPECIFIC_ACTORS  => $this->getActorsFromSpecificActors(
                $config->getSpecificActors()
            ),
            self::SPECIFIC_ANSWERS => $this->getActorsForSpecificAnswers(
                $config->getSpecificQuestionIds(),
                $answers_set
            ),
        };
    }

    private function isValidAnswer($value): bool
    {
        if (is_array($value) && is_array(current($value))) {
            foreach ($value as $item) {
                if (!$this->isValidAnswer($item)) {
                    return false;
                }
            }

            return true;
        }

        return isset($value['itemtype']) && is_string($value['itemtype'])
            && isset($value['items_id']) && is_numeric($value['items_id']);
    }

    private function getActorsForSpecificAnswers(
        array $question_ids,
        AnswersSet $answers_set,
    ): ?array {
        if ($question_ids === []) {
            return null;
        }

        $actors = [];
        foreach ($question_ids as $question_id) {
            $actors_to_add = $this->getActorsForSpecificAnswer($question_id, $answers_set);

            if (is_array($actors_to_add) && is_array(current($actors_to_add))) {
                $actors = array_merge($actors, $actors_to_add);
            } elseif ($actors_to_add !== null) {
                $actors[] = $actors_to_add;
            }
        }

        return $actors;
    }

    /**
     * Get actors for specific validation templates
     *
     * @param ?array<int> $specific_values Validation template IDs
     */
    private function getActorsForSpecificValues(
        ?array $specific_values,
    ): ?array {
        if (empty($specific_values)) {
            return null;
        }

        $actors = [];
        foreach ($specific_values as $validation_template_id) {
            $validation_template = ITILValidationTemplate::getById($validation_template_id);
            if (!$validation_template) {
                continue;
            }

            $targets = ITILValidationTemplate_Target::getTargets($validation_template_id);
            if (empty($targets)) {
                continue;
            }

            foreach ($targets as $target) {
                $actors[] = [
                    'itemtype' => $target['itemtype'],
                    'items_id' => $target['items_id'],
                ];
            }
        }

        return $actors;
    }

    private function getActorsForSpecificAnswer(
        ?int $question_id,
        AnswersSet $answers_set,
    ): ?array {
        if ($question_id === null) {
            return null;
        }

        $answer = $answers_set->getAnswerByQuestionId($question_id);
        if ($answer === null) {
            return null;
        }

        $value = $answer->getRawAnswer();
        if (!$this->isValidAnswer($value)) {
            return null;
        }

        return $value;
    }

    private function getActorsFromSpecificActors(
        array $specific_actors,
    ): ?array {
        if ($specific_actors === []) {
            return null;
        }

        $actors = [];
        foreach ($specific_actors as $itemtype => $actor_ids) {
            foreach ($actor_ids as $actor_id) {
                $actors[] = [
                    'itemtype' => $itemtype,
                    'items_id' => $actor_id,
                ];
            }
        }

        return $actors;
    }
}
