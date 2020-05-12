<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
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

class NotificationTargetDomain extends NotificationTarget {

   public function getEvents() {
      return [
         'ExpiredDomains'     => __('Expired domains'),
         'DomainsWhichExpire' => __('Expiring domains')
      ];
   }

   function addAdditionalTargets($event = '') {
      $this->addTarget(
         Notification::ITEM_TECH_IN_CHARGE,
         __('Technician in charge of the domain')
      );
      $this->addTarget(
         Notification::ITEM_TECH_GROUP_IN_CHARGE,
         __('Group in charge of the domain')
      );
   }

   public function addDataForTemplate($event, $options = []) {

      $this->data['##domain.entity##']      = Dropdown::getDropdownName('glpi_entities', $options['entities_id']);
      $this->data['##lang.domain.entity##'] = __('Entity');
      $this->data['##domain.action##']      = ($event == "ExpiredDomains" ? __('Expired domains') : __('Expiring domains'));
      $this->data['##lang.domain.name##']           = __('Name');
      $this->data['##lang.domain.dateexpiration##'] = __('Expiration date');

      foreach ($options['domains'] as $domain) {
         $tmp = [
            '##domain.name##'             => $domain['name'],
            '##domain.dateexpiration##'   => Html::convDate($domain['date_expiration'])
         ];
         $this->data['domains'][] = $tmp;
      }
   }

   public function getTags() {
      $tags = [
         'domain.name'           => __('Name'),
         'domain.dateexpiration' => __('Expiration date')
      ];

      foreach ($tags as $tag => $label) {
         $this->addTagToList([
            'tag'   => $tag,
            'label' => $label,
            'value' => true
         ]);
      }

      $this->addTagToList([
         'tag'     => 'domains',
         'label'   => __('Expired or expiring domains'),
         'value'   => false,
         'foreach' => true,
         'events'  => ['DomainsWhichExpire', 'ExpiredDomains']
      ]);

      asort($this->tag_descriptions);
   }
}
