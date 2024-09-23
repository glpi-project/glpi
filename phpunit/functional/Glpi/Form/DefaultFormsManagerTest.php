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

namespace test\units\Glpi\Form;

use DbTestCase;
use Glpi\Form\AccessControl\FormAccessControlManager;
use Glpi\Form\AccessControl\FormAccessParameters;
use Glpi\Form\DefaultFormsManager;
use Glpi\Form\Form;
use Glpi\Session\SessionInfo;
use Glpi\Tests\FormTesterTrait;
use ITILCategory;
use Location;
use Ticket;
use User;

final class DefaultFormsManagerTest extends DbTestCase
{
    use FormTesterTrait;

    private function getManager(): DefaultFormsManager
    {
        return new DefaultFormsManager();
    }

    public function testsFormAreAddedAfterInstallation(): void
    {
        $this->assertEquals(2, countElementsInTable(Form::getTable()));
    }

    public function testNoFormAreCreatedWhenDatabaseIsNotEmpty(): void
    {
        // Arrange: count the number of forms that already exist in the database
        $number_of_forms_before = countElementsInTable(Form::getTable());

        // Act: create default forms
        $this->getManager()->createDefaultForms();

        // Assert: there must be not be any new forms
        $number_of_forms_after = countElementsInTable(Form::getTable());
        $number_of_new_forms = $number_of_forms_after - $number_of_forms_before;
        $this->assertEquals(0, $number_of_new_forms);
    }

    public function testIncidentFormProperties(): void
    {
        // Arrange: Get default incident form
        $rows = (new Form())->find(['name' => 'Incident']);

        // Assert: there should be one single form with specific properties
        $this->assertCount(1, $rows);
        $row = current($rows);
        $this->assertEquals(0, $row['entities_id']);
        $this->assertEquals(true, $row['is_recursive']);
        $this->assertEquals(true, $row['is_active']);
        $this->assertEquals(false, $row['is_deleted']);
        $this->assertEquals(false, $row['is_draft']);
        $this->assertEmpty($row['header']);
        $this->assertNotEmpty($row['icon']);
        $this->assertNotEmpty($row['description']);
    }

    public function testRequestFormProperties(): void
    {
        // Arrange: Get default incident form
        $rows = (new Form())->find(['name' => 'Request']);

        // Assert: there should be one single form with specific properties
        $this->assertCount(1, $rows);
        $row = current($rows);
        $this->assertEquals(0, $row['entities_id']);
        $this->assertEquals(true, $row['is_recursive']);
        $this->assertEquals(true, $row['is_active']);
        $this->assertEquals(false, $row['is_deleted']);
        $this->assertEquals(false, $row['is_draft']);
        $this->assertEmpty($row['header']);
        $this->assertNotEmpty($row['icon']);
        $this->assertNotEmpty($row['description']);
    }

    public function testIncidentFormQuestions(): void
    {
        // Arrange: Get default incident form and fetch test data
        $rows = (new Form())->find(['name' => 'Incident']);
        $row = current($rows);
        $form = Form::getById($row['id']);

        // Create test categories and locations
        $category = $this->createItem(ITILCategory::class, [
            'name' => 'Test category',
            'is_incident' => true,
        ]);
        $location = $this->createItem(Location::class, [
            'name' => 'Test location',
        ]);

        // Fetch test users
        $tech_user_id = getItemByTypeName(User::class, "tech", true);
        $normal_user_id = getItemByTypeName(User::class, "normal", true);

        // Act: submit the default incident form
        $ticket = $this->sendFormAndGetCreatedTicket($form, [
            'Title' => 'My ticket title',
            'Description' => 'My ticket content',
            'Category' => [
                'itemtype' => ITILCategory::class,
                'items_id' => $category->getID(),
            ],
            'Location' => [
                'itemtype' => Location::class,
                'items_id' => $location->getID(),
            ],
            'Urgency' => 5, // Very high
            'Watchers' => [
                ['itemtype' => User::class, 'items_id' => $tech_user_id],
                ['itemtype' => User::class, 'items_id' => $normal_user_id],
            ]
        ]);

        // Assert: check the created ticket properties
        $this->assertEquals(Ticket::INCIDENT_TYPE, $ticket->fields['type']);
        $this->assertEquals('My ticket title', $ticket->fields['name']);
        $this->assertEquals('My ticket content', $ticket->fields['content']);
        $this->assertEquals($category->getID(), $ticket->fields['itilcategories_id']);
        $this->assertEquals($location->getID(), $ticket->fields['locations_id']);
        $this->assertEquals(5, $ticket->fields['urgency']);
        // TODO: check observers, not possible yet as there are not configurable.
    }

