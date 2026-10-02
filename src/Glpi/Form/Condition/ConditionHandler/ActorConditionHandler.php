<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

use Glpi\Form\Condition\ConditionData;
use Glpi\Form\Condition\ValueOperator;
use Glpi\Form\QuestionType\AbstractQuestionTypeActors;
use Glpi\Form\QuestionType\QuestionTypeActorsDefaultValueConfig;
use Glpi\Form\QuestionType\QuestionTypeActorsExtraDataConfig;
use Group;
use Override;
use Supplier;
use User;

use function Safe\json_decode;

class ActorConditionHandler implements ConditionHandlerInterface
{
    use ArrayConditionHandlerTrait;

    public function __construct(
        private AbstractQuestionTypeActors $question_type,
        private QuestionTypeActorsExtraDataConfig $extra_data_config,
    ) {}

    #[Override]
    public function getSupportedValueOperators(): array
    {
        return $this->getSupportedArrayValueOperators();
    }

    #[Override]
    public function getTemplate(): string
    {
        return '/pages/admin/form/condition_handler_templates/actor.html.twig';
    }

    #[Override]
    public function getTemplateParameters(ConditionData $condition): array
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
        // During form rendering, applyValueOperator is called to compute items
        // visibility using the question default value, which is stored as JSON.
        if (is_string($a) && json_validate($a)) {
            $decoded = json_decode($a, true);
            $a = [];
            foreach (
                [
                    User::class     => QuestionTypeActorsDefaultValueConfig::KEY_USERS_IDS,
                    Group::class    => QuestionTypeActorsDefaultValueConfig::KEY_GROUPS_IDS,
                    Supplier::class => QuestionTypeActorsDefaultValueConfig::KEY_SUPPLIERS_IDS,
                ] as $itemtype => $key
            ) {
                $ids = is_array($decoded) ? ($decoded[$key] ?? []) : [];
                foreach (is_array($ids) ? $ids : [] as $id) {
                    $a[] = getForeignKeyFieldForItemType($itemtype) . '-' . $id;
                }
            }
        }

        return $this->applyArrayValueOperator($a, $operator, $b);
    }
}
