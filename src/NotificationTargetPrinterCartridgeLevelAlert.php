<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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
 * NotificationTargetPrinterCartridgeLevelAlert Class
 *
 **/
class NotificationTargetPrinterCartridgeLevelAlert extends NotificationTarget
{
    public function getEvents()
    {
        return ['alert' => __('Printer cartridge level alarm')];
    }

    public function addDataForTemplate($event, $options = [])
    {
        $events = $this->getAllEvents();
        $this->data['##cartridge.action##'] = $events[$event];
        $this->data['##cartridge.entity##'] = Dropdown::getDropdownName("glpi_entities", $options["entities_id"]);

        foreach ($options['items'] as $cartridge_id => $item) {
            $tmp  = [];
            foreach (PrinterCartridgeLevelAlert::prepareBodyValues($item) as $id => $value) {
                $tmp['##' . $id . '##']      = $value;
            }
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
            'cartridge.printer'        => Printer::getTypeName(Session::getPluralNumber()),
            'cartridge.entity'         => Entity::getTypeName(1),
            'cartridge.item'           => Cartridge::getTypeName(1),
            'cartridge.level'          => __('Level')
        ];
        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'   => $tag,
                'label' => $label,
                'value' => true
            ]);
        }
        $this->addTagToList(['tag' => 'cartridges',
            'label'   => __('Device list'),
            'value'   => false,
            'foreach' => true
        ]);
        asort($this->tag_descriptions);
    }
}
