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

/**
* @since 9.2
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}


/**
 * NotificationTargetSoftwareLicense Class
**/
class NotificationTargetCertificate extends NotificationTarget {


   function getEvents() {
      return ['alert' => __('Alarms on expired certificates')];
   }


   function addDataForTemplate($event, $options = []) {

      $events = $this->getAllEvents();

      //These 2 params should be defined in $options table
      //The only case where they're not defined in when displaying
      //the debug tab of a certificate
      if (!isset($options['certificates'])) {
         $options['certificates'] = [];
      }
      if (!isset($options['entities_id'])) {
         $options['entities_id'] = $options['item']->fields['entities_id'];
      }

      $this->data['##certificate.action##'] = $events[$event];
      $this->data['##certificate.entity##'] = Dropdown::getDropdownName('glpi_entities',
                                                                        $options['entities_id']);

      foreach ($options['certificates'] as $id => $certificate) {
         $this->data['certificates'][] = [
            '##certificate.name##'           => $certificate['name'],
            '##certificate.serial##'         => $certificate['serial'],
            '##certificate.expirationdate##' => Html::convDate($certificate["date_expiration"]),
            '##certificate.url##'            => $this->formatURL($options['additionnaloption']['usertype'],
                                                                 "Certificate_".$id),
         ];
      }

      $this->getTags();
      foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
         if (!isset($this->data[$tag])) {
            $this->data[$tag] = $values['label'];
         }
      }
   }


   function getTags() {

      $tags = ['certificate.expirationdate' => __('Expiration date'),
               'certificate.name'           => __('Name'),
               'certificate.serial'         => __('Serial number'),
               'certificate.url'            => __('URL'),
               'certificate.entity'         => __('Entity'),
               ];

      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'   => $tag,
                              'label' => $label,
                              'value' => true]);
      }

      $this->addTagToList(['tag'     => 'certificates',
                           'label'   => __('Device list'),
                           'value'   => false,
                           'foreach' => true]);

      asort($this->tag_descriptions);
   }

}
