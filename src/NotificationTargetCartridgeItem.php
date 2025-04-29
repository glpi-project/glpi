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

/**
 * NotificationTargetCartridgeItem Class
 *
 * @since 0.84
 **/
class NotificationTargetCartridgeItem extends NotificationTarget
{
    public function getEvents()
    {
        return ['alert' => __('Cartridges alarm')];
    }


    public function addDataForTemplate($event, $options = [])
    {

        $events = $this->getAllEvents();

        $this->data['##cartridge.entity##'] = Dropdown::getDropdownName(
            'glpi_entities',
            $options['entities_id']
        );
        $this->data['##cartridge.action##'] = $events[$event];

        foreach ($options['items'] as $id => $cartridge) {
            $remaining_stock = Cartridge::getUnusedNumber($id);
            $target_stock = Cartridge::getStockTarget($id);
            if ($target_stock <= 0) {
                $alarm_threshold = Cartridge::getAlarmThreshold($id);
                $target_stock = $alarm_threshold + 1;
            }
            $to_order = $target_stock - $remaining_stock;
            $tmp                            = [];
            $tmp['##cartridge.item##']      = $cartridge['name'];
            $tmp['##cartridge.reference##'] = $cartridge['ref'];
            $tmp['##cartridge.remaining##'] = $remaining_stock;
            $tmp['##cartridge.stock_target##'] = $target_stock;
            $tmp['##cartridge.to_order##'] = $to_order;
            $tmp['##cartridge.url##']       = $this->formatURL(
                $options['additionnaloption']['usertype'],
                "CartridgeItem_" . $id
            );

            $this->data['cartridges'][] = $tmp;
        }

        $this->getTags();
        foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
            if (!isset($this->data[$tag])) {
                $this->data[$tag] = $values['label'];
            }
        }
    }


    public function getTags()
    {

        $tags = [
            'cartridge.action'         => _n('Event', 'Events', 1),
            'cartridge.reference'      => __('Reference'),
            'cartridge.item'           => CartridgeItem::getTypeName(1),
            'cartridge.remaining'      => __('Remaining'),
            'cartridge.stock_target'   => __('Stock target'),
            'cartridge.to_order'       => __('To order'),
            'cartridge.url'            => __('URL'),
            'cartridge.entity'         => Entity::getTypeName(1),
        ];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'   => $tag,
                'label' => $label,
                'value' => true,
            ]);
        }

        $this->addTagToList(['tag'     => 'cartridges',
            'label'   => __('Device list'),
            'value'   => false,
            'foreach' => true,
        ]);

        asort($this->tag_descriptions);
    }
}
