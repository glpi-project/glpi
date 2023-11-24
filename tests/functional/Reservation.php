<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

namespace tests\units;

use DbTestCase;

class Reservation extends DbTestCase
{
    public function testGetReservableItemtypes(): void
    {
        // No reservable items
        $this->array(\Reservation::getReservableItemtypes())->isEqualTo([]);

        $root = getItemByTypeName("Entity", "_test_root_entity", true);

        // Enable reservation on a computer
        $computer = $this->createItem("Computer", [
            "name"        => "test",
            "entities_id" => $root,
        ]);
        $this->createItem("ReservationItem", [
            "itemtype"    => "Computer",
            "items_id"    => $computer->getID(),
            "is_active"   => true,
            "entities_id" => $root,
        ]);
        $this->array(\Reservation::getReservableItemtypes())->isEqualTo(["Computer"]);
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
        $this->integer(count($reservation->find()))->isEqualTo(0);

        $reservation->add([
            "itemtype"  => "Computer",
            "items" => [
                0       => (string) $res_item->fields["id"]
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
            "comment"   => ""
        ]);
        $this->integer(count($reservation->find()))->isEqualTo(5);
    }

    protected function dataAddReservationTest(): array
    {
        return [
            [
                'begin'                   => "2023-11-01 00:00:00",
                'end'                     => "2023-11-01 00:10:00",
            ],
            [
                'begin'                   => "2023-11-02 00:00:00",
                'end'                     => "2023-11-02 23:00:00",
            ],
            [
                'begin'                   => "2023-11-03 00:00:00",
                'end'                     => "2023-11-04 00:00:00",
            ]
        ];
    }

    /**
     * @dataprovider dataAddReservationTest
     */
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
        $this->integer(count($reservation->find($data)))->isEqualTo(0);

        $reservation->add($data);
        $this->integer(count($reservation->find($data)))->isEqualTo(1);
    }

    public function testDeleteRecurrentReservation(): void
    {
        self::testAddRecurrentReservation();
        $reservation = new \Reservation();
        $this->integer(count($reservation->find()))->isEqualTo(5);
        foreach ($reservation->find() as $res) {
            $firstres = $res;
            break;
        }
        $reservation->delete($firstres + ['_delete_group' => 'on']);
        $this->integer(count($reservation->find()))->isEqualTo(0);
    }
}
