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

namespace test\units\Glpi\Helpdesk;

use CommonITILActor;
use Computer;
use DbTestCase;
use Entity;
use Glpi\Form\AccessControl\FormAccessControlManager;
use Glpi\Form\AccessControl\FormAccessParameters;
use Glpi\Form\Form;
use Glpi\Helpdesk\DefaultDataManager;
use Glpi\Helpdesk\Tile\Item_Tile;
use Glpi\Helpdesk\Tile\TileInterface;
use Glpi\Session\SessionInfo;
use Glpi\Tests\FormTesterTrait;
use Glpi\UI\IllustrationManager;
use ITILCategory;
use Location;
use Monitor;
use Session;
use Ticket;
use User;

final class DefaultDataManagerTest extends DbTestCase
{
    use FormTesterTrait;

    private function getManager(): DefaultDataManager
    {
        return new DefaultDataManager();
    }

    public function testsFormAreAddedAfterInstallation(): void
    {
        $this->assertEquals(2, countElementsInTable(Form::getTable()));
    }

    public function testNoFormAreCreatedWhenDatabaseIsNotEmpty(): void
    {
        // Arrange: count the number of forms that already exist in the database
        $number_of_forms_before = countElementsInTable(Form::getTable());

        // Act: initialize default data
        $this->getManager()->initializeDataIfNeeded();

        // Assert: there must be not be any new forms
        $number_of_forms_after = countElementsInTable(Form::getTable());
        $number_of_new_forms = $number_of_forms_after - $number_of_forms_before;
        $this->assertEquals(0, $number_of_new_forms);
    }

    public function testIncidentFormProperties(): void
    {
        // Arrange: Get default incident form
        $rows = (new Form())->find(['name' => 'Report an issue']);

        // Assert: there should be one single form with specific properties
        $this->assertCount(1, $rows);
        $row = current($rows);
        $this->assertEquals(0, $row['entities_id']);
        $this->assertEquals(true, $row['is_recursive']);
        $this->assertEquals(true, $row['is_active']);
        $this->assertEquals(false, $row['is_deleted']);
        $this->assertEquals(false, $row['is_draft']);
        $this->assertEmpty($row['header']);
        $this->assertNotEmpty($row['illustration']);
        $this->assertNotEmpty($row['description']);
        $this->assertCount(1, Form::getById($row['id'])->getDestinations());
    }

    public function testRequestFormProperties(): void
    {
        // Arrange: Get default incident form
        $rows = (new Form())->find(['name' => 'Request a service']);

        // Assert: there should be one single form with specific properties
        $this->assertCount(1, $rows);
        $row = current($rows);
        $this->assertEquals(0, $row['entities_id']);
        $this->assertEquals(true, $row['is_recursive']);
        $this->assertEquals(true, $row['is_active']);
        $this->assertEquals(false, $row['is_deleted']);
        $this->assertEquals(false, $row['is_draft']);
        $this->assertEmpty($row['header']);
        $this->assertNotEmpty($row['illustration']);
        $this->assertNotEmpty($row['description']);
        $this->assertCount(1, Form::getById($row['id'])->getDestinations());
    }

