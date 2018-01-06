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
 * NotificationTargetSoftwareLicense Class
**/
class NotificationTargetSoftwareLicense extends NotificationTarget {


   function getEvents() {
      return ['alert' => __('Alarms on expired licenses')];
   }


   function addDataForTemplate($event, $options = []) {

      $events                            = $this->getAllEvents();

      $this->data['##license.action##'] = $events[$event];

      $this->data['##license.entity##'] = Dropdown::getDropdownName('glpi_entities',
                                                                     $options['entities_id']);

      foreach ($options['licenses'] as $id => $license) {
         $tmp                       = [];
         $tmp['##license.item##']   = $license['softname'];
         $tmp['##license.name##']   = $license['name'];
         $tmp['##license.serial##'] = $license['serial'];
         $tmp['##license.expirationdate##']
                                    = Html::convDate($license["expire"]);
         $tmp['##license.url##']    = $this->formatURL($options['additionnaloption']['usertype'],
                                                       "SoftwareLicense_".$id);
         $this->data['licenses'][] = $tmp;
      }

      $this->getTags();
      foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
         if (!isset($this->data[$tag])) {
            $this->data[$tag] = $values['label'];
         }
      }
   }


   function getTags() {

      $tags = ['license.expirationdate' => __('Expiration date'),
                    'license.item'           => _n('Software', 'Software', 1),
                    'license.name'           => __('Name'),
                    'license.serial'         => __('Serial number'),
                    'license.entity'         => __('Entity'),
                    'license.url'            => __('URL'),
                    'license.action'         => _n('Event', 'Events', 1)];

      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'   => $tag,
                                   'label' => $label,
                                   'value' => true]);
      }

      $this->addTagToList(['tag'     => 'licenses',
                                'label'   => __('Device list'),
                                'value'   => false,
                                'foreach' => true]);

      asort($this->tag_descriptions);
   }

}