    public function testRequestFormQuestions(): void
    {
        // Arrange: Get default request form and fetch test data
        $rows = (new Form())->find(['name' => 'Request']);
        $row = current($rows);
        $form = Form::getById($row['id']);

        // Create test categories and locations
        $category = $this->createItem(ITILCategory::class, [
            'name' => 'Test category',
            'is_incident' => true,
        ]);
        $location = $this->createItem(Location::class, [
            'name' => 'Test location',
        ]);

        // Fetch test users
        $tech_user_id = getItemByTypeName(User::class, "tech", true);
        $normal_user_id = getItemByTypeName(User::class, "normal", true);

        // Act: submit the default incident form
        $ticket = $this->sendFormAndGetCreatedTicket($form, [
            'Title' => 'My ticket title',
            'Description' => 'My ticket content',
            'Category' => [
                'itemtype' => ITILCategory::class,
                'items_id' => $category->getID(),
            ],
            'Location' => [
                'itemtype' => Location::class,
                'items_id' => $location->getID(),
            ],
            'Urgency' => 5, // Very high
            'Watchers' => [
                ['itemtype' => User::class, 'items_id' => $tech_user_id],
                ['itemtype' => User::class, 'items_id' => $normal_user_id],
            ]
        ]);

        // Assert: check the created ticket properties
        $this->assertEquals(Ticket::DEMAND_TYPE, $ticket->fields['type']);
        $this->assertEquals('My ticket title', $ticket->fields['name']);
        $this->assertEquals('My ticket content', $ticket->fields['content']);
        $this->assertEquals($category->getID(), $ticket->fields['itilcategories_id']);
        $this->assertEquals($location->getID(), $ticket->fields['locations_id']);
        $this->assertEquals(5, $ticket->fields['urgency']);
        // TODO: check observers, not possible yet as there are not configurable.
    }

    public function testIncidentFormShouldBeAccessibleBySelfServiceUsers(): void
    {
        // Arrange: Get default incident form
        $rows = (new Form())->find(['name' => 'Incident']);
        $row = current($rows);
        $form = Form::getById($row['id']);

        // Act: check if the form can be answered by a self service user
        $form_access_manager = FormAccessControlManager::getInstance();
        $parameters = new FormAccessParameters(
            session_info: new SessionInfo(
                user_id: getItemByTypeName(User::class, "post-only", true)
            )
        );
        $can_answer = $form_access_manager->canAnswerForm($form, $parameters);

        // Assert: the user should be able to see the form
        $this->assertEquals(true, $can_answer);
    }

    public function testRequestFormShouldBeAccessibleBySelfServiceUsers(): void
    {
        // Arrange: Get default request form
        $rows = (new Form())->find(['name' => 'Request']);
        $row = current($rows);
        $form = Form::getById($row['id']);

        // Act: check if the form can be answered by a self service user
        $form_access_manager = FormAccessControlManager::getInstance();
        $parameters = new FormAccessParameters(
            session_info: new SessionInfo(
                user_id: getItemByTypeName(User::class, "post-only", true)
            )
        );
        $can_answer = $form_access_manager->canAnswerForm($form, $parameters);

        // Assert: the user should be able to see the form
        $this->assertEquals(true, $can_answer);
    }
}
