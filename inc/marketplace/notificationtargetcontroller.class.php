<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace Glpi\Marketplace;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

use \Notification;
use \NotificationTarget;
use \Plugin;
use \Session;

// Class NotificationTarget
class NotificationTargetController extends NotificationTarget {

   /**
    * Overwrite the function in NotificationTarget because there's only one target to be notified
    *
    * @see NotificationTarget::addNotificationTargets()
   **/
   function addNotificationTargets($entity) {

      $this->addProfilesToTargets();
      $this->addGroupsToTargets($entity);
      $this->addTarget(Notification::GLOBAL_ADMINISTRATOR, __('Administrator'));
   }


   function getEvents() {
      return ['checkpluginsupdate' => __('Check all plugin updates')];
   }


   function addDataForTemplate($event, $options = []) {
      $updated_plugins = $options['plugins'];
      $plugin = new Plugin;
      foreach ($updated_plugins as $plugin_key => $version) {
         $plugin_info = $plugin->getInformationsFromDirectory($plugin_key);

         $this->data['plugins'][] = [
            '##plugin.name##'        => $plugin_info['name'],
            '##plugin.key##'         => $plugin_key,
            '##plugin.version##'     => $version,
            '##plugin.old_version##' => $plugin_info['version'],
         ];
      }

      $this->getTags();
      foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
         if (!isset($this->data[$tag])) {
            $this->data[$tag] = $values['label'];
         }
      }
   }


   function getTags() {
      //Tags with just lang
      $tags = [
         'plugins_updates_available' => __('Some updates are available for your installed plugins!')
      ];

      foreach ($tags as $tag => $label) {
         $this->addTagToList([
            'tag'   => $tag,
            'label' => $label,
            'value' => false,
            'lang'  => true
         ]);
      }

      //Foreach global tags
      $tags = [
         'plugins' => _n('Plugin', 'Plugins', Session::getPluralNumber()),
      ];

      foreach ($tags as $tag => $label) {
         $this->addTagToList([
            'tag'     => $tag,
            'label'   => $label,
            'value'   => false,
            'foreach' => true,
         ]);
      }

      // sub tags
      $tags = [
         'plugin.name'        => __('Plugin name'),
         'plugin.key'         => __('Plugin directory'),
         'plugin.version'     => __('Plugin new version number'),
         'plugin.old_version' => __('Plugin old version number')
      ];

      foreach ($tags as $tag => $label) {
         $this->addTagToList([
            'tag'    => $tag,
            'label'  => $label,
            'value'  => true,
         ]);
      }
   }
}
