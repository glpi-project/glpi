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

// Class NotificationTarget
class NotificationTargetUser extends NotificationTarget {


   function getEvents() {
      return array('passwordforget' => __('Forgotten password?'));
   }


   /**
    * @see NotificationTarget::getNotificationTargets()
   **/
   function getNotificationTargets($entity) {
      $this->addTarget(Notification::USER, __('User'));
   }


   /**
    * @see NotificationTarget::getSpecificTargets()
   **/
   function getSpecificTargets($data,$options) {

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
                  $data = array('name'     => $this->obj->getName(),
                                'email'    => $this->obj->getDefaultEmail(),
                                'language' => $this->obj->getField('language'),
                                'usertype' => $usertype);
                  $this->addToAddressesList($data);
         }
      }
   }


   /**
    * Get all data needed for template processing
    *
    * @param $event
    * @param $options   array
   **/
   function getDatasForTemplate($event, $options=array()) {
      global $CFG_GLPI;

      $events = $this->getEvents();

      $this->datas['##user.name##']      = $this->obj->getField("name");
      $this->datas['##user.realname##']  = $this->obj->getField("realname");
      $this->datas['##user.firstname##'] = $this->obj->getField("firstname");
      $this->datas['##user.token##']     = $this->obj->getField("password_forget_token");

      $this->datas['##user.action##']    = $events[$event];
      $this->datas['##user.passwordforgeturl##']
                                         = urldecode($CFG_GLPI["url_base"].
                                                     "/front/lostpassword.php?password_forget_token=".
                                                     $this->obj->getField("password_forget_token"));

      $this->getTags();
      foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
         if (!isset($this->datas[$tag])) {
            $this->datas[$tag] = $values['label'];
         }
      }
   }


   function getTags() {

      $tags = array('user.name'              => __('Login'),
                    'user.realname'          => __('Name'),
                    'user.firstname'         => __('First name'),
                    'user.token'             => __('Token'),
                    'user.passwordforgeturl' => __('URL'),
                    'user.action'            => _n('Event', 'Events', 1));

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'   => $tag,
                                   'label' => $label,
                                   'value' => true));
      }

      // Only lang
      $lang = array('passwordforget.information'
                        => __('You have been made a request to reset your account password.'),
                    'passwordforget.link'
                        => __('Just follow this link (you have one day):'));

      foreach ($lang as $tag => $label) {
         $this->addTagToList(array('tag'   => $tag,
                                   'label' => $label,
                                   'value' => false,
                                   'lang'  => true));
      }

      asort($this->tag_descriptions);
      return $this->tag_descriptions;
   }

}
?>