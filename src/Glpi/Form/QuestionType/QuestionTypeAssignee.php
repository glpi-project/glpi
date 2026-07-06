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

namespace Glpi\Form\QuestionType;

use Exception;
use Glpi\Form\Question;
use Group;
use Override;
use Profile;
use Session;
use Supplier;
use Ticket;
use User;

final class QuestionTypeAssignee extends AbstractQuestionTypeActors
{
    #[Override]
    public function getName(): string
    {
        return _n('Assignee', 'Assignees', Session::getPluralNumber());
    }

    #[Override]
    public function getIcon(): string
    {
        return 'ti ti-user-check';
    }

    #[Override]
    public function getWeight(): int
    {
        return 30;
    }

    #[Override]
    public function getAllowedActorTypes(): array
    {
        return [User::class, Group::class, Supplier::class];
    }

    #[Override]
    public function getRightForUsers(): string
    {
        return 'own_ticket';
    }

    #[Override]
    public function getGroupConditions(): array
    {
        return ['is_assign' => 1];
    }

    #[Override]
    public function prepareEndUserAnswer(Question $question, mixed $answer): mixed
    {
        $actors = parent::prepareEndUserAnswer($question, $answer);
        foreach ($actors as $actor) {
            if ($actor['itemtype'] === User::class) {
                // The ITIL object's real entity isn't known yet here, so we
                // accept the actor if they can be assigned either in the
                // form's entity or in the current active entity.
                $candidate_entities_ids = array_unique(array_filter([
                    $question->getForm()->getEntityID(),
                    Session::isAuthenticated() ? Session::getActiveEntity() : null,
                ], static fn($entities_id) => $entities_id !== null && $entities_id >= 0));

                $can_be_assigned = false;
                foreach ($candidate_entities_ids as $entities_id) {
                    if (
                        Profile::haveUserRight(
                            $actor['items_id'],
                            Ticket::$rightname,
                            Ticket::OWN,
                            $entities_id
                        )
                    ) {
                        $can_be_assigned = true;
                        break;
                    }
                }

                if (!$can_be_assigned) {
                    throw new Exception('Invalid actor: must be able to be assigned');
                }
            } elseif ($actor['itemtype'] === Group::class) {
                // Check if the group can be assigned
                if (Group::getById($actor['items_id'])->fields['is_assign'] !== 1) {
                    throw new Exception('Invalid actor: must be able to be assigned');
                }
            }
        }

        return $actors;
    }
}
