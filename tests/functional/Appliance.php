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

class Appliance extends DbTestCase
{
    public function testDefineTabs()
    {
        $expected = [
            'Appliance$main'     => 'Appliance',
            'Impact$1'           => 'Impact analysis',
            'ManualLink$1'       => 'Links',
        ];
        $this
         ->given($this->newTestedInstance)
            ->then
               ->array($this->testedInstance->defineTabs())
               ->isIdenticalTo($expected, print_r($this->testedInstance->defineTabs(), true));
    }

    public function testGetTypes()
    {
        global $CFG_GLPI;

        $this
         ->given($this->newTestedInstance)
            ->then
               ->array($this->testedInstance->getTypes(true))
               ->isIdenticalTo($CFG_GLPI['appliance_types']);

        $this
         ->given($this->newTestedInstance)
            ->then
               ->array($this->testedInstance->getTypes())
               ->isIdenticalTo([]);

        $this->login();
        $this
         ->given($this->newTestedInstance)
            ->then
               ->array($this->testedInstance->getTypes())
               ->isIdenticalTo($CFG_GLPI['appliance_types']);
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
        $this->integer($id)->isGreaterThan(0);

       // Update
        $id = $app->getID();
        $this->boolean($app->getFromDB($id))->isTrue();

        $date = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date;

        $iapp = new \Appliance_Item();
        $this->integer(
            $iapp->add([
                'appliances_id'   => $id,
                'itemtype'        => 'Computer',
                'items_id'        => getItemByTypeName('Computer', '_test_pc01', true)
            ])
        )->isGreaterThan(0);

        $rapp = new \Appliance_Item_Relation();
        $this->integer(
            $rapp->add([
                'appliances_items_id'   => $iapp->fields['id'],
                'itemtype'              => 'Location',
                'items_id'              => getItemByTypeName('Location', '_location01', true)
            ])
        )->isGreaterThan(0);

       //add infocom
        $infocom = new \Infocom();
        $this->integer(
            $infocom->add([
                'itemtype'  => 'Appliance',
                'items_id'  => $id
            ])
        )->isGreaterThan(0);

       //add document
        $document = new \Document();
        $docid = (int)$document->add(['name' => 'Test link document']);
        $this->integer($docid)->isGreaterThan(0);

        $docitem = new \Document_Item();
        $this->integer(
            $docitem->add([
                'documents_id' => $docid,
                'itemtype'     => 'Appliance',
                'items_id'     => $id
            ])
        )->isGreaterThan(0);

       // Test item cloning
        $added = (int)$app->clone();
        $this->integer($added)
         ->isGreaterThan(0)
         ->isNotEqualTo($app->fields['id']);

        $clonedApp = new \Appliance();
        $this->boolean($clonedApp->getFromDB($added))->isTrue();

        $fields = $app->fields;

       // Check the values. Id and dates must be different, everything else must be equal
        foreach ($fields as $k => $v) {
            switch ($k) {
                case 'id':
                    $this->variable($clonedApp->getField($k))->isNotEqualTo($app->getField($k));
                    break;
                case 'date_mod':
                case 'date_creation':
                    $dateClone = new \DateTime($clonedApp->getField($k));
                    $expectedDate = new \DateTime($date);
                    $this->dateTime($dateClone)->isEqualTo($expectedDate);
                    break;
                case 'name':
                    $this->variable($clonedApp->getField($k))->isEqualTo("{$app->getField($k)} (copy)");
                    break;
                default:
                    $this->variable($clonedApp->getField($k))->isEqualTo($app->getField($k));
            }
        }

       //Infocom has been cloned
        $this->integer(
            countElementsInTable(
                \Infocom::getTable(),
                ['items_id' => $clonedApp->fields['id']]
            )
        )->isIdenticalTo(1);

       //documents has been cloned
        $this->boolean($docitem->getFromDBByCrit(['itemtype' => 'Appliance', 'items_id' => $added]))->isTrue();

       //items has been cloned
        $this->boolean($iapp->getFromDBByCrit(['appliances_id' => $added]))->isTrue();

       //relations has been cloned
        $this->boolean($rapp->getFromDBByCrit(['appliances_items_id' => $iapp->fields['id']]))->isTrue();
    }

    public function testMetaSearch()
    {
        $this->login();

        $computer = new \Computer();
        $this->integer($computers_id = $computer->add([
            'name' => 'Test computer',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true)
        ]));
        $cluster = new \Cluster();
        $this->integer($clusters_id = $cluster->add([
            'name' => 'Test cluster',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true)
        ]));
        $appliance = new \Appliance();
        $this->integer($appliances_id = $appliance->add([
            'name' => 'Test appliance',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true)
        ]));
        $appliance_item = new \Appliance_Item();
        $this->integer($appliance_item->add([
            'appliances_id' => $appliances_id,
            'itemtype'      => 'Computer',
            'items_id'      => $computers_id
        ]));
        $this->integer($appliance_item->add([
            'appliances_id' => $appliances_id,
            'itemtype'      => 'Cluster',
            'items_id'      => $clusters_id
        ]));

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
        $this->integer($data['data']['totalcount'])->isEqualTo(1);
        $this->string($data['data']['rows'][0]['Computer_1'][0]['name'])->isEqualTo('Test computer');

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
        $this->integer($data['data']['totalcount'])->isEqualTo(1);
        $this->string($data['data']['rows'][0]['Cluster_1'][0]['name'])->isEqualTo('Test cluster');
    }
}
