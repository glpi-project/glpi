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

use Glpi\Form\Question;
use Group;
use Override;
use Session;
use User;

final class QuestionTypeObserver extends AbstractQuestionTypeActors
{
    #[Override]
    public function getName(): string
    {
        return _n('Observer', 'Observers', Session::getPluralNumber());
    }

    #[Override]
    public function getIcon(): string
    {
        return 'ti ti-user-search';
    }

    #[Override]
    public function getWeight(): int
    {
        return 20;
    }

    #[Override]
    public function getAllowedActorTypes(): array
    {
        return [User::class, Group::class];
    }

    #[Override]
    public function getGroupConditions(): array
    {
        return ['is_watcher' => 1];
    }

    #[Override]
    public function prepareEndUserAnswer(Question $question, mixed $answer): mixed
    {
        $actors = parent::prepareEndUserAnswer($question, $answer);
        foreach ($actors as $actor) {
            if ($actor['itemtype'] === Group::class) {
                // Check if the group can be assigned
                if (Group::getById($actor['items_id'])->fields['is_watcher'] !== 1) {
                    throw new \Exception('Invalid actor: must be an observer');
                }
            }
        }

        return $actors;
    }
}
