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
 * NotificationTargetITILEvent Class
 *
 * @since 10.0.0
**/
class NotificationTargetITILEvent extends NotificationTarget {

   /**
    * Get events related to tickets
   **/
   function getEvents() {
      return ['new' => __('New event')];
   }


   function addDataForTemplate($event, $options = []) {

      $events                                   = $this->getAllEvents();

      $this->data['##event.entity##']           = Dropdown::getDropdownName('glpi_entities',
                                                                        $options['entities_id']);
      $this->data['##event.action##']           = $options[$event];
      $this->data['##event.name##']             = $options['name'];
      $this->data['##event.content##']          = $options['content'];
      $this->data['##event.category##']         = ITILEventCategory::getCategoryName($options['itileventcategories_id'], true);
      $this->data['##event.significance##']     = ITILEvent::getStatusName($options['significance']);
      $this->data['##event.significance##']     = ITILEvent::getSignificanceName($options['significance']);
      $this->data['##event.correlation_uuid##'] = $options['correlation_uuid'];

      $this->getTags();
      foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
         if (!isset($this->data[$tag])) {
            $this->data[$tag] = $values['label'];
         }
      }
   }


   function getTags() {

      $tags = [
         'event.action'             => _n('Event', 'Events', 1),
         'event.name'               => __('Name'),
         'event.content'            => __('Content'),
         'event.category'           => __('Category'),
         'event.entity'             => __('Entity'),
         'event.status'             => __('Status'),
         'event.significance'       => __('Significance'),
         'event.correlation_uuid'   => __('Correlation UUID')];

      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'   => $tag,
                                   'label' => $label,
                                   'value' => true]);
      }

      asort($this->tag_descriptions);
   }

}