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

// Class NotificationTarget
class NotificationTargetUser extends NotificationTarget {


   function getEvents() {
      return ['passwordforget' => __('Forgotten password?')];
   }


   /**
    * @see NotificationTarget::addNotificationTargets()
   **/
   function addNotificationTargets($entity) {
      $this->addTarget(Notification::USER, __('User'));
   }


   /**
    * @see NotificationTarget::addSpecificTargets()
   **/
   function addSpecificTargets($data, $options) {

      //Look for all targets whose type is Notification::ITEM_USER
      switch ($data['type']) {
         case Notification::USER_TYPE :
            switch ($data['items_id']) {
               case Notification::USER :
                  $usertype = self::GLPI_USER;
                  if ($this->obj->fields['authtype'] != Auth::DB_GLPI) {
                     $usertype = self::EXTERNAL_USER;
                  }
                  // Send to user without any check on profile / entity
                  // Do not set users_id
                  $data = ['name'     => $this->obj->getName(),
                                'email'    => $this->obj->getDefaultEmail(),
                                'language' => $this->obj->getField('language'),
                                'usertype' => $usertype];
                  $this->addToRecipientsList($data);
            }
      }
   }


   function addDataForTemplate($event, $options = []) {
      global $CFG_GLPI;

      $events = $this->getEvents();

      $this->data['##user.name##']      = $this->obj->getField("name");
      $this->data['##user.realname##']  = $this->obj->getField("realname");
      $this->data['##user.firstname##'] = $this->obj->getField("firstname");
      $this->data['##user.token##']     = $this->obj->getField("password_forget_token");

      $this->data['##user.action##']    = $events[$event];
      $this->data['##user.passwordforgeturl##']
                                         = urldecode($CFG_GLPI["url_base"].
                                                     "/front/lostpassword.php?password_forget_token=".
                                                     $this->obj->getField("password_forget_token"));

      $this->getTags();
      foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
         if (!isset($this->data[$tag])) {
            $this->data[$tag] = $values['label'];
         }
      }
   }


   function getTags() {

      $tags = ['user.name'              => __('Login'),
                    'user.realname'          => __('Name'),
                    'user.firstname'         => __('First name'),
                    'user.token'             => __('Token'),
                    'user.passwordforgeturl' => __('URL'),
                    'user.action'            => _n('Event', 'Events', 1)];

      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'   => $tag,
                                   'label' => $label,
                                   'value' => true]);
      }

      // Only lang
      $lang = ['passwordforget.information'
                        => __('You have been made a request to reset your account password.'),
                    'passwordforget.link'
                        => __('Just follow this link (you have one day):')];

      foreach ($lang as $tag => $label) {
         $this->addTagToList(['tag'   => $tag,
                                   'label' => $label,
                                   'value' => false,
                                   'lang'  => true]);
      }

      asort($this->tag_descriptions);
      return $this->tag_descriptions;
   }

}
