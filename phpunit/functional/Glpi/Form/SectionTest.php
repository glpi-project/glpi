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

use DbTestCase;
use Glpi\Form\Question;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Form\Section;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;

class SectionTest extends DbTestCase
{
    use FormTesterTrait;

    /**
     * Indirectly test the post_getFromDB method by editing and reloading an item
     *
     * @return void
     */
    public function testPost_getFromDB(): void
    {
        $this->login();

        // First test: make sure lazy loaded data is cleared when a section is reladed
        $form = $this->createForm(
            (new FormBuilder())
                ->addSection('Section 1')
                ->addQuestion('Question 1', QuestionTypeShortText::class)
                ->addQuestion('Question 2', QuestionTypeShortText::class)
                ->addQuestion('Question 3', QuestionTypeShortText::class)
        );
        $section = Section::getById($this->getSectionId($form, 'Section 1'));
        $this->assertCount(3, $section->getQuestions());

        // Delete all questions
        foreach ($section->getQuestions() as $question) {
            $this->deleteItem(Question::class, $question->getID());
        }

        // Until the section is reloaded, its internal questions data
        // shouldn't change
        $this->assertCount(3, $section->getQuestions());

        // Relaod form
        $section->getFromDB($section->getID());
        $this->assertCount(0, $section->getQuestions());
    }

    public function testGetQuestionsForSectionWithoutQuestions(): void
    {
        $form = static::createForm(
            (new FormBuilder())
                ->addSection('Section 1')
        );
        $section = Section::getById(static::getSectionId($form, 'Section 1'));
        $this->checkGetQuestions($section, []);
    }

    public function testGetQuestionsForSectionWithQuestions(): void
    {
        $form = static::createForm(
            (new FormBuilder())
                ->addSection('Section 1')
                ->addQuestion('Question 1', QuestionTypeShortText::class)
                ->addQuestion('Question 2', QuestionTypeShortText::class)
                ->addQuestion('Question 3', QuestionTypeShortText::class)
        );
        $section = Section::getById(static::getSectionId($form, 'Section 1'));
        $this->checkGetQuestions($section, ['Question 1', 'Question 2', 'Question 3']);
    }

    public function testGetQuestionsForSectionWithInvalidQuestions(): void
    {
        $form = static::createForm(
            (new FormBuilder())
                ->addSection('Section 1')
                ->addQuestion('Valid question type', QuestionTypeShortText::class)
                ->addQuestion('Invalid question type', "Not a type")
        );
        $section = Section::getById(static::getSectionId($form, 'Section 1'));
        $this->checkGetQuestions($section, ['Valid question type']);
    }

    private function checkGetQuestions(
        Section $section,
        array $expected_questions_names
    ): void {
        $questions = $section->getQuestions();
        $names = array_map(fn($question) => $question->getName(), $questions);
        $names = array_values($names); // Strip keys
        $this->assertEquals($expected_questions_names, $names);
    }
}
