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

namespace tests\units\Glpi\Asset\Capacity;

use Change;
use Change_Item;
use CommonITILObject;
use DbTestCase;
use DisplayPreference;
use Glpi\Asset\Capacity\HasHistoryCapacity;
use Item_Problem;
use Item_Ticket;
use Log;
use Problem;
use Profile;
use Search;
use Ticket;

class HasCommonITILObjectCapacity extends DbTestCase
{
    /**
     * Get the tested capacity class.
     *
     * @return string
     */
    protected function getTargetCapacity(): string
    {
        return \Glpi\Asset\Capacity\HasCommonITILObjectCapacity::class;
    }

    /**
     * Test that the capacity is properly registered in the configuration
     * when enabled and unregistered when disabled.
     *
     * @return void
     */
    public function testConfigRegistration(): void
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $this->login();

        // Create custom asset definition
        $definition = $this->initAssetDefinition();
        $class = $definition->getConcreteClassName();

        // The capacity is not yet enabled, the itemtype should not be
        // registered in $CFG_GLPI["ticket_types"]
        $this->array($CFG_GLPI["ticket_types"])->notContains($class);

        // Double check using CommonITILObject::getAllTypesForHelpdesk()
        $helpdesk_types = array_keys(CommonITILObject::getAllTypesForHelpdesk());
        $this->array($helpdesk_types)->notContains($class);

        // Enable capacity, the itemtype should now be registered
        $definition = $this->enableCapacity(
            $definition,
            $this->getTargetCapacity()
        );
        $this->array($CFG_GLPI["ticket_types"])->contains($class);

        // Allow the asset in "helpdesk_item_type" of the current profiles
        $profile = getItemByTypeName(Profile::class, "Super-Admin");
        $itemtypes = importArrayFromDB($profile->fields["helpdesk_item_type"]);
        $itemtypes[] = $class;
        $this->updateItem(Profile::class, $profile->getID(), [
            'helpdesk_item_type' => exportArrayToDB($itemtypes),
        ]);

        // Double check using CommonITILObject::getAllTypesForHelpdesk()
        $helpdesk_types = array_keys(CommonITILObject::getAllTypesForHelpdesk());
        $this->array($helpdesk_types)->contains($class);

