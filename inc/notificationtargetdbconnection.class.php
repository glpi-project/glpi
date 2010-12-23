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
class NotificationTargetDBConnection extends NotificationTarget {

   //Overwrite the function in NotificationTarget because there's only one target to be notified

   function getNotificationTargets($entity) {
      global $LANG;

      $this->addProfilesToTargets();
      $this->addGroupsToTargets($entity);
      $this->addTarget(Notification::GLOBAL_ADMINISTRATOR, $LANG['setup'][237]);
   }


   function getEvents() {
      global $LANG;

      return array ('desynchronization' => $LANG['setup'][810]);
   }


   function getDatasForTemplate($event, $options=array()) {
      global $LANG;

      $this->datas['##dbconnection.delay##'] = timestampToString($options['diff'], true).
                                               " (".$options['name'].")";
   }


   function getTags() {
      global $LANG;

      $tags = array('dbconnection.delay' => $LANG['setup'][803]);

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'   => $tag,
                                   'label' => $label,
                                   'value' => true,
                                   'lang'  => true));
      }

      //Tags with just lang
      $tags = array('dbconnection.title' => $LANG['setup'][808],
                    'dbconnection.delay' => $LANG['setup'][807]);

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'   => $tag,
                                   'label' => $label,
                                   'value' => false,
                                   'lang'  => true));
      }

      asort($this->tag_descriptions);
   }

}
?>