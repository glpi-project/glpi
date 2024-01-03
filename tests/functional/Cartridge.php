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

namespace tests\units;

use DbTestCase;

/* Test for inc/cartridge.class.php */

class Cartridge extends DbTestCase
{
    public function testInstall()
    {
        $printer = new \Printer();
        $pid = $printer->add([
            'name'         => 'Test printer',
            'entities_id'  => getItemByTypeName('Entity', '_test_root_entity', true)
        ]);
        $this->integer((int)$pid)->isGreaterThan(0);
        $this->boolean($printer->getFromDB($pid))->isTrue();

        $ctype = new \CartridgeItemType();
        $tid = $ctype->add([
            'name'         => 'Test cartridge type',
        ]);
        $this->integer((int)$tid)->isGreaterThan(0);
        $this->boolean($ctype->getFromDB($tid))->isTrue();

        $citem = new \CartridgeItem();
        $ciid = $citem->add([
            'name'                  => 'Test cartridge item',
            'cartridgeitemtypes_id' => $tid
        ]);
        $this->integer((int)$ciid)->isGreaterThan(0);
        $this->boolean($citem->getFromDB($ciid))->isTrue();

        $cartridge = new \Cartridge();
        $cid = $cartridge->add([
            'name'               => 'Test cartridge',
            'cartridgeitems_id'  => $ciid
        ]);
        $this->integer((int)$cid)->isGreaterThan(0);
        $this->boolean($cartridge->getFromDB($cid))->isTrue();
        $this->integer($cartridge->getUsedNumber($ciid))->isIdenticalTo(0);
        $this->integer($cartridge->getTotalNumberForPrinter($pid))->isIdenticalTo(0);

       //install
        $this->boolean($cartridge->install($pid, $ciid))->isTrue();
       //check install
        $this->boolean($cartridge->getFromDB($cid))->isTrue();
        $this->array($cartridge->fields)
         ->variable['printers_id']->isEqualTo($pid)
         ->string['date_use']->matches('#\d{4}-\d{2}-\d{2}$#');
        $this->variable($cartridge->fields['date_out'])->isNull();
       //already installed
        $this->boolean($cartridge->install($pid, $ciid))->isFalse();
        $this->hasSessionMessages(ERROR, ['No free cartridge']);

        $this->integer($cartridge->getUsedNumber($ciid))->isIdenticalTo(1);
        $this->integer($cartridge->getTotalNumberForPrinter($pid))->isIdenticalTo(1);

        $this->boolean($cartridge->uninstall($cid))->isTrue();
       //this is not possible... But don't know if this is expected
       //$this->boolean($cartridge->install($pid, $ciid))->isTrue();
       //check uninstall
        $this->boolean($cartridge->getFromDB($cid))->isTrue();
        $this->string($cartridge->fields['date_out'])->matches('#\d{4}-\d{2}-\d{2}$#');
        $this->integer($cartridge->getUsedNumber($ciid))->isIdenticalTo(0);
    }

    public function testInfocomInheritance()
    {
        $cartridge = new \Cartridge();

        $cartridge_item = new \CartridgeItem();
        $cu_id = (int) $cartridge_item->add([
            'name' => 'Test cartridge item'
        ]);
        $this->integer($cu_id)->isGreaterThan(0);

        $infocom = new \Infocom();
        $infocom_id = (int) $infocom->add([
            'itemtype'  => \CartridgeItem::getType(),
            'items_id'  => $cu_id,
            'buy_date'  => '2020-10-21',
            'value'     => '500'
        ]);
        $this->integer($infocom_id)->isGreaterThan(0);

        $cartridge_id = $cartridge->add([
            'cartridgeitems_id' => $cu_id
        ]);
        $this->integer($cartridge_id)->isGreaterThan(0);

        $infocom2 = new \Infocom();
        $infocom2_id = (int) $infocom2->getFromDBByCrit([
            'itemtype'  => \Cartridge::getType(),
            'items_id'  => $cartridge_id
        ]);
        $this->integer($infocom2_id)->isGreaterThan(0);
        $this->string($infocom2->fields['buy_date'])->isEqualTo($infocom->fields['buy_date']);
        $this->string($infocom2->fields['value'])->isEqualTo($infocom->fields['value']);
    }
}
