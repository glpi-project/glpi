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
use DbTestCase;
use Glpi\Form\Answer;
use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\AnswersSet;
use Glpi\Form\Destination\FormDestinationProblem;
use Glpi\Form\Form;
use Glpi\Form\Question;
use Glpi\Form\QuestionType\QuestionTypeAssignee;
use Glpi\Form\QuestionType\QuestionTypeObserver;
use Glpi\Form\QuestionType\QuestionTypeRequester;
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\QuestionType\QuestionTypeCheckbox;
use Glpi\Form\QuestionType\QuestionTypeDateTime;
use Glpi\Form\QuestionType\QuestionTypeDropdown;
use Glpi\Form\QuestionType\QuestionTypeEmail;
use Glpi\Form\QuestionType\QuestionTypeFile;
use Glpi\Form\QuestionType\QuestionTypeItem;
use Glpi\Form\QuestionType\QuestionTypeItemDropdown;
use Glpi\Form\QuestionType\QuestionTypeLongText;
use Glpi\Form\QuestionType\QuestionTypeNumber;
use Glpi\Form\QuestionType\QuestionTypeRequestType;
use Glpi\Form\QuestionType\QuestionTypeRadio;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Form\QuestionType\QuestionTypesManager;
use Glpi\Form\QuestionType\QuestionTypeUrgency;
use Glpi\Form\QuestionType\QuestionTypeUserDevice;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Group;
use Impact;
use PHPUnit\Framework\Attributes\DataProvider;
use Supplier;
use Ticket;
use User;

class AnswersSetTest extends DbTestCase
{
    use FormTesterTrait;

    public function testGetTabNameForFormWithoutQuestion(): void
    {
        $this->login();

        $_SESSION['glpishow_count_on_tabs'] = true;
        $form = $this->createForm(new FormBuilder());

        $this->checkGetTabNameForItem($form, "Form answers");
    }

    public function testGetTabNameForFormWithQuestionsWithCountEnabled(): void
    {
        $this->login();

        $_SESSION['glpishow_count_on_tabs'] = true;
        $form = $this->createAndGetFormWithTwoAnswers();

        $this->checkGetTabNameForItem($form, "Form answers 2");
    }

    public function testGetTabNameForFormWithQuestionsWithCountDisabled(): void
    {
        $this->login();

        $_SESSION['glpishow_count_on_tabs'] = false;
        $form = $this->createAndGetFormWithTwoAnswers();

        $this->checkGetTabNameForItem($form, "Form answers");
    }

    private function checkGetTabNameForItem(
        CommonGLPI $item,
        string|false $expected_tab_name,
    ): void {
        $answers_set = new AnswersSet();

        $tab_name = $answers_set->getTabNameForItem($item);

        // Strip tags to keep only the relevant data
        $tab_name = strip_tags($tab_name);

        $this->assertEquals($expected_tab_name, $tab_name);
    }

    public function testDisplayTabContentForItem(): void
    {
        $this->login();
        $answers_set = new AnswersSet();
        $form = $this->createAndGetFormWithTwoAnswers();

        ob_start();
        $return = $answers_set->displayTabContentForItem($form);
        ob_end_clean();

        $this->assertTrue($return);
    }

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
            $this->getQuestionId($form, "Name") => "Pierre Paul Jacques"
        ], \Session::getLoginUserID());

        $answer = new Answer(
            Question::getById($this->getQuestionId($form, "Name")),
            "Pierre Paul Jacques"
        );
        $expected_answer = [$answer];
        $this->assertEquals($expected_answer, $answers_set->getAnswers());
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
                ->addQuestion("Requester", QuestionTypeRequester::class)
                ->addQuestion("Observer", QuestionTypeObserver::class)
                ->addQuestion("Assignee", QuestionTypeAssignee::class)
                ->addQuestion("Urgency", QuestionTypeUrgency::class)
                ->addQuestion("Request type", QuestionTypeRequestType::class)
                ->addQuestion("Radio", QuestionTypeRadio::class, '123', json_encode([
                    'options' => [
                        123 => 'Radio 1'
                    ]
                ]))
                ->addQuestion("Checkbox", QuestionTypeCheckbox::class, '123', json_encode([
                    'options' => [
                        123 => 'Checkbox 1'
                    ]
                ]))
                ->addQuestion("File", QuestionTypeFile::class)
                ->addQuestion("Dropdown", QuestionTypeDropdown::class, '123', json_encode([
                    'options' => [
                        123 => 'Dropdown 1'
                    ]
                ]))
                ->addQuestion("GLPI Objects", QuestionTypeItem::class, 0, json_encode(['itemtype' => 'User']))
                ->addQuestion("User Devices", QuestionTypeUserDevice::class)
                ->addQuestion("Dropdowns", QuestionTypeItemDropdown::class, 0, json_encode(['itemtype' => 'Location']))
        );

        // File question type requires an uploaded file
        $unique_id = uniqid();
        $filename = $unique_id . '-test-show-form-question-type-file.txt';
        copy(FIXTURE_DIR . '/uploads/bar.txt', GLPI_TMP_DIR . '/' . $filename);
        $question = Question::getById($this->getQuestionId($form, "File"));
        $_POST['_prefix_' . $question->getEndUserInputName()] = $unique_id;

        $answers_set = $answers_handler->saveAnswers($form, [
            $this->getQuestionId($form, "Name") => "Pierre Paul Jacques",
            $this->getQuestionId($form, "Age") => 20,
            $this->getQuestionId($form, "Email") => "pierre@paul.jacques",
            $this->getQuestionId($form, "Address") => "France",
            $this->getQuestionId($form, "Date") => "2021-01-01",
            $this->getQuestionId($form, "Time") => "12:00",
            $this->getQuestionId($form, "DateTime") => "2021-01-01 12:00:00",
            $this->getQuestionId($form, "Requester") => [
                User::getForeignKeyField() . '-1',
                Group::getForeignKeyField() . '-1'
            ],
            $this->getQuestionId($form, "Observer") => [
                User::getForeignKeyField() . '-1',
                Group::getForeignKeyField() . '-1'
            ],
            $this->getQuestionId($form, "Assignee") => [
                User::getForeignKeyField() . '-1',
                Group::getForeignKeyField() . '-1',
                Supplier::getForeignKeyField() . '-1'
            ],
            $this->getQuestionId($form, "Urgency") => 2,
            $this->getQuestionId($form, "Request type") => 1,
            $this->getQuestionId($form, "File") => [$filename],
            $this->getQuestionId($form, "Radio") => 'Radio 1',
            $this->getQuestionId($form, "Checkbox") => 'Checkbox 1',
            $this->getQuestionId($form, "Dropdown") => 'Dropdown 1',
            $this->getQuestionId($form, "GLPI Objects") => [
                'itemtype' => 'User',
                'items_id' => 0
            ],
            $this->getQuestionId($form, "User Devices") => 'Computer_0',
            $this->getQuestionId($form, "Dropdowns") => [
                'itemtype' => 'Location',
                'items_id' => 0
            ],
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
        $this->assertCount(count($possible_types), $current_questions_types);
        $this->assertCount(count($form->getQuestions()), $answers_set->getAnswers());

        // Render content
        ob_start();
        $this->assertTrue($answers_set->showForm($answers_set->getID()));
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

        $this->assertCount(2, $answers->getLinksToCreatedItems());
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
            $this->getQuestionId($form, "Name") => "Pierre Paul Jacques"
        ], \Session::getLoginUserID());
        $answers_handler->saveAnswers($form, [
            $this->getQuestionId($form, "Name") => "Paul Pierre Jacques"
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
