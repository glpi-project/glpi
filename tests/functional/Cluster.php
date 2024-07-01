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

/* Test for inc/change.class.php */

class Cluster extends DbTestCase
{
    public function getSpecificValueToGetClusterByItem()
    {
        $computer = new \Computer();
        $computer->add([
            'name' => 'getClusterByItem',
            'entities_id' => '0',
        ]);

        $cluster = new \Cluster();
        $cluster_id = $cluster->add([
            'name' => 'getClusterByItem',
            'entities_id' => '0',
        ]);

        $network_equipment = new \NetworkEquipment();
        $network_equipment->add([
            'name' => 'getClusterByItem',
            'entities_id' => '0',
        ]);
        return [
            [
                'values' => [
                    'item' => $computer,
                    'cluster' => $cluster,
                ],
                'expected' => $cluster_id,
            ],
            [
                'values' => [
                    'item' => $network_equipment,
                    'cluster' => $cluster,
                ],
                'expected' => $cluster_id,
            ],

        ];
    }

    /**
     * @dataProvider getSpecificValueToGetClusterByItem
     */
    public function testgetClusterByItem($values, $expected)
    {

        $item_cluster = new \Item_Cluster();
        $item_cluster_id = $item_cluster->add([
            'items_id' => $values['item']->getID(),
            'itemtype' => $values['item']->getType(),
            'clusters_id' => $values['cluster']->getID(),
        ]);

        $this->integer($item_cluster_id)->isGreaterThan(0);

        $cluster = new \Cluster();
        $result = $cluster->getClusterByItem($values['item']);
        $this->integer($result->getID())->isEqualTo($expected);
    }
}
