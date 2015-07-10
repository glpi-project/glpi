<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

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
   die("Sorry. You can't access directly to this file");
}


/**
 * NotificationTargetInfocom Class
**/
class NotificationTargetInfocom extends NotificationTarget {


   function getEvents() {
      return array('alert' => __('Alarms on financial and administrative information'));
   }


   /**
    * Get all data needed for template processing
    *
    * @param $event
    * @param $options   array
   **/
   function getDatasForTemplate($event, $options=array()) {

      $events                                 = $this->getAllEvents();

      $this->datas['##infocom.entity##']      = Dropdown::getDropdownName('glpi_entities',
                                                                          $options['entities_id']);
      $this->datas['##infocom.action##']      = $events[$event];

      foreach ($options['items'] as $id => $item) {
         $tmp = array();

         if ($obj = getItemForItemtype($item['itemtype'])) {
            $tmp['##infocom.itemtype##']
                                     = $obj->getTypeName(1);
            $tmp['##infocom.item##'] = $item['item_name'];
            $tmp['##infocom.expirationdate##']
                                     = $item['warrantyexpiration'];
            $tmp['##infocom.url##']  = $this->formatURL($options['additionnaloption']['usertype'],
                                                        $item['itemtype']."_".
                                                          $item['items_id']."_Infocom");
         }
         $this->datas['infocoms'][] = $tmp;
      }

      $this->getTags();
      foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
         if (!isset($this->datas[$tag])) {
            $this->datas[$tag] = $values['label'];
         }
      }
   }


   function getTags() {

      $tags = array('infocom.action'         => _n('Event', 'Events', 1),
                    'infocom.itemtype'       => __('Item type'),
                    'infocom.item'           => __('Associated item'),
                    'infocom.expirationdate' => __('Expiration date'),
                    'infocom.entity'         => __('Entity'));

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'   => $tag,
                                   'label' => $label,
                                   'value' => true));
      }

      $this->addTagToList(array('tag'     => 'items',
                                'label'   => __('Device list'),
                                'value'   => false,
                                'foreach' => true));

      asort($this->tag_descriptions);
   }

}
?>