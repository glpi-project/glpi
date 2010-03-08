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
class NotificationTargetCartridge extends NotificationTarget {

   function getEvents() {
      global $LANG;

      return array ('alert' => $LANG['mailing'][33]);
   }

      /**
    * Get all data needed for template processing
    */
   function getDatasForTemplate($event, $options=array()) {
      global $LANG,$CFG_GLPI;

      $this->datas['##cartridge.entity##'] = Dropdown::getDropdownName('glpi_entities',
                                                               $options['entities_id']);
      $this->datas['##lang.cartridge.entity##'] = $LANG['entity'][0];
      $this->datas['##cartridge.action##']      = $LANG['mailing'][33];

      foreach ($options['cartridges'] as $id => $cartridge) {
         $tmp = array();
         $tmp['##cartridge.item##']      = $cartridge['cartname'];
         $tmp['##cartridge.reference##'] = $cartridge['cartref'];
         $tmp['##cartridge.remaining##']     = cartridge::getUnusedNumber($id);
         $tmp['##cartridge.url##'] = urldecode($CFG_GLPI["url_base"].
                                              "/index.php?redirect=cartridgeitem_".$id);
         $this->datas['cartridges'][] = $tmp;
      }

      $this->datas['##lang.cartridge.entity##']    = $LANG['entity'][0];
      $this->datas['##lang.cartridge.action##']    = $LANG['mailing'][36];
      $this->datas['##lang.cartridge.item##']      = $LANG['mailing'][35];
      $this->datas['##lang.cartridge.reference##'] = $LANG['consumables'][2];
      $this->datas['##lang.cartridge.remaining##']     = $LANG['software'][20];
   }
}
?>