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

namespace tests\units\Glpi\Form;

use Glpi\Form\Comment;
use Glpi\Form\Condition\LogicOperator;
use Glpi\Form\Condition\Type;
use Glpi\Form\Condition\ValueOperator;
use Glpi\Form\Condition\VisibilityStrategy;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;

final class CommentTest extends DbTestCase
{
    use FormTesterTrait;

    public function testConditionsDataAreCleanedWhenStrategyIsReset(): void
    {
        // Arrange: create a form with visibility conditions on a comment
        $builder = new FormBuilder();
        $builder->addQuestion("My question", QuestionTypeShortText::class);
        $builder->addComment("My comment");
        $builder->setCommentVisibility("My comment", VisibilityStrategy::VISIBLE_IF, [
            [
                'logic_operator' => LogicOperator::AND,
                'item_name'      => "My question",
                'item_type'      => Type::QUESTION,
                'value_operator' => ValueOperator::EQUALS,
                'value'          => "Yes",
            ],
        ]);
        $form = $this->createForm($builder);

        // Act: reset the comment's visibility strategy
        $comment_id = $this->getCommentId($form, "My comment");
        $comment = $this->updateItem(Comment::class, $comment_id, [
            'visibility_strategy' => VisibilityStrategy::ALWAYS_VISIBLE->value,
        ]);

        // Assert: the conditions should be deleted
        $this->assertEmpty($comment->getConfiguredConditionsData());
    }
}
