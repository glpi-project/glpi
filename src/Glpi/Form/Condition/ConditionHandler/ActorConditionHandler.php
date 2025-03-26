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

namespace Glpi\Form\Condition\ConditionHandler;

use Glpi\Form\Condition\ValueOperator;
use Glpi\Form\QuestionType\AbstractQuestionTypeActors;
use Glpi\Form\QuestionType\QuestionTypeActorsExtraDataConfig;
use Override;

class ActorConditionHandler implements ConditionHandlerInterface
{
    public function __construct(
        private AbstractQuestionTypeActors $question_type,
        private QuestionTypeActorsExtraDataConfig $extra_data_config,
    ) {
    }

    #[Override]
    public function getSupportedValueOperators(): array
    {
        return [
            ValueOperator::EQUALS,
            ValueOperator::NOT_EQUALS,
            ValueOperator::CONTAINS,
            ValueOperator::NOT_CONTAINS,
        ];
    }

    #[Override]
    public function getTemplate(): string
    {
        return '/pages/admin/form/condition_handler_templates/actor.html.twig';
    }

    #[Override]
    public function getTemplateParameters(): array
    {
        return [
            'multiple'       => $this->extra_data_config->isMultipleActors(),
            'allowed_actors' => $this->question_type->getAllowedActorTypes(),
        ];
    }

    #[Override]
    public function applyValueOperator(
        mixed $a,
        ValueOperator $operator,
        mixed $b,
    ): bool {
        if (empty($a)) {
            $a = [];
        }

        if (!is_array($a) || !is_array($b)) {
            return false;
        }

        return match ($operator) {
            ValueOperator::EQUALS       => empty(array_diff($a, $b)) && count($a) === count($b),
            ValueOperator::NOT_EQUALS   => !empty(array_diff($a, $b)) || count($a) !== count($b),
            ValueOperator::CONTAINS     => !empty(array_intersect($a, $b)),
            ValueOperator::NOT_CONTAINS => empty(array_intersect($a, $b)),

            // Unsupported operators
            default => false,
        };
    }
}
