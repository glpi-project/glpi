<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}


/**
 * NotificationTargetConsumableItem Class
 *
 * @since 0.84
**/
class NotificationTargetConsumableItem extends NotificationTarget {


   function getEvents() {
      return ['alert' => __('Consumables alarm')];
   }


   function addDataForTemplate($event, $options = []) {

      $events                                    = $this->getAllEvents();

      $this->data['##consumable.entity##']      = Dropdown::getDropdownName('glpi_entities',
                                                                             $options['entities_id']);
      $this->data['##lang.consumable.entity##'] = __('Entity');
      $this->data['##consumable.action##']      = $events[$event];

      foreach ($options['items'] as $id => $consumable) {
         $tmp                             = [];
         $tmp['##consumable.item##']      = $consumable['name'];
         $tmp['##consumable.reference##'] = $consumable['ref'];
         $tmp['##consumable.remaining##'] = Consumable::getUnusedNumber($id);
         $tmp['##consumable.url##']       = $this->formatURL($options['additionnaloption']['usertype'],
                                                             "ConsumableItem_".$id);
         $this->data['consumables'][] = $tmp;
      }

      $this->getTags();
      foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
         if (!isset($this->data[$tag])) {
            $this->data[$tag] = $values['label'];
         }
      }
   }


   function getTags() {

      $tags = ['consumable.action'    => _n('Event', 'Events', 1),
                    'consumable.reference' => __('Reference'),
                    'consumable.item'      => __('Consumable model'),
                    'consumable.remaining' => __('Remaining'),
                    'consumable.entity'    => __('Entity')];

      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'   => $tag,
                                   'label' => $label,
                                   'value' => true]);
      }

      $this->addTagToList(['tag'     => 'consumables',
                                'label'   => __('Device list'),
                                'value'   => false,
                                'foreach' => true]);

      asort($this->tag_descriptions);
   }

}
