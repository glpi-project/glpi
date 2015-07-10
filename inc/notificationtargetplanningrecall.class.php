<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.
 
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}


/**
 * NotificationTargetPlanningRecall Class
 *
 * @since version 0.84
**/
class NotificationTargetPlanningRecall extends NotificationTarget {


   function getEvents() {
      return array('planningrecall' => __('Planning recall'));
   }


   /**
    * @see NotificationTarget::getNotificationTargets()
   **/
   function getNotificationTargets($entity) {
      $this->addTarget(Notification::AUTHOR, __('Requester'));
   }


   /**
    * @see NotificationTarget::getDatasForTemplate()
   **/
   function getDatasForTemplate($event, $options=array()) {

      $events                             = $this->getAllEvents();
      
      $this->target_object = reset($this->target_object);
      
      $this->datas['##recall.action##']   = $events[$event];
      $this->datas['##recall.itemtype##'] = $this->target_object->getTypeName(1);
      $this->datas['##recall.item.URL##'] = '';
      // For task show parent link
      if (($this->target_object instanceof CommonDBChild)
          || ($this->target_object instanceof CommonITILTask)) {

         $item2   = $this->target_object->getItem();
         $this->datas['##recall.item.url##']
                  = $this->formatURL($options['additionnaloption']['usertype'],
                                     $item2->getType()."_".$item2->getID());

      } else {
         $this->datas['##recall.item.url##']
                  = $this->formatURL($options['additionnaloption']['usertype'],
                                     $this->target_object->getType().
                                          "_".$this->target_object->getID());
      }
      $this->datas['##recall.item.name##'] = '';

      if ($this->target_object->isField('name')) {
         $this->datas['##recall.item.name##'] = $this->target_object->getField('name');
      } else {
         if (($item2 = $this->target_object->getItem())
             && $item2->isField('name')) {
            $this->datas['##recall.item.name##'] = $item2->getField('name');
         }
      }

      $this->datas['##recall.item.content##'] = '';
      if ($this->target_object->isField('content')) {
         $this->datas['##recall.item.content##'] = $this->target_object->getField('content');
      }
      if ($this->target_object->isField('text')) {
         $this->datas['##recall.item.content##'] = $this->target_object->getField('text');
      }
      $this->datas['##recall.item.private##'] = '';
      if ($this->target_object->isField('is_private')) {
         $this->datas['##recall.item.private##']
                     = Dropdown::getYesNo($this->target_object->getField('is_private'));
      }

      $this->datas['##recall.item.date_mod##'] = '';
      if ($this->target_object->isField('date_mod')) {
         $this->datas['##recall.item.date_mod##']
                     = Html::convDateTime($this->target_object->getField('date_mod'));
      }


      $this->datas['##recall.item.user##'] = '';
      $user_tmp                            = new User();
      if ($user_tmp->getFromDB($this->target_object->getField('users_id'))) {
         $this->datas['##recall.item.user##'] = $user_tmp->getName();
      }

      $this->datas['##recall.planning.state##'] = '';
      if ($this->target_object->isField('state')) {
         $this->datas['##recall.planning.state##']
                     = Planning::getState($this->target_object->getField('state'));
      }

      $this->datas['##recall.planning.begin##']
                  = Html::convDateTime($this->target_object->getField('begin'));
      $this->datas['##recall.planning.end##']
                  = Html::convDateTime($this->target_object->getField('end'));

      $this->getTags();
      foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
         if (!isset($this->datas[$tag])) {
            $this->datas[$tag] = $values['label'];
         }
      }
   }


   function getTags() {

      $tags_all = array('recall.action'            => _n('Event', 'Events', 1),
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
                        );

      foreach ($tags_all as $tag => $label) {
         $this->addTagToList(array('tag'   => $tag,
                                   'label' => $label,
                                   'value' => true));
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
   function getObjectItem($event='') {

      if ($this->obj) {
         if (($item = getItemForItemtype($this->obj->getField('itemtype')))
             && $item->getFromDB($this->obj->getField('items_id'))) {
            $this->target_object[] = $item;
         }
      }
   }

}
?>