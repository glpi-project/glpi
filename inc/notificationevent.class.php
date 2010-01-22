<?php
/*
 * @version $Id: entitydata.class.php 10111 2010-01-12 09:03:26Z walid $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

/**
 * Class which manages notification events
 */
class NotificationEvent extends CommonDBTM {

   //Store values used in template
   var $values = array();

   static function dropdownEvents($itemtype,$value='') {
      $events = array();

      $target = NotificationTarget::getInstanceByType($itemtype);
      if ($target) {
         $events = $target->getEvents();
      }
      $events[''] = '-----';
      Dropdown::showFromArray('event',$events, array ('value'=>$value));
   }

   /**
    * Raise a notification event event
    * @param event the event raised for the itemtype
    * @param item the object which raised the event
    */
   static function raiseEvent($event,$item) {
      global $CFG_GLPI;

      //If notifications are enabled in GLPI's configuration
      if ($CFG_GLPI["use_mailing"]) {
         if ($item->isField('entities_id')) {
            $entity = $item->getField('entities_id');
         }
         else {
            //Objects don't necessary have an entities_id field (like DBConnection)
            $entity = 0;
         }

         $itemtype = $item->getType();
         //Store notifications infos & targets
         $notifications_infos = array();

         //Get all notifications by event, itemtype and entity
         foreach (Notification::getByEvent($event,
                                           $itemtype,
                                           $item->getEntityID()) as $notification_id => $notification) {

            $notificationtarget = NotificationTarget::getByNotificationIdAndEntity($notification_id,
                                                                                   $item,
                                                                                   array());

            //Get all users addresses
            $notifications_infos[] = array ("notification"=>$notification,
                                            "targets"=>$notificationtarget);

            foreach ($notifications_infos as $info) {
               logDebug($info['targets']);
               //TODO send notifications
            }
         }
      }
      return true;
   }

   public function getValues() {
      return $$this->values;
   }
}
?>