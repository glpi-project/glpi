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
class NotificationTargetUser extends NotificationTarget {

   function getEvents() {
      global $LANG;

      return array ('passwordforget' => $LANG['users'][3]);
   }


   function getNotificationTargets($entity) {
      global $LANG;
      $this->addTarget(Notification::USER,$LANG['common'][34]);
   }


   function getSpecificTargets($data,$options) {

   //Look for all targets whose type is Notification::ITEM_USER
   switch ($data['type']) {
      case Notification::USER_TYPE :

         switch ($data['items_id']) {
            case Notification::USER :
               $this->getUserByField('id');
               break;

         }
      }
   }


   /**
    * Get all data needed for template processing
   **/
   function getDatasForTemplate($event, $options=array()) {
      global $LANG,$CFG_GLPI;

      $events = $this->getEvents();

      $this->datas['##user.name##']      = $this->obj->getField("name");
      $this->datas['##user.realname##']  = $this->obj->getField("realname");
      $this->datas['##user.firstname##'] = $this->obj->getField("firstname");
      $this->datas['##user.token##']     = $this->obj->getField("token");

      $this->datas['##user.action##']            = $events[$event];
      $this->datas['##user.passwordforgeturl##'] = urldecode($CFG_GLPI["url_base"].
                                                             "/front/lostpassword.php?token=".
                                                             $this->obj->getField("token"));

      $this->getTags();
      foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
         if (!isset($this->datas[$tag])) {
            $this->datas[$tag] = $values['label'];
         }
      }
   }


   function getTags() {
      global $LANG;

      $tags = array('user.name'              => $LANG['login'][6],
                    'user.realname'          => $LANG['common'][16],
                    'user.firstname'         => $LANG['common'][43],
                    'user.token'             => $LANG['users'][4],
                    'user.passwordforgeturl' => $LANG['common'][94],
                    'user.action'            => $LANG['rulesengine'][30],
                     );
      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'=>$tag,'label'=>$label,
                                   'value'=>true));
      }

      $lang = array('passwordforget.information' => $LANG['users'][5],
                    'passwordforget.link'        => $LANG['users'][6]);

      foreach ($lang as $tag => $label) {
         $this->addTagToList(array('tag'   => $tag, 'label'=>$label,
                                   'value' => true, 'lang'=>true));
      }

      asort($this->tag_descriptions);
      return $this->tag_descriptions;
   }

}
?>