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

namespace Glpi\Form\Tag;

use Glpi\Form\Answer;
use Glpi\Form\AnswersSet;
use Glpi\Form\Form;
use Glpi\Form\Question;
use Override;

final class AnswerTagProvider implements TagProviderInterface
{
    #[Override]
    public function getTagColor(): string
    {
        return "teal";
    }

    #[Override]
    public function getTags(Form $form): array
    {
        $tags = [];
        foreach ($form->getQuestions() as $question) {
            $tags[] = $this->getTagForQuestion($question);
        }

        return $tags;
    }

    #[Override]
    public function getTagContentForValue(
        string $value,
        AnswersSet $answers_set
    ): string {
        $id = (int) $value;

        $answers = array_filter(
            $answers_set->getAnswers(),
            fn(Answer $answer) => $answer->getQuestionId() === $id
        );

        if (count($answers) !== 1) {
            return "";
        }

        $answer = array_pop($answers);
        return $answer->getFormattedAnswer();
    }

    public function getTagForQuestion(Question $question): Tag
    {
        return new Tag(
            label: sprintf(__('Answer: %s'), $question->fields['name']),
            value: $question->getId(),
            provider: $this,
        );
    }
}
