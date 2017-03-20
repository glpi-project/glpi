<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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
         'SELECT DISTINCT' => ['event'],
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


   function getDatasForTemplate($event, $options=array()) {
      global $CFG_GLPI;

      $events = $this->getEvents();

      $savedsearch_alert = $options['item'];
      $savedsearch = $options['savedsearch'];

      $this->datas['##savedsearch.action##']    = $events[$event];
      $this->datas['##savedsearch.name##']      = $savedsearch->getField('name');
      $this->datas['##savedsearch.message##']   = $options['msg'];
      $this->datas['##savedsearch.id##']        = $savedsearch->getID();
      $this->datas['##savedsearch.count##']     = (int)$options['data']['totalcount'];
      $this->datas['##savedsearch.type##']      = $savedsearch->getField('itemtype');
      $this->datas['##savedsearch.url##']       = $CFG_GLPI['url_base']."/?redirect=" .
                                                   rawurlencode($savedsearch->getSearchURL(false) .
                                                   "?action=load&id=". $savedsearch->getID());

      $this->getTags();
      foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
         if (!isset($this->datas[$tag])) {
            $this->datas[$tag] = $values['label'];
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
         $this->addTagToList(array('tag'   => $tag,
                                   'label' => $label,
                                   'value' => true));
      }
      asort($this->tag_descriptions);
   }


   function getNotificationTargets($entity) {
      if ($this->raiseevent == 'alert') {
         $this->addTarget(Notification::USER, __('User'));
      } else {
         parent::getNotificationTargets($entity);
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
                  $data = array('name'     => $user->getName(),
                                'email'    => $user->getDefaultEmail(),
                                'language' => $user->getField('language'),
                                'usertype' => $usertype);
                  $this->addToAddressesList($data);
            }
      }
   }
}
