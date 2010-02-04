<?php
/*
 * @version $Id: notification.class.php 10030 2010-01-05 11:11:22Z moyo $
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

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

// Class NotificationTarget
class NotificationTargetContract extends NotificationTarget {

   function getEvents() {
      global $LANG;
      return array ('alert_end' => $LANG['financial'][98], 'alert_notice'=>$LANG['financial'][10]);
   }

      /**
    * Get all data needed for template processing
    */
   function getDatasForTemplate($event,$options=array()) {
      global $LANG;
      $tpldatas['##contract.entity##'] =
                           Dropdown::getDropdownName('glpi_entities',
                                                     $this->obj->getField('entities_id'));
      $tpldatas['##lang.contract.entity##'] = $LANG['entity'][0];
      switch ($options['type']) {
         case ALERT_END :
            $tpldatas['##lang.contract.action##'] = $LANG['mailing'][37];
            break;
         case ALERT_NOTICE:
            $tpldatas['##lang.contract.action##'] = $LANG['mailing'][38];
            break;
      }
      $tpldatas['##lang.contract.action##']= $LANG['mailing'][39];

      return $tpldatas;
   }
}
?>