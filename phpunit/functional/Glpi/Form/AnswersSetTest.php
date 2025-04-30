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
use Glpi\Form\Answer;
use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\Destination\FormDestinationProblem;
use Glpi\Form\Form;
use Glpi\Form\Question;
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Ticket;

class AnswersSetTest extends DbTestCase
{
    use FormTesterTrait;

    /**
     * Test the "getAnswers" method
     *
     * @return void
     */
    public function testGetAnswer(): void
    {
        $this->login();
        $answers_handler = AnswersHandler::getInstance();

        // Create form and insert an anwser
        $form = $this->createForm(
            (new FormBuilder())
                ->addQuestion("Name", QuestionTypeShortText::class)
        );
        $answers_set = $answers_handler->saveAnswers($form, [
            $this->getQuestionId($form, "Name") => "Pierre Paul Jacques",
        ], \Session::getLoginUserID());

        $answer = new Answer(
            Question::getById($this->getQuestionId($form, "Name")),
            "Pierre Paul Jacques"
        );
        $expected_answer = [$answer];
        $this->assertEquals($expected_answer, $answers_set->getAnswers());
    }

    /**
     * Test the "getCreatedItems" method
     *
     * @return void
     */
    public function testGetCreatedItems(): void
    {
        $this->login();
        $answers_handler = AnswersHandler::getInstance();

        // Create a form with two destinations
        $form = $this->createForm(
            (new FormBuilder())
                ->addQuestion("Name", QuestionTypeShortText::class)
                ->addDestination(FormDestinationTicket::class, 'Ticket 1')
                ->addDestination(FormDestinationTicket::class, 'Ticket 2')
        );

        // Submit form
        $answers_set = $answers_handler->saveAnswers($form, [
            $this->getQuestionId($form, "Name") => "Pierre Paul Jacques",
        ], \Session::getLoginUserID());

        // Validate that we have the two expected items
        $items = $answers_set->getCreatedItems();
        $this->assertInstanceOf(Ticket::class, $items[0]);
        $this->assertInstanceOf(Ticket::class, $items[1]);

        // No need to test the content of the tickets here as it is already
        // handled in the "FormDestinationTicket" tests class.
    }

    public function testGetLinksToCreatedItemssForAdmin()
    {
        $this->login();
        $form = $this->createAndGetFormWithTwoProblemDestination();

        $answers_handler = AnswersHandler::getInstance();
        $answers = $answers_handler->saveAnswers($form, [], \Session::getLoginUserID());

        // 3 because +1 mandatory
        $this->assertCount(3, $answers->getLinksToCreatedItems());
    }

    public function testGetLinksToCreatedItemssForEndUser()
    {
        $this->login("post-only", "postonly");
        $form = $this->createAndGetFormWithTwoProblemDestination();

        $answers_handler = AnswersHandler::getInstance();
        $answers = $answers_handler->saveAnswers($form, [], \Session::getLoginUserID());

        // User can't see problems, still there is one fallback link to the answers
        $this->assertCount(1, $answers->getLinksToCreatedItems());
    }

    private function createAndGetFormWithTwoAnswers(): Form
    {
        $form = $this->createForm(
            (new FormBuilder())
                ->addQuestion("Name", QuestionTypeShortText::class)
        );

        $answers_handler = AnswersHandler::getInstance();
        $answers_handler->saveAnswers($form, [
            $this->getQuestionId($form, "Name") => "Pierre Paul Jacques",
        ], \Session::getLoginUserID());
        $answers_handler->saveAnswers($form, [
            $this->getQuestionId($form, "Name") => "Paul Pierre Jacques",
        ], \Session::getLoginUserID());

        return $form;
    }

    private function createAndGetFormWithTwoProblemDestination(): Form
    {
        $form = $this->createForm(
            (new FormBuilder())
                ->addQuestion("Name", QuestionTypeShortText::class)
                ->addDestination(FormDestinationProblem::class, "My first problem")
                ->addDestination(FormDestinationProblem::class, "My second problem")
        );

        return $form;
    }
}
