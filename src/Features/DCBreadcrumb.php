<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

namespace Glpi\Features;

use Datacenter;
use DCRoom;
use Enclosure;
use Item_Enclosure;
use Item_Rack;
use Rack;

/**
 * Datacenter breadcrumb
 **/
trait DCBreadcrumb
{
    /**
     * Get datacenter element breadcrumb
     *
     * @return array
     */
    public function getDcBreadcrumb()
    {
        global $CFG_GLPI;

        $item = $this;

        $types = $CFG_GLPI['rackable_types'];
        $enclosure_types = $types;
        unset($enclosure_types[array_search('Enclosure', $enclosure_types)]);

        $breadcrumb = [];
        if (in_array($item->getType(), $enclosure_types)) {
           //check if asset is part of an enclosure
            if ($enclosure = $this->isEnclosurePart($item->getType(), $item->getID(), true)) {
                $options = [
                    'linkoption' => $enclosure->isDeleted() ? 'class="target-deleted"' : '',
                    'icon'       => true
                ];
                $position = $this->getItemEnclosurePosition($item->getType(), $item->getID());
                $breadcrumb[] = $enclosure->getLink($options) . $position;
                $item = $enclosure;
            }
        }

        if (in_array($item->getType(), $types)) {
           //check if asset (or its enclosure) is part of a rack
            if ($rack = $this->isRackPart($item->getType(), $item->getID(), true)) {
                $options = [
                    'linkoption' => $rack->isDeleted() ? 'class="target-deleted"' : '',
                    'icon'       => true
                ];
                $position = $this->getItemRackPosition($item->getType(), $item->getID());
                $breadcrumb[] = $rack->getLink($options) . $position;
                $item = $rack;
            }
        }

        if ($item->getType() == Rack::getType()) {
            if ($item->fields['dcrooms_id'] > 0) {
                $dcroom = new DCRoom();
                if ($dcroom->getFromDB($item->fields['dcrooms_id'])) {
                    $options = [
                        'linkoption' => $dcroom->isDeleted() ? 'class="target-deleted"' : '',
                        'icon'       => true
                    ];
                    $breadcrumb[] = $dcroom->getLink($options);
                    $item = $dcroom;
                }
            }
        }

        if ($item->getType() == DCRoom::getType()) {
            if ($item->fields['datacenters_id'] > 0) {
                $datacenter = new Datacenter();
                if ($datacenter->getFromDB($item->fields['datacenters_id'])) {
                    $options = [
                        'linkoption' => $datacenter->isDeleted() ? 'class="target-deleted"' : '',
                        'icon'       => true
                    ];
                    $breadcrumb[] = $datacenter->getLink($options);
                }
            }
        }

        return $breadcrumb;
    }

    /**
     * Check if an item is part of an Enclosure
     *
     * @param string  $itemtype Item type
     * @param integer $items_id Item ID
     * @param boolean $getobj   Whether to return enclosure object
     *
     * @return false|Enclosure
     */
    public function isEnclosurePart($itemtype, $items_id, $getobj = false)
    {
        $ien = new Item_Enclosure();
        $found = $ien->getFromDBByCrit([
            'itemtype'  => $itemtype,
            'items_id'  => $items_id
        ]);

        if ($found && $getobj) {
            $enclosure = new Enclosure();
            if ($enclosure->getFromDB($ien->fields['enclosures_id'])) {
                return $enclosure;
            } else {
               // Association to unexisting enclosure is possible due to a bug in 9.3.0.
                return false;
            }
        }

        return $found;
    }

    /**
     * get item position from Enclosure
     *
     * @param string  $itemtype Item type
     * @param integer $items_id Item ID
     *
     * @return string
     */
    private function getItemEnclosurePosition($itemtype, $items_id)
    {
        $position = 0;
        $ien = new Item_Enclosure();

        if (
            $ien->getFromDBByCrit([
                'itemtype'  => $itemtype,
                'items_id'  => $items_id
            ])
        ) {
            $position = $ien->getField('position');
        }

        $position = "&nbsp;" . sprintf(__('(U%1$u)'), $position);
        return $position;
    }

    /**
     * get item position from Rack
     *
     * @param string  $itemtype Item type
     * @param integer $items_id Item ID
     *
     * @return string
     */
    private function getItemRackPosition($itemtype, $items_id)
    {
        $position = 0;
        $ira = new Item_Rack();

        if (
            $ira->getFromDBByCrit([
                'itemtype'  => $itemtype,
                'items_id'  => $items_id
            ])
        ) {
            $position = $ira->getField('position');
        }

        $position = "&nbsp;" . sprintf(__('(U%1$u)'), $position);
        return $position;
    }

    /**
     * Check if an item is part of a rack
     *
     * @param string  $itemtype Item type
     * @param integer $items_id Item ID
     * @param boolean $getobj   Whether to return rack object
     *
     * @return false|Rack
     */
    public function isRackPart($itemtype, $items_id, $getobj = false)
    {
        $ira = new Item_Rack();
        $found = $ira->getFromDBByCrit([
            'itemtype'  => $itemtype,
            'items_id'  => $items_id
        ]);
        if ($found && $getobj) {
            $rack = new \Rack();
            if ($rack->getFromDb($ira->fields['racks_id'])) {
                return $rack;
            } else {
               // Association to unexisting rack is possible due to a bug in 9.3.0.
                return false;
            }
        }

        return $found;
    }

    /**
     * Specific value for "Data center position".
     *
     * @return array
     */
    public static function getDcBreadcrumbSpecificValueToDisplay($items_id)
    {

        $item = new static();

        if ($item->getFromDB($items_id)) {
            $breadcrumb = $item->getDcBreadcrumb();
            if (count($breadcrumb) > 0) {
                return implode(' &gt; ', array_reverse($item->getDcBreadcrumb()));
            }
        }

        return '&nbsp;';
    }
}
