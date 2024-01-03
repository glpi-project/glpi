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

/* Test for inc/item_cluster.class.php */

class Item_Cluster extends DbTestCase
{
    /**
     * Computers provider
     *
     * @return array
     */
    protected function computersProvider()
    {
        return [
            [
                'name'   => 'SRV-NUX-1',
            ], [
                'name'   => 'SRV-NUX-2',
            ]
        ];
    }

    /**
     * Create computers
     *
     * @return void
     */
    protected function createComputers()
    {
        $computer = new \Computer();
        foreach ($this->computersProvider() as $row) {
            $row['entities_id'] = 0;
            $this->integer(
                (int)$computer->add($row)
            )->isGreaterThan(0);
        }
    }

    /**
     * Test for adding items into rack
     *
     * @return void
     */
    public function testAdd()
    {
        $this->createComputers();

        $cluster = new \Cluster();

        $this->integer(
            (int)$cluster->add([
                'name'         => 'Test cluster',
                'uuid'         => 'ytreza',
                'entities_id'  => 0
            ])
        )->isGreaterThan(0);

        $icl = new \Item_Cluster();

        $SRVNUX1 = getItemByTypeName('Computer', 'SRV-NUX-1', true);
        $SRVNUX2 = getItemByTypeName('Computer', 'SRV-NUX-2', true);

       //try to add without required field
        $icl->getEmpty();
        $this->integer(
            (int)$icl->add([
                'itemtype'     => 'Computer',
                'items_id'     => $SRVNUX1
            ])
        )->isIdenticalTo(0);

        $this->hasSessionMessages(ERROR, ['A cluster is required']);

       //try to add without required field
        $icl->getEmpty();
        $this->integer(
            (int)$icl->add([
                'clusters_id'  => $cluster->fields['id'],
                'items_id'     => $SRVNUX1
            ])
        )->isIdenticalTo(0);

        $this->hasSessionMessages(ERROR, ['An item type is required']);

       //try to add without required field
        $icl->getEmpty();
        $this->integer(
            (int)$icl->add([
                'clusters_id'  => $cluster->fields['id'],
                'itemtype'     => 'Computer',
            ])
        )->isIdenticalTo(0);

        $this->hasSessionMessages(ERROR, ['An item is required']);

       //try to add without error
        $icl->getEmpty();
        $this->integer(
            (int)$icl->add([
                'clusters_id'  => $cluster->fields['id'],
                'itemtype'     => 'Computer',
                'items_id'     => $SRVNUX1
            ])
        )->isGreaterThan(0);

       //Add another item in cluster
        $icl->getEmpty();
        $this->integer(
            (int)$icl->add([
                'clusters_id'  => $cluster->fields['id'],
                'itemtype'     => 'Computer',
                'items_id'     => $SRVNUX2
            ])
        )->isGreaterThan(0);

        global $DB;
        $items = $DB->request([
            'FROM'   => $icl->getTable(),
            'WHERE'  => [
                'clusters_id' => $cluster->fields['id']
            ]
        ]);
        $this->array(iterator_to_array($items))->hasSize(2);
    }
}
