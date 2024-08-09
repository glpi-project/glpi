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

namespace Glpi\Tests\Form\Destination;

use CommonDBTM;
use CommonGLPI;
use DbTestCase;
use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\AnswersSet;
use Glpi\Form\Destination\CommonITILField\ContentField;
use Glpi\Form\Destination\CommonITILField\SimpleValueConfig;
use Glpi\Form\Destination\CommonITILField\TitleField;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use ReflectionClass;

abstract class AbstractFormDestinationType extends DbTestCase
{
    use FormTesterTrait;

    /**
     * Get the tested instance
     *
     * @return \Glpi\Form\Destination\AbstractFormDestinationType
     */
    abstract protected function getTestedInstance(): \Glpi\Form\Destination\AbstractFormDestinationType;

    final public function testCreateDestinations(): void
    {
        $this->login();
        $answers_handler = AnswersHandler::getInstance();
        $itemtype = $this->getTestedInstance()::getTargetItemtype();
        $link_itemtype = $itemtype::getItemLinkClass();

        $title_field = new TitleField();
        $content_field = new ContentField();

        // Create a form with a single FormDestinationTicket destination
        $form = $this->createForm(
            (new FormBuilder("Test form 1"))
                ->addQuestion("Name", QuestionTypeShortText::class)
                ->addDestination(
                    $this->getTestedInstance()::class,
                    'test',
                    [
                        $title_field->getKey()             => (new SimpleValueConfig("My title"))->jsonSerialize(),
                        $title_field->getAutoConfigKey()   => 0,
                        $content_field->getKey()           => (new SimpleValueConfig("My content"))->jsonSerialize(),
                        $content_field->getAutoConfigKey() => 0,
                    ]
                )
        );

        // There are no tickets in the database named after this form
        $itil_items = (new $itemtype())->find(['name' => 'My title']);
        $this->assertCount(0, $itil_items);

        // Submit form, a single itil item should be created
        $answers = $answers_handler->saveAnswers($form, [
            $this->getQuestionId($form, "Name") => "My name",
        ], \Session::getLoginUserID());
        $itil_items = (new $itemtype())->find(['name' => 'My title']);
        $this->assertCount(1, $itil_items);

        // Check fields
        $itil_item = current($itil_items);
        $this->assertEquals('My content', $itil_item['content']);

        // Make sure link with the form answers was created too
        $links = (new $link_itemtype())->find([
            $itemtype::getForeignKeyField() => $itil_item['id'],
            'items_id' => $answers->getID(),
            'itemtype' => $answers::getType(),
        ]);
        $this->assertCount(1, $links);
    }

    final public function testGetTargetItemtype(): void
    {
        // Ensure the type defined in the child class is a valid CommonDBTM class
        $type = $this->getTestedInstance()::getTargetItemtype();
        $is_valid_class = is_a($type, CommonDBTM::class, true)
            && !(new ReflectionClass($type))->isAbstract();

        $this->assertTrue($is_valid_class);
    }

    final public function testGetFilterByAnswsersSetSearchOptionID(): void
    {
        $this->login();

        // Get search option ID
        $search_option_id = $this->getTestedInstance()::getFilterByAnswsersSetSearchOptionID();
        $this->assertGreaterThan(0, $search_option_id);

        // Compute all available search options for the target itemtype
        $created_item = new ($this->getTestedInstance()::getTargetItemtype())();
        $available_search_options = $created_item->searchOptions();

        // Ensure the search option is available for the target itemtype
        $this->assertTrue(isset($available_search_options[$search_option_id]));
    }

    public function testGetTabNameUsingFormWithoutDestination(): void
    {
        $tab_name = $this->getTestedInstance()::getTargetItemtype()::getTypeName();
        $answers = $this->getAnswersOfFormWithNoDestination();
        $this->login();
        $this->checkGetTabNameForItem($answers, $tab_name);
    }

    public function testGetTabNameUsingFormWithThreeDestination(): void
    {
        $tab_name = $this->getTestedInstance()::getTargetItemtype()::getTypeName();
        $answers = $this->getAnswersOfFormWithThreeDestination();
        $this->login();
        $_SESSION['glpishow_count_on_tabs'] = true;
        $this->checkGetTabNameForItem($answers, "$tab_name 3");
    }

    public function testGetTabNameUsingFormWithThreeDestinationWithoutCount(): void
    {
        $tab_name = $this->getTestedInstance()::getTargetItemtype()::getTypeName();
        $answers = $this->getAnswersOfFormWithThreeDestination();
        $this->login();
        $_SESSION['glpishow_count_on_tabs'] = false;
        $this->checkGetTabNameForItem($answers, $tab_name);
    }

    protected function checkGetTabNameForItem(
        CommonGLPI $item,
        string|false $expected_tab_name
    ): void {
        $destination = $this->getTestedInstance();
        $tab_name = $destination->getTabNameForItem($item);

        // Strip tags to keep only the relevant data
        $tab_name = strip_tags($tab_name);
        $this->assertEquals($expected_tab_name, $tab_name);
    }

    final public function testDisplayTabContentForItem(): void
    {
        $destination = $this->getTestedInstance();
        $answers = $this->getAnswersOfFormWithThreeDestination();

        // Render tab content
        ob_start();
        $return = $destination->displayTabContentForItem($answers);
        ob_end_clean();

        $this->assertTrue($return);
    }

    /**
     * Test the "renderConfigForm" method.
     *
     * The HTML content itself is not validated (as it should be done by an E2E
     * test instead), we just make sure the function run without errors.
     */
    final public function testRenderConfigForm(): void
    {
        $destination = $this->getTestedInstance();
        $html = $destination->renderConfigForm($this->getSimpleForm(), []);
        $this->assertNotEmpty($html);
    }

    private function getSimpleForm(): Form
    {
        $builder = new FormBuilder();
        return $this->createForm($builder);
    }

    private function getAnswersOfFormWithNoDestination(): AnswersSet
    {
        $this->login();
        $form = $this->createForm(
            (new FormBuilder())
                ->addQuestion("Name", QuestionTypeShortText::class)
        );

        $answers_handler = AnswersHandler::getInstance();
        $answers_set = $answers_handler->saveAnswers($form, [
            $this->getQuestionId($form, "Name") => "Pierre Paul Jacques",
        ], \Session::getLoginUserID());
        return $answers_set;
    }

    private function getAnswersOfFormWithThreeDestination(): AnswersSet
    {
        $this->login();
        $destination = $this->getTestedInstance();
        $form = $this->createForm(
            (new FormBuilder())
                ->addQuestion("Name", QuestionTypeShortText::class)
                ->addDestination($destination::class, 'destination 1')
                ->addDestination($destination::class, 'destination 2')
                ->addDestination($destination::class, 'destination 3')
        );

        $answers_handler = AnswersHandler::getInstance();
        $answers_set = $answers_handler->saveAnswers($form, [
            $this->getQuestionId($form, "Name") => "Pierre Paul Jacques",
        ], \Session::getLoginUserID());
        return $answers_set;
    }
}
