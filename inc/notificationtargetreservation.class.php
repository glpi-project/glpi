<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

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
   die("Sorry. You can't access this file directly");
}


/**
 * NotificationTargetReservation Class
**/
class NotificationTargetReservation extends NotificationTarget {


   function getEvents() {

      return array('new'    => __('New reservation'),
                   'update' => __('Update of a reservation'),
                   'delete' => __('Deletion of a reservation'),
                   'alert'  => __('Reservation expired'));
   }


   /**
    * @see NotificationTarget::getAdditionalTargets()
   **/
   function getAdditionalTargets($event='') {

      if ($event != 'alert') {
         $this->addTarget(Notification::ITEM_TECH_IN_CHARGE,
                          __('Technician in charge of the hardware'));
         $this->addTarget(Notification::ITEM_TECH_GROUP_IN_CHARGE,
                          __('Group in charge of the hardware'));
         $this->addTarget(Notification::ITEM_USER, __('Hardware user'));
         $this->addTarget(Notification::AUTHOR, __('Requester'));
      }
      // else if ($event == 'alert') {
      //   $this->addTarget(Notification::ITEM_USER, __('User reserving equipment'));
      //}
   }


   /**
    * @see NotificationTarget::getDatasForTemplate()
   **/
   function getDatasForTemplate($event, $options=array()) {
      //----------- Reservation infos -------------- //
      $events                                  = $this->getAllEvents();

      $this->datas['##reservation.action##']   = $events[$event];

      if ($event != 'alert') {
         $this->datas['##reservation.user##']   = "";
         $user_tmp                              = new User();
         if ($user_tmp->getFromDB($this->obj->getField('users_id'))) {
            $this->datas['##reservation.user##'] = $user_tmp->getName();
         }
         $this->datas['##reservation.begin##']   = Html::convDateTime($this->obj->getField('begin'));
         $this->datas['##reservation.end##']     = Html::convDateTime($this->obj->getField('end'));
         $this->datas['##reservation.comment##'] = $this->obj->getField('comment');

         $reservationitem = new ReservationItem();
         $reservationitem->getFromDB($this->obj->getField('reservationitems_id'));
         $itemtype        = $reservationitem->getField('itemtype');

         if ($item = getItemForItemtype($itemtype)) {
            $item->getFromDB($reservationitem->getField('items_id'));
            $this->datas['##reservation.itemtype##']
                                 = $item->getTypeName(1);
            $this->datas['##reservation.item.name##']
                                 = $item->getField('name');
            $this->datas['##reservation.item.entity##']
                                 = Dropdown::getDropdownName('glpi_entities',
                                                             $item->getField('entities_id'));

            if ($item->isField('users_id_tech')) {
                $this->datas['##reservation.item.tech##']
                                 = Dropdown::getDropdownName('glpi_users',
                                                             $item->getField('users_id_tech'));
            }

            $this->datas['##reservation.itemurl##']
                                 = $this->formatURL($options['additionnaloption']['usertype'],
                                                    $itemtype."_".$item->getField('id'));

            $this->datas['##reservation.url##']
                                 = $this->formatURL($options['additionnaloption']['usertype'],
                                                    "Reservation_".$this->obj->getField('id'));
                     }

      } else {
         $this->datas['##reservation.entity##'] = Dropdown::getDropdownName('glpi_entities',
                                                                            $options['entities_id']);

         foreach ($options['items'] as $id => $item) {
            $tmp = array();
            if ($obj = getItemForItemtype($item['itemtype'])) {
               $tmp['##reservation.itemtype##']
                                    = $obj->getTypeName(1);
               $tmp['##reservation.item##']
                                    = $item['item_name'];
               $tmp['##reservation.expirationdate##']
                                    = Html::convDateTime($item['end']);
               $tmp['##reservation.url##']
                                    = $this->formatURL($options['additionnaloption']['usertype'],
                                                       "Reservation_".$id);
            }
            $this->datas['reservations'][] = $tmp;
         }
      }

      $this->getTags();
      foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
         if (!isset($this->datas[$tag])) {
            $this->datas[$tag] = $values['label'];
         }
      }
   }


   function getTags() {

      $tags_all = array('reservation.item'     => __('Associated item'),
                        'reservation.itemtype' => __('Item type'),
                        'reservation.url'      => __('URL'),
                        'reservation.itemurl'  => __('URL of item reserved'),
                        'reservation.action'   => _n('Event', 'Events', 1));

      foreach ($tags_all as $tag => $label) {
         $this->addTagToList(array('tag'   => $tag,
                                   'label' => $label,
                                   'value' => true));
      }

      $tags_except_alert = array('reservation.user'        => __('Writer'),
                                 'reservation.begin'       => __('Start date'),
                                 'reservation.end'         => __('End date'),
                                 'reservation.comment'     => __('Comments'),
                                 'reservation.item.entity' => __('Entity'),
                                 'reservation.item.name'   => __('Associated item'),
                                 'reservation.item.tech'   => __('Technician in charge of the hardware'));

      foreach ($tags_except_alert as $tag => $label) {
         $this->addTagToList(array('tag'    => $tag,
                                   'label'  => $label,
                                   'value'  => true,
                                   'events' => array('new', 'update', 'delete')));
      }

      $this->addTagToList(array('tag'     => 'items',
                                'label'   => __('Device list'),
                                'value'   => false,
                                'foreach' => true,
                                'events'  => array('alert')));

      $tag_alert = array('reservation.expirationdate' => __('End date'),
                         'reservation.entity'         => __('Entity'));

      foreach ($tag_alert as $tag => $label) {
         $this->addTagToList(array('tag'    => $tag,
                                   'label'  => $label,
                                   'value'  => true,
                                   'events' => array('alert')));
      }

      asort($this->tag_descriptions);
   }


   /**
    * Get item associated with the object on which the event was raised
    *
    * @param $event  (default '')
    *
    * @return the object associated with the itemtype
   **/
   function getObjectItem($event='') {

      if ($this->obj) {
         $ri = new ReservationItem();

         if ($ri->getFromDB($this->obj->getField('reservationitems_id'))) {
            $itemtype = $ri->getField('itemtype');

            if (($itemtype != NOT_AVAILABLE) && ($itemtype != '')
                && ($item = getItemForItemtype($itemtype))) {
               $item->getFromDB($ri->getField('items_id'));
               $this->target_object[] = $item;
            }
         }
      }
   }


}
?>
