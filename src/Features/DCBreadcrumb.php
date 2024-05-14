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

namespace Glpi\Features;

use CommonDBTM;
use Datacenter;
use DCRoom;
use Enclosure;
use Glpi\Application\View\TemplateRenderer;
use Item_Enclosure;
use Item_Rack;
use Location;
use PDU;
use PDU_Rack;
use Rack;
use Toolbox;

/**
 * Datacenter breadcrumb
 **/
trait DCBreadcrumb
{
    /**
     * Get datacenter element breadcrumb
     *
     * @return array
     *
     * @deprecated 11.0.0
     */
    public function getDcBreadcrumb()
    {
        Toolbox::deprecated();

        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $item = $this;

        $types = $CFG_GLPI['rackable_types'];
        $enclosure_types = $types;
        unset($enclosure_types[array_search('Enclosure', $enclosure_types)]);

        $breadcrumb = [];

        if ($item instanceof PDU) {
            $pdu_rack = new PDU_Rack();
            $rack = new Rack();
            if (
                $pdu_rack->getFromDBByCrit(['pdus_id'  => $item->getID()])
                && $rack->getFromDB($pdu_rack->fields['racks_id'])
            ) {
                $position = '';
                switch ($pdu_rack->fields['side']) {
                    case PDU_Rack::SIDE_LEFT:
                        $position =  __('On left');
                        break;
                    case PDU_Rack::SIDE_RIGHT:
                        $position =  __('On right');
                        break;
                    case PDU_Rack::SIDE_TOP:
                        $position =  __('On top');
                        break;
                    case PDU_Rack::SIDE_BOTTOM:
                        $position =  __('On bottom');
                        break;
                }
                $position .= ' (' . $pdu_rack->fields['position'] . ')';
                $options = [
                    'linkoption' => $rack->isDeleted() ? 'class="target-deleted"' : '',
                    'icon'       => true
                ];
                $breadcrumb[] = $rack->getLink($options) . '&nbsp;' . $position;
                $item = $rack;
            }
        }

        if (in_array($item->getType(), $enclosure_types)) {
           //check if asset is part of an enclosure
            if ($enclosure = $this->isEnclosurePart($item->getType(), $item->getID(), true)) {
                $options = [
                    'linkoption' => $enclosure->isDeleted() ? 'class="target-deleted"' : '',
                    'icon'       => true
                ];
                $position = sprintf(__('(U%d)'), (int)$item->getPositionInEnclosure());
                $breadcrumb[] = $enclosure->getLink($options) . '&nbsp;' . $position;
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
                $position = sprintf(__('(U%d)'), (int)$item->getPositionInRack());
                $breadcrumb[] = $rack->getLink($options) . '&nbsp;' . $position;
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
     *
     * @deprecated 11.0.0
     */
    public function isEnclosurePart($itemtype, $items_id, $getobj = false)
    {
        Toolbox::deprecated();

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
     * Check if an item is part of a rack
     *
     * @param string  $itemtype Item type
     * @param integer $items_id Item ID
     * @param boolean $getobj   Whether to return rack object
     *
     * @return false|Rack
     *
     * @deprecated 11.0.0
     */
    public function isRackPart($itemtype, $items_id, $getobj = false)
    {
        Toolbox::deprecated();

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
     *
     * @deprecated 11.0.0
     */
    public static function getDcBreadcrumbSpecificValueToDisplay($items_id)
    {
        Toolbox::deprecated();

        $item = new static();

        if ($item->getFromDB($items_id)) {
            $breadcrumb = $item->getDcBreadcrumb();
            if (count($breadcrumb) > 0) {
                return implode(' &gt; ', array_reverse($breadcrumb));
            }
        }

        return '&nbsp;';
    }

    /**
     * Specific value for "Data center position".
     *
     * @param int $items_id
     *
     * @return string
     */
    final public static function renderDcBreadcrumb(int $items_id): string
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $breadcrumb = [];

        $item = new static();
        if ($item->getFromDB($items_id)) {
            $types = $CFG_GLPI['rackable_types'];

            if ($item instanceof PDU) {
                $pdu_rack = new PDU_Rack();
                $rack = new Rack();
                if (
                    $pdu_rack->getFromDBByCrit(['pdus_id'  => $item->getID()])
                    && $rack->getFromDB($pdu_rack->fields['racks_id'])
                ) {
                    $breadcrumb[Rack::getType()] = [
                        'link'     => $rack->getLink(
                            [
                                'linkoption' => $rack->isDeleted() ? 'class="target-deleted"' : '',
                                'icon'       => true
                            ]
                        ),
                        'position' => $pdu_rack->fields['position'],
                        'side'     => $pdu_rack->fields['side'],
                    ];

                    $item = $rack;
                }
            }

            // Add Enclosure part of breadcrumb
            $enclosure_types = $types;
            unset($enclosure_types[array_search('Enclosure', $enclosure_types)]);
            if (in_array($item->getType(), $enclosure_types) && $enclosure = $item->getParentEnclosure()) {
                $location = Location::getFromItem($enclosure) ?: null;
                $breadcrumb[Enclosure::getType()] = [
                    'link'     => $enclosure->getLink(
                        [
                            'linkoption' => $enclosure->isDeleted() ? 'class="target-deleted"' : '',
                            'icon'       => true
                        ]
                    ),
                    'position' => $item->getPositionInEnclosure(),
                    'location' => $location !== null ? $location->fields : null,
                ];

                $item = $enclosure; // use Enclosure for previous breadcrumb item
            }

            // Add Rack part of breadcrumb
            if (in_array($item->getType(), $types) && $rack = $item->getParentRack()) {
                $location = Location::getFromItem($rack) ?: null;
                $breadcrumb[Rack::getType()] = [
                    'link'     => $rack->getLink(
                        [
                            'linkoption' => $rack->isDeleted() ? 'class="target-deleted"' : '',
                            'icon'       => true
                        ]
                    ),
                    'position' => $item->getPositionInRack(),
                    'location' => $location !== null ? $location->fields : null,
                ];

                $item = $rack; // use Rack for previous breadcrumb item
            }

            // Add DCRoom part of breadcrumb
            $dcroom = new DCRoom();
            if (
                $item->getType() == Rack::getType()
                && $item->fields['dcrooms_id'] > 0
                && $dcroom->getFromDB($item->fields['dcrooms_id'])
            ) {
                $location = Location::getFromItem($dcroom) ?: null;
                $breadcrumb[DCRoom::getType()] = [
                    'link'     => $dcroom->getLink(
                        [
                            'linkoption' => $dcroom->isDeleted() ? 'class="target-deleted"' : '',
                            'icon'       => true
                        ]
                    ),
                    'location' => $location !== null ? $location->fields : null,
                ];

                $item = $dcroom; // use DCRoom for previous breadcrumb item
            }

            // Add Datacenter part of breadcrumb
            $datacenter = new Datacenter();
            if (
                $item->getType() == DCRoom::getType()
                && $item->fields['datacenters_id'] > 0
                && $datacenter->getFromDB($item->fields['datacenters_id'])
            ) {
                $location = Location::getFromItem($datacenter) ?: null;
                $breadcrumb[Datacenter::getType()] = [
                    'link'     => $datacenter->getLink(
                        [
                            'linkoption' => $datacenter->isDeleted() ? 'class="target-deleted"' : '',
                            'icon'       => true
                        ]
                    ),
                    'location' => $location !== null ? $location->fields : null,
                ];
            }

            $breadcrumb = array_reverse($breadcrumb);
        }

        return TemplateRenderer::getInstance()->render(
            'layout/parts/dcbreadcrumbs.html.twig',
            [
                'breadcrumbs'   => $breadcrumb
            ]
        );
    }

    /**
     * Get parent Enclosure.
     *
     * @return Enclosure|null
     */
    final public function getParentEnclosure(): ?Enclosure
    {
        $ien = new Item_Enclosure();
        if (
            !($this instanceof CommonDBTM)
            || !$ien->getFromDBByCrit(['itemtype' => $this->getType(), 'items_id' => $this->getID()])
        ) {
            return null;
        }

        $enclosure = new Enclosure();
        return $enclosure->getFromDB($ien->fields['enclosures_id']) ? $enclosure : null;
    }

    /**
     * Get position in Enclosure.
     *
     * @return int|null
     */
    final public function getPositionInEnclosure(): ?int
    {
        $ien = new Item_Enclosure();
        if (
            !($this instanceof CommonDBTM)
            || !$ien->getFromDBByCrit(['itemtype' => $this->getType(), 'items_id' => $this->getID()])
        ) {
            return null;
        }

        return $ien->fields['position'];
    }

    /**
     * Get parent Rack.
     *
     * @return Rack|null
     */
    final public function getParentRack(): ?Rack
    {
        $ira = new Item_Rack();
        if (
            !($this instanceof CommonDBTM)
            || !$ira->getFromDBByCrit(['itemtype' => $this->getType(), 'items_id' => $this->getID()])
        ) {
            return null;
        }

        $rack = new Rack();
        return $rack->getFromDB($ira->fields['racks_id']) ? $rack : null;
    }

    /**
     * Get position in Rack.
     *
     * @return int|null
     */
    final public function getPositionInRack(): ?int
    {
        $ira = new Item_Rack();
        if (
            !($this instanceof CommonDBTM)
            || !$ira->getFromDBByCrit(['itemtype' => $this->getType(), 'items_id' => $this->getID()])
        ) {
            return null;
        }

        return $ira->fields['position'];
    }
}
