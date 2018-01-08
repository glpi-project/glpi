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

interface NotificationEventInterface {

   /**
    * Raise a notification event
    *
    * @param string               $event              Event
    * @param CommonDBTM           $item               Item
    * @param array                $options            Options
    * @param string               $label              Label
    * @param array                $data               Notification data
    * @param NotificationTarget   $notificationtarget Target
    * @param NotificationTemplate $template           Template
    * @param boolean              $notify_me          Whether to notify current user
    *
    * @return void
    */
   static public function raise(
      $event,
      CommonDBTM $item,
      array $options,
      $label,
      array $data,
      NotificationTarget $notificationtarget,
      NotificationTemplate $template,
      $notify_me
   );


   /**
    * Get target field name
    *
    * @return string
    */
   static public function getTargetFieldName();

   /**
    * Get (and populate if needed) target field for notification
    *
    * @param array $data Input event data
    *
    * @return string
    */
   static public function getTargetField(&$data);

   /**
    * Whether notifications can be handled by a crontab
    *
    * @return boolean
    */
   static public function canCron();

   /**
    * Get admin data
    *
    * @return array
    */
   static public function getAdminData();

   /**
    * Get entity admin data
    *
    * @param integer $entity Entity ID
    *
    * @return array
    */
   static public function getEntityAdminsData($entity);


   /**
    * Send notification
    *
    * @param array $data Data to send
    *
    * @return false|integer False if something went wrong, number of send notifications otherwise
    */
   static public function send(array $data);
}
