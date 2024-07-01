<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

use CommonDBTM;
use Computer;
use DbTestCase;
use Item_Cluster;
use NetworkEquipment;

class Cluster extends DbTestCase
{
    protected function getClusterByItemProvider(): iterable
    {
        $root_entity_id = $this->getTestRootEntity(true);

        $computer = $this->createItem(
            Computer::class,
            [
                'name'        => __FUNCTION__,
                'entities_id' => $root_entity_id,
            ]
        );
        $network_equipment = $this->createItem(
            NetworkEquipment::class,
            [
                'name'        => __FUNCTION__,
                'entities_id' => $root_entity_id,
            ]
        );
        $cluster_1 = $this->createItem(
            \Cluster::class,
            [
                'name'        => __FUNCTION__,
                'entities_id' => $root_entity_id,
            ]
        );
        $cluster_2 = $this->createItem(
            \Cluster::class,
            [
                'name'        => __FUNCTION__,
                'entities_id' => $root_entity_id,
            ]
        );

        // Computer not yet linked
        yield [
            'item'     => $computer,
            'expected' => null,
        ];

        // Computer with a link to a cluster
        $computer_cluster = $this->createItem(
            Item_Cluster::class,
            [
                'items_id'    => $computer->getID(),
                'itemtype'    => Computer::class,
                'clusters_id' => $cluster_1->getID(),
            ]
        );
        yield [
            'item'     => $computer,
            'expected' => $cluster_1->getId(),
        ];

        // Computer with updated link
        $this->updateItem(
            Item_Cluster::class,
            $computer_cluster->getID(),
            [
                'clusters_id' => $cluster_2->getID(),
            ]
        );
        yield [
            'item'     => $computer,
            'expected' => $cluster_2->getId(),
        ];

        // NetworkEquipement not yet linked
        yield [
            'item'     => $network_equipment,
            'expected' => null,
        ];

        // NetworkEquipement with a link to a cluster
        $this->createItem(
            Item_Cluster::class,
            [
                'items_id'    => $network_equipment->getID(),
                'itemtype'    => NetworkEquipment::class,
                'clusters_id' => $cluster_1->getID(),
            ]
        );
        yield [
            'item'     => $network_equipment,
            'expected' => $cluster_1->getId(),
        ];
    }

    /**
     * @dataProvider getClusterByItemProvider
     */
    public function testgetClusterByItem(CommonDBTM $item, ?int $expected): void
    {
        $result = \Cluster::getClusterByItem($item);
        if ($result === null) {
            $this->variable($result)->isNull();
        } else {
            $this->object($result)->isInstanceOf(\Cluster::class);
            $this->integer($result->getID())->isEqualTo($expected);
        }
    }
}
