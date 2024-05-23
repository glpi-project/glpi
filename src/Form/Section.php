<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace Glpi\Form;

use CommonDBChild;
use Override;

/**
 * Section of a given helpdesk form
 */
final class Section extends CommonDBChild
{
    public static $itemtype = Form::class;
    public static $items_id = 'forms_forms_id';

    /**
     * Lazy loaded array of questions
     * Should always be accessed through getQuestions()
     * @var Question[]|null
     */
    protected ?array $questions = null;

    /**
     * Lazy loaded array of comments
     * Should always be accessed through getComments()
     * @var Comment[]|null
     */
    protected ?array $comments = null;

    #[Override]
    public static function getTypeName($nb = 0)
    {
        return _n('Step', 'Steps', $nb);
    }

    #[Override]
    public function post_getFromDB()
    {
        // Clear any lazy loaded data
        $this->clearLazyLoadedData();
    }

    #[Override]
    public function cleanDBonPurge()
    {
        $this->deleteChildrenAndRelationsFromDb(
            [
                Question::class,
                Comment::class,
            ]
        );
    }

    /**
     * Get blocks of this section
     * Block can be a question or a comment
     *
     * @return Block[]
     */
    public function getBlocks(): array
    {
        $blocks = array_merge($this->getQuestions(), $this->getComments());
        usort($blocks, fn($a, $b) => $a->fields['rank'] <=> $b->fields['rank']);

        return $blocks;
    }

    /**
     * Get questions of this section
     *
     * @return Question[]
     */
    public function getQuestions(): array
    {
        // Lazy loading
        if ($this->questions === null) {
            $this->questions = [];

            // Read from database
            $questions_data = (new Question())->find(
                [self::getForeignKeyField() => $this->fields['id']],
                'rank ASC',
            );
            foreach ($questions_data as $row) {
                $question = new Question();
                $question->getFromResultSet($row);
                $question->post_getFromDB();

                if ($question->getQuestionType() === null) {
                    // The question might belong to a disabled plugin
                    continue;
                }

                $this->questions[$row['id']] = $question;
            }
        }

        return $this->questions;
    }

    /**
     * Get comments of this section
     *
     * @return Comment[]
     */
    public function getComments(): array
    {
        // Lazy loading
        if ($this->comments === null) {
            $this->comments = [];

            // Read from database
            $comments_data = (new Comment())->find(
                [self::getForeignKeyField() => $this->fields['id']],
                'rank ASC',
            );
            foreach ($comments_data as $row) {
                $comment = new Comment();
                $comment->getFromResultSet($row);
                $comment->post_getFromDB();

                $this->comments[$row['id']] = $comment;
            }
        }

        return $this->comments;
    }

    /**
     * Clear lazy loaded data
     *
     * @return void
     */
    protected function clearLazyLoadedData(): void
    {
        $this->questions = null;
        $this->comments = null;
    }
}
