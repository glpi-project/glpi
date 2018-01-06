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

// Class NotificationTarget
class NotificationTargetFieldUnicity extends NotificationTarget {


   function getEvents() {
      return ['refuse' => __('Alert on duplicate record')];
   }


   function addDataForTemplate($event, $options = []) {

      //User who tries to add or update an item in DB
      $action = ($options['action_user'] ?__('Add the item') :__('Update the item'));
      $this->data['##unicity.action_type##'] = $action;
      $this->data['##unicity.action_user##'] = $options['action_user'];
      $this->data['##unicity.date##']        = Html::convDateTime($options['date']);

      if ($item = getItemForItemtype($options['itemtype'])) {
         $this->data['##unicity.itemtype##'] = $item->getTypeName(1);
         $this->data['##unicity.message##']
                  = Html::clean($item->getUnicityErrorMessage($options['label'],
                                                              $options['field'],
                                                              $options['double']));
      }
      $this->data['##unicity.entity##']      = Dropdown::getDropdownName('glpi_entities',
                                                                          $options['entities_id']);
      if ($options['refuse']) {
         $this->data['##unicity.action##'] = __('Record into the database denied');
      } else {
         $this->data['##unicity.action##'] = __('Item successfully added but duplicate record on');
      }
      $this->getTags();
      foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
         if (!isset($this->data[$tag])) {
            $this->data[$tag] = $values['label'];
         }
      }
   }


   function getTags() {

      $tags = ['unicity.message'     => __('Message'),
                    'unicity.action_user' => __('Doer'),
                    'unicity.action_type' => __('Intended action'),
                    'unicity.date'        => __('Date'),
                    'unicity.itemtype'    => __('Type'),
                    'unicity.entity'      => __('Entity'),
                    'unicity.action'      => __('Alert on duplicate record')];

      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'   => $tag,
                                   'label' => $label,
                                   'value' => true]);
      }

      asort($this->tag_descriptions);
      return $this->tag_descriptions;
   }

}
