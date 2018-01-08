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
 * NotificationTargetMailCollector Class
 *
 * @since 0.85
**/
class NotificationTargetMailCollector extends NotificationTarget {


   function getEvents() {
      return ['error' => __('Receiver errors')];
   }


   function addDataForTemplate($event, $options = []) {

      $events                                  = $this->getEvents();
      $this->data['##mailcollector.action##'] = $events[$event];

      foreach ($options['items'] as $id => $mailcollector) {
         $tmp                             = [];
         $tmp['##mailcollector.name##']   = $mailcollector['name'];
         $tmp['##mailcollector.errors##'] = $mailcollector['errors'];
         $tmp['##mailcollector.url##']    = $this->formatURL($options['additionnaloption']['usertype'],
                                                             "MailCollector_".$id);
         $this->data['mailcollectors'][] = $tmp;
      }

      $this->getTags();
      foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
         if (!isset($this->data[$tag])) {
            $this->data[$tag] = $values['label'];
         }
      }
   }


   function getTags() {

      $tags = ['mailcollector.action' => _n('Event', 'Events', 1),
                    'mailcollector.name'   => __('Name'),
                    'mailcollector.errors' => __('Connection errors')];

      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'   => $tag,
                                   'label' => $label,
                                   'value' => true]);
      }

      $tags = ['mailcollector.url' => sprintf(__('%1$s: %2$s'), _n('Receiver', 'Receivers', 1),
                                                   __('URL'))];

      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'   => $tag,
                                   'label' => $label,
                                   'value' => true,
                                   'lang'  => false]);
      }

      //Foreach global tags
      $tags = ['mailcollectors' => _n('Receiver', 'Receivers', Session::getPluralNumber())];

      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'     => $tag,
                                   'label'   => $label,
                                   'value'   => false,
                                   'foreach' => true]);
      }

      asort($this->tag_descriptions);
      return $this->tag_descriptions;
   }

}
