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

use Glpi\Form\AnswersSet;
use Glpi\Form\Comment;
use Glpi\Form\Form;
use Override;

final class CommentDescriptionTagProvider implements TagProviderInterface, TagWithIdValueInterface
{
    #[Override]
    public function getTagColor(): string
    {
        return "green";
    }

    #[Override]
    public function getTags(Form $form): array
    {
        $tags = [];
        foreach ($form->getFormComments() as $comment) {
            $tags[] = $this->getDescriptionTagForComment($comment);
        }

        return $tags;
    }

    #[Override]
    public function getTagContentForValue(
        string $value,
        AnswersSet $answers_set
    ): string {
        $id = (int) $value;

        $comment = Comment::getById($id);
        if ($comment === false) {
            return '';
        }
        return $comment->fields['description'];
    }

    #[Override]
    public function getItemtype(): string
    {
        return Comment::class;
    }

    public function getDescriptionTagForComment(Comment $comment): Tag
    {
        return new Tag(
            label: sprintf(__('Comment description: %s'), $comment->fields['description']),
            value: $comment->getId(),
            provider: $this,
        );
    }
}
