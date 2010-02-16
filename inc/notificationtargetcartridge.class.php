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

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

// Class NotificationTarget
class NotificationTargetCartridge extends NotificationTarget {

   function __construct($entity='', $object = null) {
      parent::__construct($entity, $object);
      if ($object != null) {
         $this->getObjectItem();
      }
   }

   /**
    * Get item associated with the object on which the event was raised
    * @return the object associated with the itemtype
    */
   function getObjectItem() {
      $ci = new CartridgeItem;
      if ($ci->getFromDB($this->obj->getField('cartridgeitems_id')))
      {
         $this->target_object = $ci;
      }
   }

   function getEvents() {
      global $LANG;
      return array ('alert' => $LANG['mailing'][33]);
   }

      /**
    * Get all data needed for template processing
    */
   function getDatasForTemplate($event,$tpldata = array(), $options=array()) {
      global $LANG;
      $prefix = strtolower($item->getType());
      $tpldata['##'.$prefix.'.entity##'] =
                           Dropdown::getDropdownName('glpi_entities',
                                                     $this->obj->getField('entities_id'));
      $tpldata['##'.$prefix.'.item##'] = $this->target_object->getField('name');
      $tpldata['##'.$prefix.'.reference##'] = $this->target_object->getField('ref');
      $tpldata['##'.$prefix.'.value##'] = Cartridge::getUnusedNumber($this->getField('id'));

      $tpldata['##lang.'.$prefix.'.entity##'] = $LANG['entity'][0];
      $tpldata['##lang.'.$prefix.'.action##']= $LANG['mailing'][33];
      $tpldata['##lang.'.$prefix.'.item##'] = $LANG['mailing'][35];
      $tpldata['##lang.'.$prefix.'.reference##'] = $LANG['consumables'][2];
      $tpldata['##lang.'.$prefix.'.value##'] = $LANG['software'][20];

      return $tpldata;
   }
}
?>