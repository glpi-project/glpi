<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

/**
 * Datacenter breadcrumb
 **/
trait DCBreadcrumb
{
    /** @see DCBreadcrumbInterface::renderDcBreadcrumb() */
    final public static function renderDcBreadcrumb(int $items_id): string
    {
        global $CFG_GLPI;

        $breadcrumb = [];

        $item = new static();
        if ($item->getFromDB($items_id)) {
            $types = $CFG_GLPI['rackable_types'];

            // TODO: avoid instanceof in traits
            if ($item instanceof PDU) { // @phpstan-ignore instanceof.alwaysTrue, instanceof.alwaysFalse
                $pdu_rack = new PDU_Rack();
                $rack = new Rack();
                if (
                    $pdu_rack->getFromDBByCrit(['pdus_id'  => $item->getID()])
                    && $rack->getFromDB($pdu_rack->fields['racks_id'])
                ) {
                    $location = Location::getFromItem($rack) ?: null;
                    $breadcrumb[Rack::getType()] = [
                        'link'     => $rack->getLink(
                            [
                                'class' => $rack->isDeleted() ? 'target-deleted' : '',
                                'icon'  => true,
                            ]
                        ),
                        'position' => $pdu_rack->fields['position'],
                        'side'     => $pdu_rack->fields['side'],
                        'location' => $location?->fields,
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
                            'class' => $enclosure->isDeleted() ? 'target-deleted' : '',
                            'icon'  => true,
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
                            'class' => $rack->isDeleted() ? 'target-deleted' : '',
                            'icon'  => true,
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
                            'class' => $dcroom->isDeleted() ? 'target-deleted' : '',
                            'icon'  => true,
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
                            'class' => $datacenter->isDeleted() ? 'target-deleted' : '',
                            'icon'  => true,
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
                'breadcrumbs'   => $breadcrumb,
            ]
        );
    }

    /** @see DCBreadcrumbInterface::getParentEnclosure() */
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

    /** @see DCBreadcrumbInterface::getPositionInEnclosure() */
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

    /** @see DCBreadcrumbInterface::getParentRack() */
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

    /** @see DCBreadcrumbInterface::getPositionInRack() */
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
