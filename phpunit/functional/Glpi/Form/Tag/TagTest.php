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

namespace tests\units\Glpi\Form\Tag;

use DbTestCase;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Form\Tag\FormTagsManager;
use Glpi\Form\Tag\QuestionTagProvider;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;

final class TagTest extends DbTestCase
{
    use FormTesterTrait;

    public function testTagCanBeExportedToJson(): void
    {
        $form = $this->getFormWithQuestions();
        $tag_manager = new FormTagsManager();
        $tags = $tag_manager->getTags($form);

        $tag = $this->getTagByName($tags, 'Question: First name');
        $question_id = $this->getQuestionId($form, 'First name');
        $provider = QuestionTagProvider::class;
        $color = (new $provider())->getTagColor();

        $expected = json_encode([
            'label' => 'Question: First name',
            'html' => "<span contenteditable=\"false\" data-form-tag=\"true\" data-form-tag-value=\"$question_id\" data-form-tag-provider=\"$provider\" class=\"border-$color border-start border-3 bg-dark-lt\">#Question: First name</span>",
        ]);
        $this->assertEquals($expected, json_encode($tag));
    }

    private function getFormWithQuestions(): Form
    {
        $builder = new FormBuilder();
        $builder->addQuestion("First name", QuestionTypeShortText::class);
        $builder->addQuestion("Last name", QuestionTypeShortText::class);
        return $this->createForm($builder);
    }
}
