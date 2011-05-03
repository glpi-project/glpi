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
class NotificationTargetCrontask extends NotificationTarget {

   function getEvents() {
      global $LANG;

      return array('alert' => $LANG['crontask'][17]);
   }


   /**
    * Get all data needed for template processing
   **/
   function getDatasForTemplate($event, $options=array()) {
      global $LANG, $CFG_GLPI;

      $events = $this->getAllEvents();

      $this->datas['##crontask.action##'] = $events[$event];

      $cron = new Crontask();
      foreach ($options['crontasks'] as $id => $crontask) {
         $tmp = array();
         $tmp['##crontask.name##'] = '';

         if ($isplug=isPluginItemType($crontask["itemtype"])) {
            $tmp['##crontask.name##'] = $isplug["plugin"]." - ";
         }

         $tmp['##crontask.name##']       .= $crontask['name'];
         $tmp['##crontask.description##'] = $cron->getDescription($id);
         $tmp['##crontask.url##']         = urldecode($CFG_GLPI["url_base"].
                                                      "/index.php?redirect=crontask_".$id);
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
      global $LANG;

      $tags = array('crontask.action'      => $LANG['crontask'][17],
                    'crontask.url'         => $LANG['common'][94],
                    'crontask.name'        => $LANG['common'][16],
                    'crontask.description' => $LANG['joblist'][6]);

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'   => $tag,
                                   'label' => $label,
                                   'value' => true));
      }

      $this->addTagToList(array('tag'     => 'crontasks',
                                'label'   => $LANG['reports'][57],
                                'value'   => false,
                                'foreach' => true));

      //Tags with just lang
      $tags = array('crontask.warning' => $LANG['crontask'][49]);
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
