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

use Certificate_Item;
use DbTestCase;
use Glpi\Asset\Capacity\HasCertificatesCapacity;
use Glpi\Features\Clonable;
use Toolbox;

class Certificate_ItemTest extends DbTestCase
{
    public function testRelatedItemHasTab()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [HasCertificatesCapacity::class]);

        $this->login(); // tab will be available only if corresponding right is available in the current session

        foreach ($CFG_GLPI['certificate_types'] as $itemtype) {
            $item = $this->createItem(
                $itemtype,
                $this->getMinimalCreationInput($itemtype)
            );

            $tabs = $item->defineAllTabs();
            $this->assertArrayHasKey('Certificate_Item$1', $tabs, $itemtype);
        }
    }

    public function testRelatedItemCloneRelations()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [HasCertificatesCapacity::class]);

        foreach ($CFG_GLPI['certificate_types'] as $itemtype) {
            if (!Toolbox::hasTrait($itemtype, Clonable::class)) {
                continue;
            }

            $item = \getItemForItemtype($itemtype);
            $this->assertContains(Certificate_Item::class, $item->getCloneRelations(), $itemtype);
        }
    }

    public function testRelations()
    {
        $this->login();

        $root_entity_id = getItemByTypeName('Entity', '_test_root_entity', true);

        $cert_item = new \Certificate_Item();
        $cert = new \Certificate();

        $input = [
            'name'        => 'Test certificate',
            'entities_id' => $root_entity_id,
        ];
        $cid1 = $cert->add($input);
        $this->assertGreaterThan(0, $cid1);

        $input = [
            'name'        => 'Test certificate 2',
            'entities_id' => $root_entity_id,
        ];
        $cid2 = $cert->add($input);
        $this->assertGreaterThan(0, $cid2);

        $input = [
            'name'        => 'Test certificate 3',
            'entities_id' => $root_entity_id,
        ];
        $cid3 = $cert->add($input);
        $this->assertGreaterThan(0, $cid3);

        $input = [
            'name'        => 'Test certificate 4',
            'entities_id' => $root_entity_id,
        ];
        $cid4 = $cert->add($input);
        $this->assertGreaterThan(0, $cid4);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $printer = getItemByTypeName('Printer', '_test_printer_all');

        $input = [
            'certificates_id' => $cid1,
            'itemtype'        => 'Computer',
            'items_id'        => $computer->getID()
        ];
        $this->assertGreaterThan(0, $cert_item->add($input));

        $input['certificates_id'] = $cid2;
        $this->assertGreaterThan(0, $cert_item->add($input));

        $input['certificates_id'] = $cid3;
        $this->assertGreaterThan(0, $cert_item->add($input));

        $input = [
            'certificates_id' => $cid1,
            'itemtype'        => 'Printer',
            'items_id'        => $printer->getID()
        ];
        $this->assertGreaterThan(0, $cert_item->add($input));

        $input['certificates_id'] = $cid4;
        $this->assertGreaterThan(0, $cert_item->add($input));

        $list_items = iterator_to_array($cert_item->getListForItem($computer));
        $this->assertCount(3, $list_items);
        $this->assertArrayHasKey($cid1, $list_items);
        $this->assertArrayHasKey($cid2, $list_items);
        $this->assertArrayHasKey($cid3, $list_items);

        $list_items = iterator_to_array($cert_item->getListForItem($printer));
        $this->assertCount(2, $list_items);
        $this->assertArrayHasKey($cid1, $list_items);
        $this->assertArrayHasKey($cid4, $list_items);

        $this->assertTrue($cert->getFromDB($cid1));

        $list_types = iterator_to_array($cert_item->getDistinctTypes($cid1));
        $expected = [
            ['itemtype' => 'Computer'],
            ['itemtype' => 'Printer']
        ];
        $this->assertSame($expected, $list_types);

        foreach ($list_types as $type) {
            $list_items = iterator_to_array($cert_item->getTypeItems($cid1, $type['itemtype']));
            $this->assertCount(1, $list_items);
        }

        $this->assertSame(3, $cert_item->countForItem($computer));
        $this->assertSame(2, $cert_item->countForItem($printer));

        $computer = getItemByTypeName('Computer', '_test_pc02');
        $this->assertSame(0, $cert_item->countForItem($computer));

        $this->assertSame(2, $cert_item->countForMainItem($cert));
    }

    public function testgetListForItemParamsForCertificate()
    {
        $cert = new \Certificate();
        $cert_item = new \Certificate_Item();
        $this->expectExceptionMessage('Cannot use getListForItemParams() for a Certificate');
        $cert_item->countForItem($cert);
    }
}