        // Disable capacity, the itemtype should no longer be registered
        $definition = $this->disableCapacity(
            $definition,
            $this->getTargetCapacity()
        );
        $this->array($CFG_GLPI["ticket_types"])->notContains($class);
    }

    /**
     * Test that the "Tickets", "Problems" and "Changes" tabs are registered
     * when the capacity is enabled.
     *
     * @return void
     */
    public function testCommonITILTabRegistration(): void
    {
        // Some tabs won't be displayed if the user can't see the related type
        $this->login();

        // Create custom asset definition
        $definition = $this->initAssetDefinition();
        $class = $definition->getConcreteClassName();

        // Create our test subject
        $subject = $this->createItem($class, [
            'name' => 'Test asset',
        ]);

        // Validate that the subject does not have the target tabs yet, as the
        // capacity is not enabled
        $tab_names = [
            'Ticket$1',
            'Item_Problem$1',
            'Change_Item$1',
        ];
        $tabs = $subject->defineAllTabs();
        foreach ($tab_names as $tab_name) {
            $this->array($tabs)->notHasKey($tab_name);
        }

        // Enable capacity, the tabs should now be registered
        $definition = $this->enableCapacity(
            $definition,
            $this->getTargetCapacity()
        );
        $tabs = $subject->defineAllTabs();
        foreach ($tab_names as $tab_name) {
            $this->array($tabs)->hasKey($tab_name);
        }
    }

    /**
     * Test that this asset type is correctly removed from the
     * "helpdesk_item_type" field of profiles when the capacity is disabled.
     *
     * @return void
     */
    public function testProfileConfigDeletion(): void
    {
        // Create custom asset definition with the target capacity enabled
        $definition = $this->initAssetDefinition(
            capacities: [$this->getTargetCapacity()]
        );
        $class = $definition->getConcreteClassName();

        // Allow this asset on the super admin profile
        $profile = getItemByTypeName(Profile::class, "Super-Admin");
        $itemtypes = importArrayFromDB($profile->fields["helpdesk_item_type"]);
        $itemtypes[] = $class;
        $this->updateItem(Profile::class, $profile->getID(), [
            'helpdesk_item_type' => exportArrayToDB($itemtypes),
        ]);

        // Check that the item is enabled for the profile
        $profile->getFromDB($profile->getID());
        $itemtypes = importArrayFromDB($profile->fields["helpdesk_item_type"]);
        $this->array($itemtypes)->contains($class);

        // Disable capacity
        $definition = $this->disableCapacity(
            $definition,
            $this->getTargetCapacity()
        );

        // Recheck profile
        $profile->getFromDB($profile->getID());
        $itemtypes = importArrayFromDB($profile->fields["helpdesk_item_type"]);
        $this->array($itemtypes)->notContains($class);
    }

    /**
     * ITIL types data provider
     */
    protected function getITILClassProvider(): iterable
    {
        yield [Ticket::class];
        yield [Problem::class];
        yield [Change::class];
    }

    /**
     * Test that any relation between the asset and ITIL objects are
     * not deleted when the capacity is disabled.
     *
     * @dataProvider getITILClassProvider
     *
     * @return void
     */
    public function testITILRelationDataDeletion(string $itil): void
    {
        $item_class = $itil::getItemLinkClass();
        $fk = getForeignKeyFieldForItemType($itil);

        // Create custom asset definition with the target capacity enabled
        $definition = $this->initAssetDefinition(
            capacities: [$this->getTargetCapacity()]
        );
        $class = $definition->getConcreteClassName();

        // Create our test subject
        $subject = $this->createItem($class, [
            'name' => 'Test asset',
        ]);
        // Create a ticket
        $ticket = $this->createItem($itil, [
            'name'    => 'Test ticket',
            'content' => 'Test ticket',
        ]);
        // Link subject to ticket
        $this->createItem($item_class, [
            'itemtype' => $subject::getType(),
            'items_id' => $subject->getID(),
            $fk        => $ticket->getID(),
        ]);

        // Ensure items are properly linked
        $items = (new $item_class())->find([
            'itemtype' => $subject::getType(),
            'items_id' => $subject->getID(),
            $fk        => $ticket->getID(),
        ]);
        $this->array($items)->hasSize(1);

        // Disable capacity, linked ticket should NOT be deleted
        $definition = $this->disableCapacity(
            $definition,
            $this->getTargetCapacity()
        );
        $items = (new $item_class())->find([
            'itemtype' => $subject::getType(),
            'items_id' => $subject->getID(),
            $fk        => $ticket->getID(),
        ]);
        $this->array($items)->hasSize(1);
    }

    /**
     * Test that any history entries related to ITIL items are deleted when the
     * capacity is disabled.
     *
     * @return void
     */
    public function testHistoryDataDeletion(): void
    {
        // Create custom asset definition with the target capacity enabled
        $definition = $this->initAssetDefinition(
            capacities: [
                $this->getTargetCapacity(),
                HasHistoryCapacity::class
            ]
        );
        $class = $definition->getConcreteClassName();

        // Create our test subject
        $subject = $this->createItem($class, [
            'name' => 'Test asset',
        ]);

        // Creat itil items to be linked to the subject
        $ticket = $this->createItem(Ticket::class, [
            'name'    => 'Test ticket',
            'content' => 'Test ticket',
        ]);
        $problem = $this->createItem(Problem::class, [
            'name'    => 'Test problem',
            'content' => 'Test problem',
        ]);
        $change = $this->createItem(Change::class, [
            'name'    => 'Test change',
            'content' => 'Test change',
        ]);

        // Create and update the linked OS in order to generate history entries
        $this->createItem(Item_Ticket::class, [
            'itemtype'   => $subject::getType(),
            'items_id'   => $subject->getID(),
            'tickets_id' => $ticket->getID(),
        ]);
        $this->createItem(Item_Problem::class, [
            'itemtype'    => $subject::getType(),
            'items_id'    => $subject->getID(),
            'problems_id' => $problem->getID(),
        ]);
        $this->createItem(Change_Item::class, [
            'itemtype'   => $subject::getType(),
            'items_id'   => $subject->getID(),
            'changes_id' => $change->getID(),
        ]);

        // Check logs number:
        // - 1 log for $subject creation
        // - 1 log for link with $ticket
        // - 1 log for link with $problem
        // - 1 log for link with $change
        $count_logs = countElementsInTable(Log::getTable(), [
            'itemtype' => $class,
        ]);
        $this->integer($count_logs)->isEqualTo(4);

        // Disable capacity, history entries should be cleaned
        $definition = $this->disableCapacity(
            $definition,
            $this->getTargetCapacity()
        );
        $count_logs = countElementsInTable(Log::getTable(), [
            'itemtype' => $class,
        ]);
        $this->integer($count_logs)->isEqualTo(1); // Only $subject creation log
    }

    /**
     * Test that any display preferences entries related to ITIL items
     * are deleted when the capacity is disabled.
     *
     * @dataProvider getITILClassProvider
     *
     * @return void
     */
    public function testDisplayPreferencesDataDeletion(string $itil): void
    {
        // Must be logged in as some code in SearchOption::getOptionsForItemtype()
        // require a valid session
        $this->login();

        // Create custom asset definition with the target capacity enabled
        $definition = $this->initAssetDefinition(
            capacities: [$this->getTargetCapacity()]
        );
        $class = $definition->getConcreteClassName();

        // Create our test subject and enable the capacity
        $subject = $this->createItem($class, [
            'name' => 'Test asset',
        ]);

        // Set display preferences on both common ITIL objects and the asset
        $this->createItems(DisplayPreference::class, [
            [
                'itemtype' => $subject::getType(),
                'num'      => '60', // Number of tickets
                'users_id' => 0,
            ],
            [
                'itemtype' => $subject::getType(),
                'num'      => '140', // Number of problem
                'users_id' => 0,
            ],
            [
                'itemtype' => $subject::getType(),
                'num'      => '3', // Location
                'users_id' => 0,
            ]
        ]);

        // Count display preferences, should be 3
        $count_display_preferences = countElementsInTable(
            DisplayPreference::getTable(),
            [
                'itemtype' => $subject::getType(),
            ]
        );
        $this->integer($count_display_preferences)->isEqualTo(3);

        // Disable capacity, display preferences related to itil items should be
        // deleted while display preferences related to the asset should not be
        // deleted
        $definition = $this->disableCapacity(
            $definition,
            $this->getTargetCapacity()
        );
        $count_display_preferences = countElementsInTable(
            DisplayPreference::getTable(),
            [
                'itemtype' => $subject::getType(),
            ]
        );
        $this->integer($count_display_preferences)->isEqualTo(1);
    }

    protected function testGetConfigurationMessagesProvider(): iterable
    {
        // Create custom asset definition with the target capacity enabled
        $definition = $this->initAssetDefinition(
            capacities: [$this->getTargetCapacity()]
        );
        $class = $definition->getConcreteClassName();

        // No profiles allowed
        yield [
            "class"          => $class,
            "message"        => "This asset definition is not enabled on any profiles.",
            "search_results" => [],
        ];


        // Allow this asset on the super admin profile
        $profile = getItemByTypeName(Profile::class, "Super-Admin");
        $itemtypes = importArrayFromDB($profile->fields["helpdesk_item_type"]);
        $itemtypes[] = $class;
        $this->updateItem(Profile::class, $profile->getID(), [
            'helpdesk_item_type' => exportArrayToDB($itemtypes),
        ]);
        yield [
            "class"          => $class,
            "message"        => "This asset definition is enabled on 1 profile(s).",
            "search_results" => ["Super-Admin"],
        ];
    }

    /**
     * Test the getConfigurationMessages() method
     *
     * @dataProvider testGetConfigurationMessagesProvider
     *
     * @return void
     */
    public function testGetConfigurationMessages(
        string $class,
        string $message,
        array $search_results
    ): void {
        $capacity = $this->getTargetCapacity();
        $capacity_item = new $capacity();
        $messages = $capacity_item->getConfigurationMessages($class);

        // Validate message content
        $this->array($messages)->hasSize(1);
        $this->string($messages[0]["text"])->isEqualTo($message);

        // Run search criteria found in the message link
        $parts = parse_url($messages[0]["link"]);
        parse_str($parts["query"], $query_params);
        $search = Search::getDatas(Profile::class, $query_params, []);

        // Convert search result into simple array of names
        $profiles = [];
        foreach ($search["data"]["rows"] as $row) {
            $profiles[] = $row["raw"]["ITEM_Profile_1"];
        }
        $this->array($profiles)->isEqualTo($search_results);
    }
}