    public function testIncidentFormQuestions(): void
    {
        $this->login();

        // Arrange: Get default incident form and fetch test data
        $rows = (new Form())->find(['name' => 'Report an issue']);
        $row = current($rows);
        $form = Form::getById($row['id']);

        // Create test categories, locations and user devices
        $category = $this->createItem(ITILCategory::class, [
            'name' => 'Test category',
            'is_incident' => true,
        ]);
        $location = $this->createItem(Location::class, [
            'name' => 'Test location',
        ]);
        $computer = $this->createItem(Computer::class, [
            'name' => 'Test User Computer',
            'users_id' => Session::getLoginUserID(),
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $monitors = $this->createItems(Monitor::class, [
            [
                'name' => 'Test Monitor 1',
                'users_id' => Session::getLoginUserID(),
                'entities_id' => $this->getTestRootEntity(true),
            ],
            [
                'name' => 'Test Monitor 2',
                'users_id' => Session::getLoginUserID(),
                'entities_id' => $this->getTestRootEntity(true),
            ],
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
            'Observers' => [
                User::getForeignKeyField() . '-' . $tech_user_id,
                User::getForeignKeyField() . '-' . $normal_user_id,
            ],
            'User devices' => [
                Computer::class . '_' . $computer->getID(),
                Monitor::class . '_' . $monitors[0]->getID(),
                Monitor::class . '_' . $monitors[1]->getID(),
            ],
        ]);

        // Assert: check the created ticket properties
        $this->assertEquals(Ticket::INCIDENT_TYPE, $ticket->fields['type']);
        $this->assertEquals('My ticket title', $ticket->fields['name']);
        $this->assertEquals('My ticket content', $ticket->fields['content']);
        $this->assertEquals($category->getID(), $ticket->fields['itilcategories_id']);
        $this->assertEquals($location->getID(), $ticket->fields['locations_id']);
        $this->assertEquals(
            [$tech_user_id, $normal_user_id],
            array_column($ticket->getActorsForType(CommonITILActor::OBSERVER), 'items_id')
        );
        $this->assertEquals(
            [User::class, User::class],
            array_column($ticket->getActorsForType(CommonITILActor::OBSERVER), 'itemtype')
        );
        $this->assertEquals(
            [Computer::class, Monitor::class, Form::class],
            array_keys($ticket->getLinkedItems())
        );
        $this->assertEquals(
            [$monitors[0]->getID(), $monitors[1]->getID()],
            array_values($ticket->getLinkedItems()[Monitor::class])
        );
        $this->assertEquals(
            [$computer->getID()],
            array_values($ticket->getLinkedItems()[Computer::class])
        );
        $actors = $ticket->getActorsForType(CommonITILActor::REQUESTER);
        $actor = current($actors);
        $this->assertEquals(TU_USER, $actor['title']);
    }

    public function testRequestFormQuestions(): void
    {
        $this->login();

        // Arrange: Get default request form and fetch test data
        $rows = (new Form())->find(['name' => 'Request a service']);
        $row = current($rows);
        $form = Form::getById($row['id']);

        // Create test categories, locations and user devices
        $category = $this->createItem(ITILCategory::class, [
            'name' => 'Test category',
            'is_incident' => true,
        ]);
        $location = $this->createItem(Location::class, [
            'name' => 'Test location',
        ]);
        $computer = $this->createItem(Computer::class, [
            'name' => 'Test User Computer',
            'users_id' => Session::getLoginUserID(),
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $monitors = $this->createItems(Monitor::class, [
            [
                'name' => 'Test Monitor 1',
                'users_id' => Session::getLoginUserID(),
                'entities_id' => $this->getTestRootEntity(true),
            ],
            [
                'name' => 'Test Monitor 2',
                'users_id' => Session::getLoginUserID(),
                'entities_id' => $this->getTestRootEntity(true),
            ],
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
            'Observers' => [
                User::getForeignKeyField() . '-' . $tech_user_id,
                User::getForeignKeyField() . '-' . $normal_user_id,
            ],
            'User devices' => [
                Computer::class . '_' . $computer->getID(),
                Monitor::class . '_' . $monitors[0]->getID(),
                Monitor::class . '_' . $monitors[1]->getID(),
            ],
        ]);

        // Assert: check the created ticket properties
        $this->assertEquals(Ticket::DEMAND_TYPE, $ticket->fields['type']);
        $this->assertEquals('My ticket title', $ticket->fields['name']);
        $this->assertEquals('My ticket content', $ticket->fields['content']);
        $this->assertEquals($category->getID(), $ticket->fields['itilcategories_id']);
        $this->assertEquals($location->getID(), $ticket->fields['locations_id']);
        $this->assertEquals(5, $ticket->fields['urgency']);
        $this->assertEquals(
            [$tech_user_id, $normal_user_id],
            array_column($ticket->getActorsForType(CommonITILActor::OBSERVER), 'items_id')
        );
        $this->assertEquals(
            [User::class, User::class],
            array_column($ticket->getActorsForType(CommonITILActor::OBSERVER), 'itemtype')
        );
        $this->assertEquals(
            [Computer::class, Monitor::class, Form::class],
            array_keys($ticket->getLinkedItems())
        );
        $this->assertEquals(
            [$monitors[0]->getID(), $monitors[1]->getID()],
            array_values($ticket->getLinkedItems()[Monitor::class])
        );
        $this->assertEquals(
            [$computer->getID()],
            array_values($ticket->getLinkedItems()[Computer::class])
        );
        $actors = $ticket->getActorsForType(CommonITILActor::REQUESTER);
        $actor = current($actors);
        $this->assertEquals(TU_USER, $actor['title']);
    }

    public function testIncidentFormShouldBeAccessibleBySelfServiceUsers(): void
    {
        // Arrange: Get default incident form
        $rows = (new Form())->find(['name' => 'Report an issue']);
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
        $rows = (new Form())->find(['name' => 'Request a service']);
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

    public function testsTilesAreAddedAfterInstallation(): void
    {
        $this->assertEquals(6, countElementsInTable(Item_Tile::getTable()));

        // Default tiles must be attached to the root entity
        $profile_tiles = (new Item_Tile())->find([]);
        foreach ($profile_tiles as $row) {
            $this->assertEquals(Entity::class, $row['itemtype_item']);
            $this->assertEquals(0, $row['items_id_item']);
        }
    }

    public function testNoTilesAreCreatedWhenDatabaseIsNotEmpty(): void
    {
        // Arrange: count the number of tiles that already exist in the database
        $number_of_tiles_before = countElementsInTable(Item_Tile::getTable());

        // Act: initialize default data
        $this->getManager()->initializeDataIfNeeded();

        // Assert: there must be not be any new tiles
        $number_of_tiles_after = countElementsInTable(Item_Tile::getTable());
        $number_of_new_tiles = $number_of_tiles_after - $number_of_tiles_before;
        $this->assertEquals(0, $number_of_new_tiles);
    }

    public function testDefaultTilesAreValid(): void
    {
        // Arrange: load valid illustration names
        $illustration_manager = new IllustrationManager();
        $valid_icons = $illustration_manager->getAllIconsIds();

        // Act: load the default tiles
        $profile_tiles = (new Item_Tile())->find([]);
        $tiles = array_map(function ($row) {
            $itemtype = $row['itemtype_tile'];
            $tile = new $itemtype();
            $tile->getFromDb($row['items_id_tile']);
            return $tile;
        }, $profile_tiles);

        // Assert: there should be at least one tile and each tile should have a
        // valid title, description, illustration and link
        $this->assertNotEmpty($tiles);
        foreach ($tiles as $tile) {
            $this->assertInstanceOf(TileInterface::class, $tile);
            $this->assertNotEmpty($tile->getTitle());
            $this->assertNotEmpty($tile->getDescription());
            $this->assertContains($tile->getIllustration(), $valid_icons);
            $this->assertNotEmpty($tile->getTileUrl());
        }
    }
}
