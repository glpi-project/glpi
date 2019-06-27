<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

/**
 * SIEMServiceTemplate class.
 *
 * @since 10.0.0
 */
class SIEMServiceTemplate extends CommonDBTM {

   protected $twig_compat = true;

   /**
    * Name of the type
    *
    * @param $nb : number of item in the type
   **/
   static function getTypeName($nb = 0) {
      return _n('Service template', 'Service templates', $nb);
   }

   /**
    * Form fields configuration and mapping.
    *
    * Array order will define fields display order.
    *
    * Missing fields from database will be automatically displayed.
    * If you want to avoid this;
    * @see getFormHiddenFields and/or @see getFormFieldsToDrop
    *
    * @since 10.0.0
    *
    * @return array
    */
   protected function getFormFields() {
      $fields = [
         'name' => [
            'label'  => __('Name'),
            'type'   => 'text'
         ],
         'comment' => [
            'label'  => __('Comment'),
            'type'   => 'textarea'
         ],
         'links_id' => [
            'label'  => Link::getTypeName(1),
            'type'   => 'Link'
         ],
         'priority' => [
            'label'  => __('Priority'),
            'type'   => 'select',
            'values' => [
               0  => CommonITILObject::getPriorityName(0),
               -5 => CommonITILObject::getPriorityName(-5),
               -4 => CommonITILObject::getPriorityName(-4),
               -3 => CommonITILObject::getPriorityName(-3),
               -2 => CommonITILObject::getPriorityName(-2),
               -1 => CommonITILObject::getPriorityName(-1)
            ],
            //FIXME What is the purpose of requiring the following 2 keys?
            'itemtype_name' => null,
            'itemtype'      => null
         ],
         'calendars_id' => [
            'label'  => __('Notification period'),
            'type'   => 'Calendar',
         ],
         'notificationinterval' => [
            'label'  => __('Notification interval'),
            'type'   => 'number',
         ],
         'check_interval' => [
            'label'  => __('Check interval'),
            'type'   => 'number',
         ],
         'use_flap_detection' => [
            'label'  => __('Enable flapping detection'),
            'type'   => 'yesno',
         ],
         'check_mode' => [
            'label'  => __('Enable flapping detection'),
            'type'   => 'select',
            'values' => [
               SIEMService::CHECK_MODE_ACTIVE => __('Active'),
               SIEMService::CHECK_MODE_PASSIVE => __('Passive'),
               SIEMService::CHECK_MODE_HYBRID => __('Hybrid'),
            ],
            'itemtype_name' => null,
            'itemtype' => null
         ],
         'is_stateless' => [
            'label'  => __('Stateless'),
            'type'   => 'yesno',
         ],
         'flap_threshold_low' => [
            'label'  => __('Flapping lower threshold'),
            'name'   => 'flap_threshold_low',
            'type'   => 'number',
         ],
         'flap_threshold_high' => [
            'label'  => __('Flapping upper threshold'),
            'name'   => 'flap_threshold_high',
            'type'   => 'number',
         ],
         'max_checks' => [
            'label'  => __('Max checks'),
            'name'   => 'max_checks',
            'type'   => 'number',
         ],
         'logger' => [
            //FIXME Why doesn't type 'Plugin' work here?
            'label'  => __('Logger'),
            'type'   => 'select',
            'values' => [],
            'itemtype_name' => null,
            'itemtype' => null
         ],
         'sensor' => [
            'label'  => __('Sensor'),
            'type'   => 'select',
            'values' => [],
            'itemtype_name' => null,
            'itemtype' => null
         ]
      ] + parent::getFormFields();
      return $fields;
   }
}