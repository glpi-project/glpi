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

use DbTestCase;

/* Test for inc/item_line.class.php */

class Item_LineTest extends DbTestCase
{
    /**
     * Test for linking items to lines
     *
     * @return void
     */
    public function testAdd()
    {
        $line = new \Line();

        $this->assertGreaterThan(
            0,
            (int) $line->add([
                'name'         => 'Test line - testAdd',
                'entities_id'  => getItemByTypeName('Entity', '_test_root_entity', true),
            ])
        );

        $item_line = new \Item_Line();

        $phone_1 = getItemByTypeName('Phone', 'PHONE-LNE-1', true);
        $phone_2 = getItemByTypeName('Phone', 'PHONE-LNE-2', true);

        //try to add without required field
        $item_line->getEmpty();
        $this->assertFalse(
            $item_line->add([
                'itemtype'  => 'Phone',
                'items_id'  => $phone_1,
            ])
        );

        $this->hasSessionMessages(ERROR, ['A line is required']);

        //try to add without required field
        $item_line->getEmpty();
        $this->assertFalse(
            $item_line->add([
                'lines_id'  => $line->fields['id'],
                'items_id'  => $phone_1,
            ])
        );

        $this->hasSessionMessages(ERROR, ['An item type is required']);

        //try to add without required field
        $item_line->getEmpty();
        $this->assertFalse(
            $item_line->add([
                'lines_id'  => $line->fields['id'],
                'itemtype'  => 'Phone',
            ])
        );

        $this->hasSessionMessages(ERROR, ['An item is required']);

        //try to add without error
        $item_line->getEmpty();
        $this->assertGreaterThan(
            0,
            (int) $item_line->add([
                'lines_id'  => $line->fields['id'],
                'itemtype'  => 'Phone',
                'items_id'  => $phone_1,
            ])
        );

        //Add another item linked to line
        $item_line->getEmpty();
        $this->assertGreaterThan(
            0,
            (int) $item_line->add([
                'lines_id'  => $line->fields['id'],
                'itemtype'  => 'Phone',
                'items_id'  => $phone_2,
            ])
        );

        $this->assertCount(
            2,
            $item_line->find(['lines_id'  => $line->fields['id']])
        );
    }
}
