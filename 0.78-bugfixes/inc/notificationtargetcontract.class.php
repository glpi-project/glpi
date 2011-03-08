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
class NotificationTargetContract extends NotificationTarget {

   function getEvents() {
      global $LANG;

      return array ('end'    => $LANG['financial'][86],
                    'notice' => $LANG['financial'][10]);
   }


   /**
    * Get all data needed for template processing
    */
   function getDatasForTemplate($event, $options=array()) {
      global $LANG,$CFG_GLPI;

      $this->datas['##contract.entity##'] = Dropdown::getDropdownName('glpi_entities',
                                                                      $options['entities_id']);
      $events = $this->getEvents();
      $this->datas['##contract.action##']      = $events[$event];

      foreach($options['contracts'] as $id => $contract) {
         $tmp = array();
         $tmp['##contract.name##']   = $contract['name'];
         $tmp['##contract.number##'] = $contract['num'];
         if ($contract['contracttypes_id']) {
            $tmp['##contract.type##'] = Dropdown::getDropdownName('glpi_contracttypes',
                                                                  $contract['contracttypes_id']);
         } else {
            $tmp['##contract.type##'] = "";
         }
         $tmp['##contract.time##'] = getWarrantyExpir($contract["begin_date"],$contract["duration"],
                                                      $contract["notice"]);
         $tmp['##contract.url##'] = urldecode($CFG_GLPI["url_base"].
                                              "/index.php?redirect=contract_".$id);
         $this->datas['contracts'][] = $tmp;
      }

      $this->getTags();
      foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
         $this->datas[$tag] = $values['label'];
      }

      $this->datas['##lang.contract.time##']   = ($event==Alert::END?$LANG['contract'][0]:
                                                                     $LANG['contract'][1]);

   }

   function getTags() {
      global $LANG;

      $tags = array('contract.action'          =>$LANG['mailing'][39],
                    'contract.name'            =>$LANG['common'][16],
                    'contract.number'          =>$LANG['financial'][4],
                    'contract.type'            =>$LANG['common'][17],
                    'contract.time'            =>$LANG['contract'][0].'/'.$LANG['contract'][1],
                    'contract.entity'          =>$LANG['entity'][0]);
      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'=>$tag,'label'=>$label,
                                   'value'=>true));
      }

      $this->addTagToList(array('tag'=>'contracts','label'=>$LANG['reports'][57],
                                'value'=>false,'foreach'=>true));

      asort($this->tag_descriptions);
   }
}
?>