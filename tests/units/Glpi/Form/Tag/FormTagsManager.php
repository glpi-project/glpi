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

namespace tests\units\Glpi\Form\Tag;

use DbTestCase;
use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Glpi\Form\Tag\Tag;

final class FormTagsManager extends DbTestCase
{
    use FormTesterTrait;

    public function testGetTags()
    {
        $tag_manager = new \Glpi\Form\Tag\FormTagsManager();
        $form = $this->getFormWithQuestions();

        $tags = $tag_manager->getTags($form);

        $this->array($tags)->isNotEmpty();
        foreach ($tags as $tag) {
            $this->object($tag)->isInstanceOf(Tag::class);
        }
    }

    public function testInsertTagsContent()
    {
        $tag_manager = new \Glpi\Form\Tag\FormTagsManager();
        $answers_handler = AnswersHandler::getInstance();

        $form = $this->getFormWithQuestions();
        $answers = $answers_handler->saveAnswers($form, [
            $this->getQuestionId($form, "First name") => "John",
            $this->getQuestionId($form, "Last name") => "Smith",
        ], 0 /* Invalid user id but we dont care for this here */);

        $tags = $tag_manager->getTags($form);
        $first_name_question_tag = $this->getTagByName(
            $tags,
            'Question: First name'
        );
        $first_name_answer_tag = $this->getTagByName(
            $tags,
            'Answer: First name'
        );
        $last_name_question_tag = $this->getTagByName(
            $tags,
            'Question: Last name'
        );
        $last_name_answer_tag = $this->getTagByName(
            $tags,
            'Answer: Last name'
        );

        $content_with_tag =
            "$first_name_question_tag->html: $first_name_answer_tag->html, "
            . "$last_name_question_tag->html: $last_name_answer_tag->html"
        ;
        $computed_content = $tag_manager->insertTagsContent(
            $content_with_tag,
            $answers
        );

        $this->string($computed_content)->isEqualTo(
            'First name: John, Last name: Smith'
        );
    }

    public function testGetTagProviders(): void
    {
        $tag_manager = new \Glpi\Form\Tag\FormTagsManager();
        $providers = $tag_manager->getTagProviders();
        $this->array($providers)->isNotEmpty();
    }

    private function getFormWithQuestions(): Form
    {
        $builder = new FormBuilder();
        $builder->addQuestion("First name", QuestionTypeShortText::class);
        $builder->addQuestion("Last name", QuestionTypeShortText::class);
        return $this->createForm($builder);
    }
}
