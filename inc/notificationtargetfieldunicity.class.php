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
class NotificationTargetFieldUnicity extends NotificationTarget {


   function getEvents() {
      return array('refuse' => __('Alert on duplicate record'));
   }


   /**
    * Get all data needed for template processing
    *
    * @param $event
    * @param $options   array
   **/
   function getDatasForTemplate($event, $options=array()) {

      //User who tries to add or update an item in DB
      $action = ($options['action_user'] ?__('Add the item') :__('Update the item'));
      $this->datas['##unicity.action_type##'] = $action;
      $this->datas['##unicity.action_user##'] = $options['action_user'];
      $this->datas['##unicity.date##']        = Html::convDateTime($options['date']);

      if ($item = getItemForItemtype($options['itemtype'])) {
         $this->datas['##unicity.itemtype##'] = $item->getTypeName(1);
         $this->datas['##unicity.message##']
                  = Html::clean($item->getUnicityErrorMessage($options['label'],
                                                              $options['field'],
                                                              $options['double']));
      }
      $this->datas['##unicity.entity##']      = Dropdown::getDropdownName('glpi_entities',
                                                                          $options['entities_id']);
      if ($options['refuse']) {
         $this->datas['##unicity.action##'] = __('Record into the database denied');
      } else {
         $this->datas['##unicity.action##'] = __('Item successfully added but duplicate record on');
      }
      $this->getTags();
      foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
         if (!isset($this->datas[$tag])) {
            $this->datas[$tag] = $values['label'];
         }
      }
   }


   function getTags() {

      $tags = array('unicity.message'     => __('Message'),
                    'unicity.action_user' => __('Doer'),
                    'unicity.action_type' => __('Intended action'),
                    'unicity.date'        => __('Date'),
                    'unicity.itemtype'    => __('Type'),
                    'unicity.entity'      => __('Entity'),
                    'unicity.action'      => __('Alert on duplicate record'));

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