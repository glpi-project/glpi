<?php
/*
 * @version $Id: notificationtargetuser.class.php 13683 2011-01-19 08:49:07Z yllen $
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
class NotificationTargetFieldUnicity extends NotificationTarget {

   function getEvents() {
      global $LANG;

      return array('refuse' => $LANG['setup'][821]);
   }

   /**
    * Get all data needed for template processing
   **/
   function getDatasForTemplate($event, $options=array()) {
      global $LANG;

      //User who tries to add or update an item in DB
      $action = ($options['action_user']?$LANG['log'][20]:$LANG['log'][21]);
      $this->datas['##unicity.action_type##'] = $action;
      $this->datas['##unicity.action_user##'] = $options['action_user'];
      $this->datas['##unicity.message##']     = $options['message'];
      $this->datas['##unicity.date##']        = convDateTime($options['date']);
      $item = new $options['itemtype'];
      $this->datas['##unicity.itemtype##']    = $item->getTypeName();
      $this->datas['##unicity.entity##']      = Dropdown::getDropdownName('glpi_entities',
                                                                          $options['entities_id']);
      if ($options['refuse']) {
         $this->datas['##unicity.action##'] = $LANG['setup'][821];
      } else {
         $this->datas['##unicity.action##'] = $LANG['setup'][823];
      }
      $this->getTags();
      foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
         if (!isset($this->datas[$tag])) {
            $this->datas[$tag] = $values['label'];
         }
      }
   }


   function getTags() {
      global $LANG;

      $tags = array('unicity.message'     => $LANG['event'][4],
                    'unicity.action_user' => $LANG['setup'][824],
                    'unicity.action_type' => $LANG['setup'][825],
                    'unicity.date'        => $LANG['common'][27],
                    'unicity.itemtype'    => $LANG['common'][17],
                    'unicity.entity'      => $LANG['entity'][0],
                    'unicity.action'      => $LANG['setup'][827]);

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'   => $tag,
                                   'label' => $label,
                                   'value' => true));
      }

      asort($this->tag_descriptions);
      return $this->tag_descriptions;
   }

}
?>