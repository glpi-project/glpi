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
 * NotificationTargetPlanningRecall Class
 *
 * @since 0.84
**/
class NotificationTargetPlanningRecall extends NotificationTarget {


   function getEvents() {
      return ['planningrecall' => __('Planning recall')];
   }


   /**
    * @see NotificationTarget::addNotificationTargets()
   **/
   function addNotificationTargets($entity) {
      $this->addTarget(Notification::AUTHOR, __('Requester'));
      $this->addTarget(Notification::TASK_ASSIGN_TECH, __('Technician in charge of the task'));
   }

   /**
    * @see NotificationTarget::addSpecificTargets()
   **/
   function addSpecificTargets($data, $options) {
      switch ($data['type']) {
         case Notification::USER_TYPE :
            switch ($data['items_id']) {
               //Send to the ITIL object followup author
               case Notification::TASK_ASSIGN_TECH :
                  $this->addTaskAssignUser($options);
                  break;
            }
         break;
      }
   }

   /**
    * Get tech related to the task
    *
    * @param $options array
   **/
   function addTaskAssignUser() {
      $item = new $this->obj->fields['itemtype'];
      if ($item->getFromDB($this->obj->fields['items_id'])) {
         $user = new User();
         if ($item->isField('users_id_tech')
             && $user->getFromDB($item->getField('users_id_tech'))) {
            $this->addToRecipientsList(['language' => $user->getField('language'),
                                            'users_id' => $user->getField('id')]);
         }
      }
   }


   function addDataForTemplate($event, $options = []) {

      $events                             = $this->getAllEvents();
      $target_object                      = reset($this->target_object);;

      $this->data['##recall.action##']   = $events[$event];
      $this->data['##recall.itemtype##'] = $target_object->getTypeName(1);
      $this->data['##recall.item.URL##'] = '';
      // For task show parent link
      if (($target_object instanceof CommonDBChild)
          || ($target_object instanceof CommonITILTask)) {

         $item2   = $target_object->getItem();
         $this->data['##recall.item.url##']
                  = $this->formatURL($options['additionnaloption']['usertype'],
                                     $item2->getType()."_".$item2->getID());

      } else {
         $this->data['##recall.item.url##']
                  = $this->formatURL($options['additionnaloption']['usertype'],
                                     $target_object->getType().
                                          "_".$target_object->getID());
      }
      $this->data['##recall.item.name##'] = '';

      if ($target_object->isField('name')) {
         $this->data['##recall.item.name##'] = $target_object->getField('name');
      } else {
         if (($item2 = $target_object->getItem())
             && $item2->isField('name')) {
            $this->data['##recall.item.name##'] = $item2->getField('name');
         }
      }

      $this->data['##recall.item.content##'] = '';
      if ($target_object->isField('content')) {
         $this->data['##recall.item.content##'] = $target_object->getField('content');
      }
      if ($target_object->isField('text')) {
         $this->data['##recall.item.content##'] = $target_object->getField('text');
      }
      $this->data['##recall.item.private##'] = '';
      if ($target_object->isField('is_private')) {
         $this->data['##recall.item.private##']
                     = Dropdown::getYesNo($target_object->getField('is_private'));
      }

      $this->data['##recall.item.date_mod##'] = '';
      if ($target_object->isField('date_mod')) {
         $this->data['##recall.item.date_mod##']
                     = Html::convDateTime($target_object->getField('date_mod'));
      }

      $this->data['##recall.item.user##'] = '';
      $user_tmp                            = new User();
      if ($user_tmp->getFromDB($target_object->getField('users_id'))) {
         $this->data['##recall.item.user##'] = $user_tmp->getName();
      }

      $this->data['##recall.planning.state##'] = '';
      if ($target_object->isField('state')) {
         $this->data['##recall.planning.state##']
                     = Planning::getState($target_object->getField('state'));
      }

      $this->data['##recall.planning.begin##']
                  = Html::convDateTime($target_object->getField('begin'));
      $this->data['##recall.planning.end##']
                  = Html::convDateTime($target_object->getField('end'));

      $this->getTags();
      foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
         if (!isset($this->data[$tag])) {
            $this->data[$tag] = $values['label'];
         }
      }
   }


   function getTags() {

      $tags_all = ['recall.action'            => _n('Event', 'Events', 1),
                        'recall.item.user'         => __('Writer'),
                        'recall.item.date_mod'     => __('Last update'),
                        'recall.item.name'         => __('Title'),
                        'recall.item.private'      => __('Private'),
                        'recall.item.content'      => __('Description'),
                        'recall.item.url'          => __('URL'),
                        'recall.itemtype'          => __('Item type'),
                        'recall.planning.begin'    => __('Start date'),
                        'recall.planning.state'    => __('Status'),
                        'recall.planning.end'      => __('End date'),
                        ];

      foreach ($tags_all as $tag => $label) {
         $this->addTagToList(['tag'   => $tag,
                                   'label' => $label,
                                   'value' => true]);
      }

      asort($this->tag_descriptions);
   }


   /**
    * Get item associated with the object on which the event was raised
    *
    * @see NotificationTarget::getObjectItem()
    *
    * @param $event  (default '')
    *
    * @return the object associated with the itemtype
   **/
   function getObjectItem($event = '') {

      if ($this->obj) {
         if (($item = getItemForItemtype($this->obj->getField('itemtype')))
             && $item->getFromDB($this->obj->getField('items_id'))) {
            $this->target_object[] = $item;
         }
      }
   }

}
