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
        $reservation_item = $this->createItem("ReservationItem", [
            "itemtype"    => "Computer",
            "items_id"    => $computer->getID(),
            "is_active"   => true,
            "entities_id" => $root,
        ]);
        // Nothing showing because we are not logged in
        $this->array(\Reservation::getReservableItemtypes())->size->isEqualTo(0);

        $this->login();
        $this->array(\Reservation::getReservableItemtypes())->isEqualTo(["Computer"]);

        \Session::changeActiveEntities(getItemByTypeName("Entity", "_test_child_1", true));
        // Nothing showing because we are now in a child entity and the computer is not recursive
        $this->array(\Reservation::getReservableItemtypes())->size->isEqualTo(0);

        //Make computer recursive and check again
        $this->boolean($computer->update([
            'id' => $computer->getID(),
            "is_recursive" => true,
        ]))->isTrue();
        $this->boolean($reservation_item->update([
            'id' => $reservation_item->getID(),
            "is_recursive" => true,
        ]))->isTrue();
        $this->array(\Reservation::getReservableItemtypes())->isEqualTo(["Computer"]);
    }
}
