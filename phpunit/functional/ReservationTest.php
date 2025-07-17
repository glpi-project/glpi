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

namespace tests\units;

use Computer;
use DbTestCase;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\IsReservableCapacity;
use MassiveAction;
use PHPUnit\Framework\Attributes\DataProvider;
use ReservationItem;

class ReservationTest extends DbTestCase
{
    public function testRelatedItemHasTab()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: IsReservableCapacity::class)]);

        $this->login(); // tab will be available only if corresponding right is available in the current session

        foreach ($CFG_GLPI['reservation_types'] as $itemtype) {
            $item = $this->createItem(
                $itemtype,
                $this->getMinimalCreationInput($itemtype)
            );

            $tabs = $item->defineAllTabs();
            $this->assertArrayHasKey('Reservation$1', $tabs, $itemtype);
        }
    }

    public function testGetReservableItemtypes(): void
    {
        // No reservable items
        $this->assertEquals([], \Reservation::getReservableItemtypes());

        $root = getItemByTypeName("Entity", "_test_root_entity", true);

        // Enable reservation on a computer
        $computer = $this->createItem("Computer", [
            "name"        => "test",
            "entities_id" => $root,
        ]);
        $reservation_item = $this->createItem("ReservationItem", [
            "itemtype"    => "Computer",
            "items_id"    => $computer->getID(),
            "is_active"   => true,
            "entities_id" => $root,
        ]);
        // Nothing showing because we are not logged in
        $this->assertCount(0, \Reservation::getReservableItemtypes());

        $this->login();
        $this->assertEquals(["Computer"], \Reservation::getReservableItemtypes());

        \Session::changeActiveEntities(getItemByTypeName("Entity", "_test_child_1", true));
        // Nothing showing because we are now in a child entity and the computer is not recursive
        $this->assertCount(0, \Reservation::getReservableItemtypes());

        //Make computer recursive and check again
        $this->assertTrue($computer->update([
            'id' => $computer->getID(),
            "is_recursive" => true,
        ]));
        $this->assertTrue($reservation_item->update([
            'id' => $reservation_item->getID(),
            "is_recursive" => true,
        ]));
        $this->assertEquals(["Computer"], \Reservation::getReservableItemtypes());
    }

    public function testAddRecurrentReservation(): void
    {
        $this->login();
        $computer = $this->createItem("Computer", [
            "name"        => "test",
            "entities_id" => 0,
        ]);
        $res_item = $this->createItem("ReservationItem", [
            "itemtype"    => "Computer",
            "items_id"    => $computer->getID(),
            "is_active"   => true,
            "entities_id" => 0,
        ]);
        $reservation = new \Reservation();
        $this->assertEquals(0, count($reservation->find()));

        \Reservation::handleAddForm([
            "itemtype"  => "Computer",
            "items" => [
                0       => (string) $res_item->fields["id"],
            ],
            "resa" => [
                "begin" => "2023-11-02 00:00:00",
                "end"   => "2023-11-03 00:00:00",
            ],
            "periodicity" => [
                "type"  => "week",
                "end"   => "2023-11-30",
                "days"  => [
                    "Wednesday" => "on",
                ],
            ],
            "users_id"  => getItemByTypeName('User', TU_USER, true),
            "comment"   => "",
        ]);
        $this->assertEquals(5, count($reservation->find()));
    }

    public static function dataAddReservationTest(): array
    {
        return [
            [
                'begin'                   => "2023-11-01 00:00:00",
                'end'                     => "2023-11-01 00:10:00",
            ],
            [
                'begin'                   => "2023-11-02 00:00:00",
                'end'                     => "2023-11-25 23:00:00",
            ],
            [
                'begin'                   => "2023-11-03 00:00:00",
                'end'                     => "2023-11-04 00:00:00",
            ],
        ];
    }

    #[DataProvider('dataAddReservationTest')]
    public function testAddJustOneReservation($begin, $end): void
    {
        $this->login();
        $computer = $this->createItem("Computer", [
            "name"        => "test",
            "entities_id" => 0,
        ]);
        $res_item = $this->createItem("ReservationItem", [
            "itemtype"    => "Computer",
            "items_id"    => $computer->getID(),
            "is_active"   => true,
            "entities_id" => 0,
        ]);

        $data = [
            'begin'                   => $begin,
            'end'                     => $end,
            'reservationitems_id'     => $res_item->getID(),
            'users_id'                => getItemByTypeName('User', TU_USER, true),
        ];
        $reservation = new \Reservation();
        $this->assertCount(0, $reservation->find($data));

        $reservation->add($data);
        $this->assertCount(1, $reservation->find($data));
    }

    public function testDeleteRecurrentReservation(): void
    {
        self::testAddRecurrentReservation();
        $reservation = new \Reservation();
        $this->assertCount(5, $reservation->find());
        foreach ($reservation->find() as $res) {
            $firstres = $res;
            break;
        }
        $reservation->delete($firstres + ['_delete_group' => 'on']);
        $this->assertCount(0, $reservation->find());
    }

    public function testMassiveActions()
    {
        $this->login();

        $actions = MassiveAction::getAllMassiveActions(Computer::class);
        $this->assertArrayHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'enable', $actions);
        $this->assertArrayHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'disable', $actions);
        $this->assertArrayHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'available', $actions);
        $this->assertArrayHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'unavailable', $actions);

        $computer_template = $this->createItem(Computer::class, [
            'template_name' => __FUNCTION__ . '_template',
            'is_template' => 1,
            'entities_id' => $_SESSION['glpiactive_entity'],
        ]);
        $computer = $this->createItem(Computer::class, [
            'name' => __FUNCTION__ . '_1',
            'entities_id' => $_SESSION['glpiactive_entity'],
        ]);

        $template_actions = MassiveAction::getAllMassiveActions(Computer::class, false, $computer_template, $computer_template->getID());
        $this->assertArrayNotHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'enable', $template_actions);
        $this->assertArrayNotHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'disable', $template_actions);
        $this->assertArrayNotHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'available', $template_actions);
        $this->assertArrayNotHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'unavailable', $template_actions);

        $computer_actions = MassiveAction::getAllMassiveActions(Computer::class, false, $computer, $computer->getID());
        $this->assertArrayHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'enable', $computer_actions);
        $this->assertArrayNotHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'disable', $computer_actions);
        $this->assertArrayNotHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'available', $computer_actions);
        $this->assertArrayNotHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'unavailable', $computer_actions);

        $ri = $this->createItem(ReservationItem::class, [
            'itemtype' => Computer::class,
            'items_id' => $computer->getID(),
            'is_active' => 0,
            'entities_id' => $_SESSION['glpiactive_entity'],
        ]);
        $computer_actions = MassiveAction::getAllMassiveActions(Computer::class, false, $computer, $computer->getID());
        $this->assertArrayNotHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'enable', $computer_actions);
        $this->assertArrayHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'disable', $computer_actions);
        $this->assertArrayHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'available', $computer_actions);
        $this->assertArrayNotHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'unavailable', $computer_actions);

        $ri->update(['id' => $ri->getID(), 'is_active' => 1]);
        $computer_actions = MassiveAction::getAllMassiveActions(Computer::class, false, $computer, $computer->getID());
        $this->assertArrayNotHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'enable', $computer_actions);
        $this->assertArrayHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'disable', $computer_actions);
        $this->assertArrayNotHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'available', $computer_actions);
        $this->assertArrayHasKey('Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR . 'unavailable', $computer_actions);
    }
}
