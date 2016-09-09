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


/**
 * NotificationTargetCrontask Class
**/
class NotificationTargetCrontask extends NotificationTarget {


   function getEvents() {
      return array('alert' => __('Monitoring of automatic actions'));
   }


   /**
    * Get all data needed for template processing
    *
    * @param $event
    * @param $options   array
   **/
   function getDatasForTemplate($event, $options=array()) {

      $events                             = $this->getAllEvents();
      $this->datas['##crontask.action##'] = $events[$event];

      $cron                               = new Crontask();
      foreach ($options['items'] as $id => $crontask) {
         $tmp                      = array();
         $tmp['##crontask.name##'] = '';

         if ($isplug=isPluginItemType($crontask["itemtype"])) {
            $tmp['##crontask.name##'] = $isplug["plugin"]." - ";
         }

         $tmp['##crontask.name##']       .= $crontask['name'];
         $tmp['##crontask.description##'] = $cron->getDescription($id);
         $tmp['##crontask.url##']         = $this->formatURL($options['additionnaloption']['usertype'],
                                                             "Crontask_".$id);
         $this->datas['crontasks'][] = $tmp;
      }

      $this->getTags();
      foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
         if (!isset($this->datas[$tag])) {
            $this->datas[$tag] = $values['label'];
         }
      }
   }


   function getTags() {

      $tags = array('crontask.action'      => __('Monitoring of automatic actions'),
                    'crontask.url'         => __('URL'),
                    'crontask.name'        => __('Name'),
                    'crontask.description' => __('Description'));

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'   => $tag,
                                   'label' => $label,
                                   'value' => true));
      }

      $this->addTagToList(array('tag'     => 'crontasks',
                                'label'   => __('Device list'),
                                'value'   => false,
                                'foreach' => true));

      //Tags with just lang
      $tags = array('crontask.warning'
                     => __('The following automatic actions are in error. They require intervention.'));
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
