<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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
use Enclosure;
use Glpi\Tests\DbTestCase;
use Item_Enclosure;

use function Safe\ob_get_clean;
use function Safe\ob_start;

class Item_EnclosureTest extends DbTestCase
{
    public function testContainedItemVisibility(): void
    {
        [$enclosure, $computer] = $this->createEnclosureWithComputer(__FUNCTION__);

        $_SESSION['glpiactiveprofile'][Enclosure::$rightname] = READ;
        $_SESSION['glpiactiveprofile'][Computer::$rightname] = 0;

        ob_start();
        $this->assertTrue(Item_Enclosure::showItems($enclosure));
        $html = ob_get_clean();

        $this->assertStringContainsString('You are not allowed to view this item', $html);
        $this->assertStringNotContainsString($computer->fields['name'], $html);

        $_SESSION['glpiactiveprofile'][Computer::$rightname] = READ;

        ob_start();
        $this->assertTrue(Item_Enclosure::showItems($enclosure));
        $html = ob_get_clean();

        $this->assertStringContainsString($computer->fields['name'], $html);
        $this->assertStringNotContainsString('You are not allowed to view this item', $html);
    }

    public function testUpdatingRelationRequiresRightsOnBothItems(): void
    {
        [$enclosure, $computer, $relation] = $this->createEnclosureWithComputer(__FUNCTION__);

        $_SESSION['glpiactiveprofile'][Enclosure::$rightname] = UPDATE;
        $_SESSION['glpiactiveprofile'][Computer::$rightname] = UPDATE;
        $this->assertTrue($relation->canUpdateItem());

        $_SESSION['glpiactiveprofile'][Enclosure::$rightname] = 0;
        $this->assertFalse($relation->canUpdateItem());

        $_SESSION['glpiactiveprofile'][Enclosure::$rightname] = UPDATE;
        $_SESSION['glpiactiveprofile'][Computer::$rightname] = 0;
        $this->assertFalse($relation->canUpdateItem());
    }

    /**
     * @return array{Enclosure, Computer, Item_Enclosure}
     */
    private function createEnclosureWithComputer(string $test_name): array
    {
        $this->login();
        $this->setEntity('_test_root_entity', false);
        $entities_id = getItemByTypeName('Entity', '_test_root_entity', true);

        $enclosure = $this->createItem(Enclosure::class, [
            'name'        => 'Test enclosure ' . $test_name,
            'entities_id' => $entities_id,
        ]);
        $computer = $this->createItem(Computer::class, [
            'name'        => 'Test enclosed computer ' . $test_name,
            'entities_id' => $entities_id,
        ]);
        $relation = $this->createItem(Item_Enclosure::class, [
            'enclosures_id' => $enclosure->getID(),
            'itemtype'      => Computer::class,
            'items_id'      => $computer->getID(),
            'position'      => 1,
        ]);

        return [$enclosure, $computer, $relation];
    }
}
