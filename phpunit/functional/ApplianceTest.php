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

class ApplianceTest extends DbTestCase
{
    public function testDefineTabs()
    {
        $expected = [
            'Appliance$main'     => 'Appliance',
            'Impact$1'           => 'Impact analysis',
            'ManualLink$1'       => 'Links',
        ];

        $appliance = new \Appliance();
        $this->assertSame($expected, $appliance->defineTabs());
    }

    public function testGetTypes()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $appliance = new \Appliance();
        $this->assertSame($CFG_GLPI['appliance_types'], $appliance->getTypes(true));
        $this->assertSame([], $appliance->getTypes());

        $this->login();
        $this->assertSame($CFG_GLPI['appliance_types'], $appliance->getTypes());
    }

    public function testClone()
    {
        $this->login();
        $app = new \Appliance();

        // Add
        $id = $app->add([
            'name'        => $this->getUniqueString(),
            'entities_id' => 0
        ]);
        $this->assertGreaterThan(0, $id);

        // Update
        $id = $app->getID();
        $this->assertTrue($app->getFromDB($id));

        $date = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date;

        $iapp = new \Appliance_Item();
        $this->assertGreaterThan(
            0,
            $iapp->add([
                'appliances_id'   => $id,
                'itemtype'        => 'Computer',
                'items_id'        => getItemByTypeName('Computer', '_test_pc01', true)
            ])
        );

        $rapp = new \Appliance_Item_Relation();
        $this->assertGreaterThan(
            0,
            $rapp->add([
                'appliances_items_id'   => $iapp->fields['id'],
                'itemtype'              => 'Location',
                'items_id'              => getItemByTypeName('Location', '_location01', true)
            ])
        );

        //add infocom
        $infocom = new \Infocom();
        $this->assertGreaterThan(
            0,
            $infocom->add([
                'itemtype'  => 'Appliance',
                'items_id'  => $id
            ])
        );

        //add document
        $document = new \Document();
        $docid = (int)$document->add(['name' => 'Test link document']);
        $this->assertGreaterThan(0, $docid);

        $docitem = new \Document_Item();
        $this->assertGreaterThan(
            0,
            $docitem->add([
                'documents_id' => $docid,
                'itemtype'     => 'Appliance',
                'items_id'     => $id
            ])
        );

        // Test item cloning
        $added = (int)$app->clone();
        $this->assertGreaterThan(0, $added);
        $this->assertNotEquals($app->fields['id'], $added);

        $clonedApp = new \Appliance();
        $this->assertTrue($clonedApp->getFromDB($added));

        $fields = $app->fields;

        // Check the values. ID and dates must be different, everything else must be equal
        foreach ($fields as $k => $v) {
            switch ($k) {
                case 'id':
                    $this->assertNotEquals($app->getField($k), $clonedApp->getField($k));
                    break;
                case 'date_mod':
                case 'date_creation':
                    $dateClone = new \DateTime($clonedApp->getField($k));
                    $expectedDate = new \DateTime($date);
                    $this->assertEquals($expectedDate, $dateClone);
                    break;
                case 'name':
                    $this->assertEquals("{$app->getField($k)} (copy)", $clonedApp->getField($k));
                    break;
                default:
                    $this->assertEquals($app->getField($k), $clonedApp->getField($k));
            }
        }

        //Infocom has been cloned
        $this->assertSame(
            1,
            countElementsInTable(
                \Infocom::getTable(),
                ['items_id' => $clonedApp->fields['id']]
            )
        );

        //documents has been cloned
        $this->assertTrue($docitem->getFromDBByCrit(['itemtype' => 'Appliance', 'items_id' => $added]));

        //items has been cloned
        $this->assertTrue($iapp->getFromDBByCrit(['appliances_id' => $added]));

        //relations has been cloned
        $this->assertTrue($rapp->getFromDBByCrit(['appliances_items_id' => $iapp->fields['id']]));
    }

    public function testMetaSearch()
    {
        $this->login();

        $computer = new \Computer();
        $computers_id = $computer->add([
            'name' => 'Test computer',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true)
        ]);
        $this->assertGreaterThan(0, $computers_id);

        $cluster = new \Cluster();
        $clusters_id = $cluster->add([
            'name' => 'Test cluster',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true)
        ]);
        $this->assertGreaterThan(0, $clusters_id);

        $appliance = new \Appliance();
        $appliances_id = $appliance->add([
            'name' => 'Test appliance',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true)
        ]);
        $this->assertGreaterThan(0, $appliances_id);

        $appliance_item = new \Appliance_Item();
        $this->assertGreaterThan(
            0,
            $appliance_item->add([
                'appliances_id' => $appliances_id,
                'itemtype'      => 'Computer',
                'items_id'      => $computers_id
            ])
        );

        $this->assertGreaterThan(
            0,
            $appliance_item->add([
                'appliances_id' => $appliances_id,
                'itemtype'      => 'Cluster',
                'items_id'      => $clusters_id
            ])
        );

        $criteria = [
            [
                'link' => 'AND',
                'itemtype' => 'Computer',
                'meta' => true,
                'field' => 1, //Name
                'searchtype' => 'contains',
                'value' => 'computer',
            ]
        ];
        $data = \Search::getDatas('Appliance', [
            'criteria' => $criteria,
        ]);
        $this->assertSame(1, $data['data']['totalcount']);
        $this->assertSame('Test computer', $data['data']['rows'][0]['Computer_1'][0]['name']);

        $criteria = [
            [
                'link' => 'AND',
                'itemtype' => 'Cluster',
                'meta' => true,
                'field' => 1, //Name
                'searchtype' => 'contains',
                'value' => 'cluster',
            ]
        ];
        $data = \Search::getDatas('Appliance', [
            'criteria' => $criteria,
        ]);
        $this->assertSame(1, $data['data']['totalcount']);
        $this->assertSame('Test cluster', $data['data']['rows'][0]['Cluster_1'][0]['name']);
    }
}
