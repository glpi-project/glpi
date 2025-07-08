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

/* Test for inc/cartridge.class.php */

class CartridgeTest extends DbTestCase
{
    public function testInstall()
    {
        $printer = new \Printer();
        $pid = $printer->add([
            'name'         => 'Test printer',
            'entities_id'  => getItemByTypeName('Entity', '_test_root_entity', true),
        ]);
        $this->assertGreaterThan(0, $pid);
        $this->assertTrue($printer->getFromDB($pid));

        $ctype = new \CartridgeItemType();
        $tid = $ctype->add([
            'name'         => 'Test cartridge type',
        ]);
        $this->assertGreaterThan(0, $tid);
        $this->assertTrue($ctype->getFromDB($tid));

        $citem = new \CartridgeItem();
        $ciid = $citem->add([
            'name'                  => 'Test cartridge item',
            'cartridgeitemtypes_id' => $tid,
        ]);
        $this->assertGreaterThan(0, $ciid);
        $this->assertTrue($citem->getFromDB($ciid));

        $cartridge = new \Cartridge();
        $cid = $cartridge->add([
            'name'               => 'Test cartridge',
            'cartridgeitems_id'  => $ciid,
        ]);
        $this->assertGreaterThan(0, $cid);
        $this->assertTrue($cartridge->getFromDB($cid));
        $this->assertSame(0, $cartridge->getUsedNumber($ciid));
        $this->assertSame(0, $cartridge->getTotalNumberForPrinter($pid));

        //install
        $this->assertTrue($cartridge->install($pid, $ciid));
        //check install
        $this->assertTrue($cartridge->getFromDB($cid));
        $this->assertSame($pid, $cartridge->fields['printers_id']);
        $this->assertMatchesRegularExpression('#\d{4}-\d{2}-\d{2}$#', $cartridge->fields['date_use']);
        $this->assertNull($cartridge->fields['date_out']);
        //already installed
        $this->assertFalse($cartridge->install($pid, $ciid));
        $this->hasSessionMessages(ERROR, ['No free cartridge']);

        $this->assertSame(1, $cartridge->getUsedNumber($ciid));
        $this->assertSame(1, $cartridge->getTotalNumberForPrinter($pid));

        $this->assertTrue($cartridge->uninstall($cid));
        //this is not possible... But don't know if this is expected
        //$this->assertTrue($cartridge->install($pid, $ciid));
        //check uninstall
        $this->assertTrue($cartridge->getFromDB($cid));
        $this->assertMatchesRegularExpression('#\d{4}-\d{2}-\d{2}$#', $cartridge->fields['date_out']);
        $this->assertSame(0, $cartridge->getUsedNumber($ciid));
    }

    public function testInfocomInheritance()
    {
        $cartridge = new \Cartridge();

        $cartridge_item = new \CartridgeItem();
        $cu_id = (int) $cartridge_item->add([
            'name' => 'Test cartridge item',
        ]);
        $this->assertGreaterThan(0, $cu_id);

        $infocom = new \Infocom();
        $infocom_id = (int) $infocom->add([
            'itemtype'  => \CartridgeItem::getType(),
            'items_id'  => $cu_id,
            'buy_date'  => '2020-10-21',
            'value'     => '500',
        ]);
        $this->assertGreaterThan(0, $infocom_id);

        $cartridge_id = $cartridge->add([
            'cartridgeitems_id' => $cu_id,
        ]);
        $this->assertGreaterThan(0, $cartridge_id);

        $infocom2 = new \Infocom();
        $infocom2_id = (int) $infocom2->getFromDBByCrit([
            'itemtype'  => \Cartridge::getType(),
            'items_id'  => $cartridge_id,
        ]);
        $this->assertGreaterThan(0, $infocom2_id);
        $this->assertEquals($infocom->fields['buy_date'], $infocom2->fields['buy_date']);
        $this->assertEquals($infocom->fields['value'], $infocom2->fields['value']);
    }
}
