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
use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\QuestionType\QuestionTypeDateTime;
use Glpi\Form\QuestionType\QuestionTypeEmail;
use Glpi\Form\QuestionType\QuestionTypeLongText;
use Glpi\Form\QuestionType\QuestionTypeNumber;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Form\QuestionType\QuestionTypesManager;
use Glpi\Form\QuestionType\QuestionTypeTime;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Impact;
use Ticket;

class AnswersSet extends DbTestCase
{
    use FormTesterTrait;

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
        yield [$form_1, "Form answers"];

        // Form with answers
        $form_2 = $this->createForm(
            (new FormBuilder())
                ->addQuestion("Name", QuestionTypeShortText::class)
        );

        $answers_handler->saveAnswers($form_2, [
            $this->getQuestionId($form_2, "Name") => "Pierre Paul Jacques"
        ], \Session::getLoginUserID());

        $answers_handler->saveAnswers($form_2, [
            $this->getQuestionId($form_2, "Name") => "Paul Pierre Jacques"
        ], \Session::getLoginUserID());

        $_SESSION['glpishow_count_on_tabs'] = true;
        yield [$form_2, "Form answers 2"];

        $_SESSION['glpishow_count_on_tabs'] = false;
        yield [$form_2, "Form answers"];
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
                ->addQuestion("Name", QuestionTypeShortText::class)
        );

        $answers_handler->saveAnswers($form_2, [
            $this->getQuestionId($form_2, "Name") => "Pierre Paul Jacques"
        ], \Session::getLoginUserID());

        $answers_handler->saveAnswers($form_2, [
            $this->getQuestionId($form_2, "Name") => "Paul Pierre Jacques"
        ], \Session::getLoginUserID());

        $answers_handler->saveAnswers($form_2, [
            $this->getQuestionId($form_2, "Name") => "Jacques Paul Pierre"
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
                ->addQuestion("Name", QuestionTypeShortText::class)
        );
        $answers_set = $answers_handler->saveAnswers($form, [
            $this->getQuestionId($form, "Name") => "Pierre Paul Jacques"
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
                ->addQuestion("Name", QuestionTypeShortText::class)
                ->addQuestion("Age", QuestionTypeNumber::class)
                ->addSection("Second section")
                ->addQuestion("Email", QuestionTypeEmail::class)
                ->addQuestion("Address", QuestionTypeLongText::class)
                ->addSection("Third section")
                ->addQuestion("Date", QuestionTypeDateTime::class)
                ->addQuestion("Time", QuestionTypeDateTime::class, '', json_encode(['is_time_enabled' => 1]))
                ->addQuestion("DateTime", QuestionTypeDateTime::class, '', json_encode([
                    'is_date_enabled' => 1,
                    'is_time_enabled' => 1
                ]))
        );
        $answers_set = $answers_handler->saveAnswers($form, [
            $this->getQuestionId($form, "Name") => "Pierre Paul Jacques",
            $this->getQuestionId($form, "Age") => 20,
            $this->getQuestionId($form, "Email") => "pierre@paul.jacques",
            $this->getQuestionId($form, "Address") => "France",
            $this->getQuestionId($form, "Date") => "2021-01-01",
            $this->getQuestionId($form, "Time") => "12:00",
            $this->getQuestionId($form, "DateTime") => "2021-01-01 12:00:00",
        ], \Session::getLoginUserID());

        // Ensure we used every possible questions types
        // Questions types can have multiple questions in the form
        // so we need to check the count of unique types
        $possible_types = $types_manager->getQuestionTypes();
        $current_questions_types = array_reduce(
            $form->getQuestions(),
            function ($carry, $question) {
                $type = $question->getQuestionType();
                if (!in_array($type, $carry)) {
                    $carry[] = $type;
                }

                return $carry;
            },
            []
        );
        $this->array($current_questions_types)
            ->hasSize(count($possible_types));

        $this->array($answers_set->fields["answers"])
            ->hasSize(count($form->getQuestions()));

        // Render content
        ob_start();
        $this
            ->boolean($answers_set->showForm($answers_set->getID()))
            ->isTrue()
        ;
        ob_end_clean();
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
                ->addQuestion("Name", QuestionTypeShortAnswerText::class)
                ->addDestination(FormDestinationTicket::class, ['name' => 'Ticket 1'])
                ->addDestination(FormDestinationTicket::class, ['name' => 'Ticket 2'])
        );

        // Submit form
        $answers_set = $answers_handler->saveAnswers($form, [
            $this->getQuestionId($form, "Name") => "Pierre Paul Jacques",
        ], \Session::getLoginUserID());

        // Validate that we have the two expected items
        $items = $answers_set->getCreatedItems();
        $this
            ->array($items)
            ->hasSize(2);
        $this
            ->object($items[0])
            ->isInstanceOf(Ticket::class);
        $this
            ->object($items[1])
            ->isInstanceOf(Ticket::class);

        // No need to test the content of the tickets here as it is already
        // handled in the "FormDestinationTicket" tests class.
    }
}
