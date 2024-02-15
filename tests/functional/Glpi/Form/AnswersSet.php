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

namespace tests\units\Glpi\Form;

use CommonGLPI;
use Computer;
use DbTestCase;
use FormBuilder;
use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\QuestionType\QuestionTypeLongAnswer;
use Glpi\Form\QuestionType\QuestionTypeShortAnswerEmail;
use Glpi\Form\QuestionType\QuestionTypeShortAnswerNumber;
use Glpi\Form\QuestionType\QuestionTypeShortAnswerText;
use Glpi\Form\QuestionType\QuestionTypesManager;
use Impact;
use Ticket;

class AnswersSet extends DbTestCase
{
    /**
     * Data provider for the "testGetTabNameForItem" method
     *
     * @return iterable
     */
    protected function testGetTabNameForItemProvider(): iterable
    {
        $this->login();
        $answers_handler = AnswersHandler::getInstance();

        // Invalid types
        yield [new Computer(), false];
        yield [new Ticket(), false];
        yield [new Impact(), false];

        // Form without questions
        $form_1 = $this->createForm(new FormBuilder());
        yield [$form_1, "Answers"];

        // Form with answers
        $form_2 = $this->createForm(
            (new FormBuilder())
                ->addQuestion("Name", QuestionTypeShortAnswerText::class)
        );

        $answers_handler->saveAnswers($form_2, [
            $this->getQuestionsId($form_2, "Name") => "Pierre Paul Jacques"
        ], \Session::getLoginUserID());

        $answers_handler->saveAnswers($form_2, [
            $this->getQuestionsId($form_2, "Name") => "Paul Pierre Jacques"
        ], \Session::getLoginUserID());

        $_SESSION['glpishow_count_on_tabs'] = true;
        yield [$form_2, "Answers 2"];

        $_SESSION['glpishow_count_on_tabs'] = false;
        yield [$form_2, "Answers"];
    }

    /**
     * Test the "getTabNameForItem" method
     *
     * @dataProvider testGetTabNameForItemProvider
     *
     * @param CommonGLPI $item
     * @param string|false $expected_tab_name
     *
     * @return void
     */
    public function testGetTabNameForItem(
        CommonGLPI $item,
        string|false $expected_tab_name
    ): void {
        $answers_set = new \Glpi\Form\AnswersSet();

        $tab_name = $answers_set->getTabNameForItem($item);

        if ($tab_name !== false) {
            // Strip tags to keep only the relevant data
            $tab_name = strip_tags($tab_name);
        }

        $this->variable($tab_name)->isEqualTo($expected_tab_name);
    }

    /**
     * Data provider for the "testDisplayTabContentForItem" method
     *
     * @return iterable
     */
    protected function testDisplayTabContentForItemProvider(): iterable
    {
        $this->login();
        $answers_handler = AnswersHandler::getInstance();

        // Invalid types
        yield [new Computer(), false];
        yield [new Ticket(), false];
        yield [new Impact(), false];

        // Form without questions
        $form_1 = $this->createForm(new FormBuilder());
        yield [$form_1, true];

        // Form using all possible questions types
        $form_2 = $this->createForm(
            (new FormBuilder())
                ->addQuestion("Name", QuestionTypeShortAnswerText::class)
        );

        $answers_handler->saveAnswers($form_2, [
            $this->getQuestionsId($form_2, "Name") => "Pierre Paul Jacques"
        ], \Session::getLoginUserID());

        $answers_handler->saveAnswers($form_2, [
            $this->getQuestionsId($form_2, "Name") => "Paul Pierre Jacques"
        ], \Session::getLoginUserID());

        $answers_handler->saveAnswers($form_2, [
            $this->getQuestionsId($form_2, "Name") => "Jacques Paul Pierre"
        ], \Session::getLoginUserID());

        yield [$form_2, true];
    }

    /**
     * Tests for the "displayTabContentForItem" method
     *
     * Note: the tab content itself is not verified here as it would be too
     * complex.
     * It should be verified using a separate E2E test instead.
     * Any error while rendering the tab will still be caught by this tests so
     * it isn't completely useless.
     *
     * @dataProvider testdisplayTabContentForItemProvider
     *
     * @param CommonGLPI $item
     * @param bool       $expected_return
     *
     * @return void
     */
    public function testDisplayTabContentForItem(
        CommonGLPI $item,
        bool $expected_return
    ): void {
        $answers_set = new \Glpi\Form\AnswersSet();

        ob_start();
        $return = $answers_set->displayTabContentForItem($item);
        ob_end_clean();

        $this->variable($return)->isEqualTo($expected_return);
    }

    /**
     * Test the "post_getFromDB" method
     *
     * @return void
     */
    public function testPost_getFromDB(): void
    {
        $this->login();
        $answers_handler = AnswersHandler::getInstance();

        // Create form and insert an anwser
        $form = $this->createForm(
            (new FormBuilder())
                ->addQuestion("Name", QuestionTypeShortAnswerText::class)
        );
        $answers_set = $answers_handler->saveAnswers($form, [
            $this->getQuestionsId($form, "Name") => "Pierre Paul Jacques"
        ], \Session::getLoginUserID());

        // Ensure JSON is decoded
        $this->array($answers_set->fields["answers"]);
    }

    /**
     * Test the "showForm" method
     *
     * Note: the HTML content itself is not verified here as it would be too
     * complex.
     * It should be verified using a separate E2E test instead.
     * Any error while rendering the tab will still be caught by this tests so
     * we must try to send the most complex answers set possible.
     *
     * @return void
     */
    public function testShowForm(): void
    {
        $this->login();
        $answers_handler = AnswersHandler::getInstance();
        $types_manager = QuestionTypesManager::getInstance();

        // Create a form with each possible types of questions and multiple sections
        $form = $this->createForm(
            (new FormBuilder())
                ->addQuestion("Name", QuestionTypeShortAnswerText::class)
                ->addQuestion("Age", QuestionTypeShortAnswerNumber::class)
                ->addSection("Second section")
                ->addQuestion("Email", QuestionTypeShortAnswerEmail::class)
                ->addQuestion("Address", QuestionTypeLongAnswer::class)
                ->addSection("Third section")
        );
        $answers_set = $answers_handler->saveAnswers($form, [
            $this->getQuestionsId($form, "Name") => "Pierre Paul Jacques",
            $this->getQuestionsId($form, "Age") => 20,
            $this->getQuestionsId($form, "Email") => "pierre@paul.jacques",
            $this->getQuestionsId($form, "Address") => "France",
        ], \Session::getLoginUserID());

        // Ensure we used every possible questions types
        $possible_types = $types_manager->getQuestionTypes();
        $this
            ->array($form->getQuestions())
            ->hasSize(count($possible_types))
        ;
        $this
            ->array($answers_set->fields["answers"])
            ->hasSize(count($possible_types))
        ;

        // Render content
        ob_start();
        $this
            ->boolean($answers_set->showForm($answers_set->getID()))
            ->isTrue()
        ;
        ob_end_clean();
    }
}
