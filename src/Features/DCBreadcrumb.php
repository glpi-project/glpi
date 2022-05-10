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
use Dropdown;
use Enclosure;
use Glpi\Application\View\TemplateRenderer;
use Item_Enclosure;
use Item_Rack;
use Location;
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
    public function getDcBreadcrumb($with_location = false)
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
                $breadcrumb_item = [
                    'link' => $enclosure->getLink($options),
                    'position' => $position
                ];

                if ($with_location) {
                    $breadcrumb_item['location']['item'] = Location::getItemLocation($enclosure);
                }
                $breadcrumb[Enclosure::getType()] = $breadcrumb_item;
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
                $breadcrumb_item = [
                    'link' => $rack->getLink($options),
                    'position' => $position
                ];

                if ($with_location) {
                    $breadcrumb_item['location']['item'] = Location::getItemLocation($enclosure);
                }

                $breadcrumb[Rack::getType()] = $breadcrumb_item;
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

                    $breadcrumb_item = [
                        'link' => $dcroom->getLink($options),
                    ];

                    if ($with_location) {
                        $breadcrumb_item['location']['item'] = Location::getItemLocation($enclosure);
                    }

                    $breadcrumb[DCRoom::getType()] = $breadcrumb_item;
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

                    $breadcrumb_item = [
                        'link' => $datacenter->getLink($options),
                    ];

                    if ($with_location) {
                        $breadcrumb_item['location']['item'] = Location::getItemLocation($enclosure);
                    }

                    $breadcrumb[Datacenter::getType()] = $breadcrumb_item;
                }
            }
        }

        $breadcrumb = array_reverse($breadcrumb);

        if ($with_location) {
            $breadcrumb = $this->cleanLocationInformations($breadcrumb);
        }

        return $breadcrumb;
    }

    /**
     * Clean Location information foreach item breadcrumb
     * If Current item Location = Parent item Location              -> do not display Location
     * If Current item Location is child of Parent item Location    -> display only Location name instead of completename
     * Else Current item Location != Parent item Location           -> display Location completename
     *
     * @param array $breadcrumb
     *
     * @return array cleanded $breadcrumb
     */
    public function cleanLocationInformations(&$breadcrumb)
    {
        $rack_location = isset($breadcrumb[Rack::getType()]) ? $breadcrumb[Rack::getType()]['location']['item'] : null;
        $enclosure_location = isset($breadcrumb[Enclosure::getType()]) ? $breadcrumb[Enclosure::getType()]['location']['item'] : null;
        $dcroom_location = isset($breadcrumb[DCRoom::getType()]) ? $breadcrumb[DCRoom::getType()]['location']['item'] : null;
        $datacenter_location = isset($breadcrumb[Datacenter::getType()]) ? $breadcrumb[Datacenter::getType()]['location']['item'] : null;

        if ($rack_location) {
            if ($rack_location->fields['id'] ==  $dcroom_location->fields['id']) {
                unset($breadcrumb[Rack::getType()]['location']);
            } else if ($rack_location->fields['locations_id'] == $dcroom_location->fields['id']) {
                $breadcrumb[Rack::getType()]['location']['name'] = "> " . $rack_location->fields['name'];
            } else {
                $breadcrumb[Rack::getType()]['location']['name'] = $rack_location->fields['completename'];
            }
        }

        if ($enclosure_location) {
            if ($enclosure_location->fields['id'] ==  $dcroom_location->fields['id']) {
                unset($breadcrumb[Enclosure::getType()]['location']);
            } else if ($enclosure_location->fields['locations_id'] == $dcroom_location->fields['id']) {
                $breadcrumb[Enclosure::getType()]['location']['name'] = "> " . $enclosure_location->fields['name'];
            } else {
                $breadcrumb[Enclosure::getType()]['location']['name'] = $enclosure_location->fields['completename'];
            }
        }

        if ($dcroom_location) {
            if ($dcroom_location->fields['id'] == $datacenter_location->fields['id']) {
                unset($breadcrumb[DCRoom::getType()]['location']);
            } else if ($dcroom_location->fields['locations_id'] == $datacenter_location->fields['id']) {
                $breadcrumb[DCRoom::getType()]['location']['name'] = "> " . $dcroom_location->fields['name'];
            } else {
                $breadcrumb[DCRoom::getType()]['location']['name'] = $dcroom_location->fields['completename'];
            }
        }

        //keep datacenter location completename name (start of breadcrumb)
        if ($datacenter_location) {
            $breadcrumb[Datacenter::getType()]['location']['name'] = $datacenter_location->fields['completename'];
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

        $position = sprintf(__('(U%1$u)'), $position);
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

        $position = sprintf(__('(U%1$u)'), $position);
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
     * @param integer $items_id   Item ID
     * @param boolean $with_location   display location name if needed
     * @param boolean $display   display or return HTML
     *
     * @return array
     */
    public static function getDcBreadcrumbSpecificValueToDisplay($items_id, $with_location = false, $display = true)
    {
        $item = new static();
        if ($item->getFromDB($items_id)) {
            $breadcrumb = $item->getDcBreadcrumb($with_location);
            if (count($breadcrumb) > 0) {
                $options = [
                    'breadcrumbs'   => $breadcrumb
                ];

                if ($display) {
                    return TemplateRenderer::getInstance()->display('layout/parts/dcbreadcrumbs.html.twig', $options);
                } else {
                    return TemplateRenderer::getInstance()->render('layout/parts/dcbreadcrumbs.html.twig', $options);
                }
            }
        }
        return false;
    }
}
