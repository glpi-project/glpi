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

class NotificationTargetSavedsearch_Alert extends NotificationTarget {


   function getEvents() {
      global $DB;

      $events = [];

      $iterator = $DB->request([
         'SELECT DISTINCT' => 'event',
         'FROM'            => Notification::getTable(),
         'WHERE'           => ['itemtype' => SavedSearch_Alert::getType()]
      ]);

      if ($iterator->numRows()) {
         while ($row = $iterator->next()) {
            if (strpos($row['event'], 'alert_') !== false) {
               $search = new SavedSearch();
               $search->getFromDB(str_replace('alert_', '', $row['event']));
               $events[$row['event']] = sprintf(
                  __('Search  alert for "%1$s" (%2$s)'),
                  $search->getName(),
                  $search->getID()
               );
            } else {
               $events[$row['event']] = __('Private search alert');
            }
         }
      } else {
         $events['alert'] = __('Private search alert');
      }

      return $events;
   }


   function addDataForTemplate($event, $options = []) {
      global $CFG_GLPI;

      $events = $this->getEvents();

      $savedsearch_alert = $options['item'];
      $savedsearch = $options['savedsearch'];

      $this->data['##savedsearch.action##']    = $events[$event];
      $this->data['##savedsearch.name##']      = $savedsearch->getField('name');
      $this->data['##savedsearch.message##']   = $options['msg'];
      $this->data['##savedsearch.id##']        = $savedsearch->getID();
      $this->data['##savedsearch.count##']     = (int)$options['data']['totalcount'];
      $this->data['##savedsearch.type##']      = $savedsearch->getField('itemtype');
      $this->data['##savedsearch.url##']       = $CFG_GLPI['url_base']."/?redirect=" .
                                                   rawurlencode($savedsearch->getSearchURL(false) .
                                                   "?action=load&id=". $savedsearch->getID());

      $this->getTags();
      foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
         if (!isset($this->data[$tag])) {
            $this->data[$tag] = $values['label'];
         }
      }
   }


   function getTags() {
      $tags = [
         'savedsearch.action' => _n('Event', 'Events', 1),
         'savedsearch.name'   => __('Name'),
         'savedsearch.message'=> __('Message'),
         'savedsearch.id'     => __('ID'),
         'savedsearch.count'  => __('Number of results'),
         'savedsearch.type'   => __('Item type'),
         'savedsearch.url'    => __('Load saved search')
      ];

      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'   => $tag,
                                   'label' => $label,
                                   'value' => true]);
      }
      asort($this->tag_descriptions);
   }


   function addNotificationTargets($entity) {
      if ($this->raiseevent == 'alert') {
         $this->addTarget(Notification::USER, __('User'));
      } else {
         parent::addNotificationTargets($entity);
      }
   }


   function getSpecificTargets($data, $options) {
      //Look for all targets whose type is Notification::ITEM_USER
      switch ($data['type']) {
         case Notification::USER_TYPE :
            switch ($data['items_id']) {
               case Notification::USER :
                  $usertype = self::GLPI_USER;
                  $user = new User();
                  $savedsearch = new SavedSearch();
                  $savedsearch->getFromDB($this->obj->getField('savedsearches_id'));
                  $user->getFromDB($savedsearch->getField('users_id'));
                  // Send to user without any check on profile / entity
                  // Do not set users_id
                  $data = ['name'     => $user->getName(),
                                'email'    => $user->getDefaultEmail(),
                                'language' => $user->getField('language'),
                                'users_id' => $user->getID(),
                                'usertype' => $usertype];
                  $this->addToRecipientsList($data);
            }
      }
   }
}
