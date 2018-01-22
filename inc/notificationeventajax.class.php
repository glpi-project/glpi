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

class NotificationEventAjax extends NotificationEventAbstract implements NotificationEventInterface {

   static public function getTargetFieldName() {
      return 'users_id';
   }


   static public function getTargetField(&$data) {
      $field = self::getTargetFieldName();

      if (!isset($data[$field])) {
         //Missing users_id; set to null
         $data[$field] = null;
      }

      return $field;
   }


   static public function canCron() {
      //notifications are pulled from web browser, it must not be handled from cron
      return false;
   }


   static public function getAdminData() {
      //since admin cannot be logged in; no ajax notifications for global admin
      return false;
   }


   static public function getEntityAdminsData($entity) {
      //since entities admin cannot be logged in; no ajax notifications for them
      return false;
   }


   static public function send(array $data) {
      Toolbox::logError(__METHOD__ . ' should not be called!');
      return false;
   }
}
