<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

// Class NotificationTarget
class NotificationTargetReservation extends NotificationTarget {

   /**
    * Get users emails by profile
    * @param $profiles_id the profile ID to get users emails
    *
    * @return nothing
    */
   function getUsersAddressesByProfile($profiles_id) {
      global $DB;

      $query = $this->getDistinctUserSql().", glpi_profiles_users.entities_id AS entity
               FROM `glpi_profiles_users`".
               $this->getJoinProfileSql()."
               INNER JOIN `glpi_users` ON (`glpi_profiles_users`.`users_id` = `glpi_users`.`id`)
               WHERE `glpi_profiles_users`.`profiles_id` = '".$profiles_id."'".
                     getEntitiesRestrictRequest("AND","glpi_profiles_users","entities_id",
                                                $this->target_object->getEntityID(),true);

      foreach ($DB->request($query) as $data) {
         $this->addToAddressesList($data);
      }
   }
   /**
    * Get item associated with the object on which the event was raised
    * @return the object associated with the itemtype
    */
   function getObjectItem($event='') {
      if ($event != 'alert') {
         $ri = new ReservationItem;
         if ($ri->getFromDB($this->obj->getField('reservationitems_id'))) {
            $itemtype = $ri->getField('itemtype');
            $item = new  $itemtype ();
            $item->getFromDB($ri->getField('items_id'));
            $this->target_object = $item;
         }
      }
      else {
         $this->target_object = $this->obj;
      }
   }

   function getEvents() {
      global $LANG;

      return array ('new'    => $LANG['mailing'][19],
                    'update' => $LANG['mailing'][23],
                    'delete' => $LANG['mailing'][29],
                    'alert' => $LANG['mailing'][136]);
   }


   function getAdditionalTargets($event='') {
      global $LANG;
      if ($event != 'alert') {
         $this->addTarget(Notification::ITEM_TECH_IN_CHARGE,$LANG['common'][10]);
         $this->addTarget(Notification::ITEM_USER,$LANG['mailing'][137]);
         $this->addTarget(Notification::AUTHOR,$LANG['job'][4]);
      }
   }


   function getDatasForTemplate($event, $options=array()) {
      global $LANG,$CFG_GLPI;

      //----------- Reservation infos -------------- //

      $events = $this->getAllEvents();

      $this->datas['##reservation.action##'] = $events[$event];

      if ($event != 'alert') {
         $this->datas['##reservation.user##']   = Dropdown::getDropdownName('glpi_users',
                                                                     $this->obj->getField('users_id'));
         $this->datas['##reservation.begin##']  = convDateTime($this->obj->getField('begin'));
         $this->datas['##reservation.end##']    = convDateTime($this->obj->getField('end'));
         $this->datas['##reservation.comment##']     = $this->obj->getField('comment');

         $reservationitem = new ReservationItem;
         $reservationitem->getFromDB($this->obj->getField('reservationitems_id'));
         $itemtype = $reservationitem->getField('itemtype');
         if (class_exists($itemtype)) {
            $item = new $itemtype();
            $item->getFromDB($reservationitem->getField('items_id'));
            $this->datas['##reservation.itemtype##']    = $item->getTypeName();
            $this->datas['##reservation.item.name##']   = $item->getField('name');
            $this->datas['##reservation.item.entity##'] = Dropdown::getDropdownName('glpi_entities',
                                                                       $item->getField('entities_id'));
            if ($item->isField('users_id_tech')) {
                $this->datas['##reservation.item.tech##'] = Dropdown::getDropdownName('glpi_users',
                                                                     $item->getField('users_id_tech'));
            }
            $tmp['##reservation.url##'] = urldecode($CFG_GLPI["url_base"].
                                       "/index.php?redirect=".strtolower($itemtype)."_".
                                                      $reservationitem->getField('id'));
         }
      }
      else {
         $this->datas['##reservation.entity##'] = Dropdown::getDropdownName('glpi_entities',
                                                                            $options['entities_id']);

         foreach ($options['items'] as $id => $item) {
            $tmp = array();
            $obj = new $item['itemtype'] ();
            $tmp['##reservation.itemtype##'] = $obj->getTypeName();
            $tmp['##reservation.item##']     = $item['item_name'];
            $tmp['##reservation.expirationdate##'] = convDateTime($item['end']);
            $tmp['##reservation.url##'] = urldecode($CFG_GLPI["url_base"].
                                                 "/index.php?redirect=reservation_".$id);
            $this->datas['reservations'][] = $tmp;
         }
      }

      $this->getTags();
      foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
         $this->datas[$tag] = $values['label'];
      }
   }

   function getTags() {
      global $LANG;

      $tags_all = array('reservation.item'        => $LANG['financial'][104],
                    'reservation.itemtype'    => $LANG['reports'][12],
                    'reservation.url'         => 'URL');

      foreach ($tags_all as $tag => $label) {
         $this->addTagToList(array('tag'=>$tag,'label'=>$label,
                                   'value'=>true));
      }

      $tags_except_alert = array('reservation.user'        => $LANG['common'][37],
                    'reservation.begin'       => $LANG['search'][8],
                    'reservation.end'         => $LANG['search'][9],
                    'reservation.comment'     => $LANG['common'][25],
                    'reservation.item.entity' => $LANG['entity'][0],
                    'reservation.entity'      => $LANG['entity'][0],
                    'reservation.item.name'   => $LANG['financial'][104],
                    'reservation.item.tech'   => $LANG['common'][10]);

      foreach ($tags_except_alert as $tag => $label) {
         $this->addTagToList(array('tag'=>$tag,'label'=>$label,
                                   'value'=>true,'events'=>array('new','update','delete')));
      }

      $this->addTagToList(array('tag'=>'items','label'=>$LANG['reports'][57],
                                'value'=>false,'foreach'=>true,
                                'events'=>array('alert')));

      $tag_alert= array('reservation.expirationdate'         => $LANG['search'][9]);
      foreach ($tag_alert as $tag => $label) {
         $this->addTagToList(array('tag'=>$tag,'label'=>$label,
                                   'value'=>true,'events'=>array('alert')));
      }

      asort($this->tag_descriptions);
   }
}
?>