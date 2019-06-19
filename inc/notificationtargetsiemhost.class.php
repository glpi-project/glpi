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
 * NotificationTargetSIEMHost Class
 * @since 10.0.0
**/
class NotificationTargetSIEMHost extends NotificationTarget {


   function getEvents() {
      return [
         'recovery_soft'      => __('Soft recovery'),
         'recovery_hard'      => __('Hard recovery'),
         'problem_soft'       => __('Soft problem'),
         'problem_hard'       => __('Hard problem'),
         'flapping_start'     => __('Flapping started'),
         'flapping_stop'      => __('Flapping stopped'),
         'flapping_disable'   => __('Flapping disabled')
      ];
   }


   function addDataForTemplate($event, $options = []) {

      $events = $this->getAllEvents();
      $host = new SIEMHost();
      $host->getFromDB($options['id']);
      $service = $host->getAvailabilityService();

      $this->data['##siemhost.action##'] = $events[$event];
      $this->data['##siemhost.name##'] = $host->getHostName();
      // TODO Finish

      $this->getTags();
      foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
         if (!isset($this->data[$tag])) {
            $this->data[$tag] = $values['label'];
         }
      }
   }


   function getTags() {

      $tags = [
         'siemhost.name'                  => __('Name'),
         'siemhost.itemtype'              => __('Item type'),
         'siemhost.availabilityservice'   => __('Availability service'),
         'siemhost.status'                => __('Status'),
         'siemhost.is_flapping'           => __('Is flapping'),
         'siemhost.state_type'            => __('State type'),
         'siemhost.current_check'         => __('Current checkk'),
         'siemhost.max_check'             => __('Max checks'),
         'siemhost.flap_detection'        => __('Flap detection'),
         'siemhost.check_interval'        => __('Check interval'),
         'siemhost.check_mode'            => __('Check mode'),
         'siemhost.logger'                => __('Logger'),
         'siemhost.sensor'                => __('Sensor'),
      ];

      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'   => $tag,
                              'label' => $label,
                              'value' => true]);
      }

      asort($this->tag_descriptions);
   }

}