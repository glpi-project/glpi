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
use Glpi\Form\EndUserInputNameProvider;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;

class EndUserInputNameProviderTest extends DbTestCase
{
    use FormTesterTrait;

    public function testGetEndUserInputName()
    {
        // Create a new form
        $form = $this->createForm(
            (new FormBuilder())
                ->addQuestion('Name', QuestionTypeShortText::class)
        );

        // Check that the end user input name contains the question ID
        // and if the regex match with the generated end user input name
        foreach ($form->getQuestions() as $question) {
            $this->assertStringContainsString(
                $question->getID(),
                $question->getEndUserInputName()
            );
            $this->assertMatchesRegularExpression(
                EndUserInputNameProvider::END_USER_INPUT_NAME_REGEX,
                $question->getEndUserInputName()
            );
        }
    }

    public function testGetAnswers()
    {
        // Create a new form
        $form = $this->createForm(
            (new FormBuilder())
                ->addQuestion('Name', QuestionTypeShortText::class)
                ->addQuestion('Email', QuestionTypeShortText::class)
        );

        // Generate the answers
        $inputs = [
            $form->getQuestions()[array_keys($form->getQuestions())[0]]->getEndUserInputName() => 'John Doe',
            $form->getQuestions()[array_keys($form->getQuestions())[1]]->getEndUserInputName() => 'john.doe@mail.mail',
            'invalid_input' => 'invalid_value',
        ];

        // Check that the answers are correctly indexed by question ID
        $answers = (new EndUserInputNameProvider())->getAnswers($inputs);
        $this->assertEquals([
            $form->getQuestions()[array_keys($form->getQuestions())[0]]->getID() => 'John Doe',
            $form->getQuestions()[array_keys($form->getQuestions())[1]]->getID() => 'john.doe@mail.mail',
        ], $answers);
    }
}
