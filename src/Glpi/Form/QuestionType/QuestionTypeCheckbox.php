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

use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\Condition\ConditionHandler\MultipleChoiceFromValuesConditionHandler;
use Glpi\Form\Condition\UsedAsCriteriaInterface;
use Glpi\Form\Question;
use InvalidArgumentException;
use Override;

final class QuestionTypeCheckbox extends AbstractQuestionTypeSelectable implements UsedAsCriteriaInterface
{
    #[Override]
    public function getInputType(?Question $question): string
    {
        return 'checkbox';
    }

    #[Override]
    public function getCategory(): QuestionTypeCategoryInterface
    {
        return QuestionTypeCategory::CHECKBOX;
    }

    #[Override]
    public function getConditionHandlers(
        ?JsonFieldInterface $question_config
    ): array {
        if (!$question_config instanceof QuestionTypeSelectableExtraDataConfig) {
            throw new InvalidArgumentException();
        }

        return array_merge(
            parent::getConditionHandlers($question_config),
            [new MultipleChoiceFromValuesConditionHandler($question_config->getOptions())]
        );
    }
}
