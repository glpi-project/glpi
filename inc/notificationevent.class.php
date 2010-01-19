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
 * Entity Data class
 */
class NotificationEvent extends CommonDBTM {

   static function dropdownEvents($itemtype,$value='') {
      $events = array();

      if (class_exists($itemtype)) {
         $item = new $itemtype ();
         $events = $item->getEvents();
      }
      $events[''] = '-----';
      Dropdown::showFromArray('event',$events, array ('value'=>$value));
   }

   /**
    * Raise an event
    */
   static function raiseEvent($event, $itemtype, $entity,$item) {

      //Store notifications infos & targets
      $notifications_infos = array();

/*
      //Get all notifications by event, itemtype and entity
      foreach (Notification::getByEvent($event, $itemtype, $entity) as $notification_id => $notification) {
         $notifications_infos[] = array ($notification,
         NotificationTarget::getByNotificationIdAndEntity($notification_id, $itemtype,array()));
      }*/
   }


}

?>
