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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/// Class RequestType
class RequestType extends CommonDropdown {


   static function getTypeName($nb = 0) {
      return _n('Request source', 'Request sources', $nb);
   }


   function getAdditionalFields() {

      return [['name'  => 'is_active',
                         'label' => __('Active'),
                         'type'  => 'bool'],
                   ['name'  => 'is_helpdesk_default',
                         'label' => __('Default for tickets'),
                         'type'  => 'bool'],
                   ['name'  => 'is_followup_default',
                         'label' => __('Default for followups'),
                         'type'  => 'bool'],
                   ['name'  => 'is_mail_default',
                         'label' => __('Default for mail recipients'),
                         'type'  => 'bool'],
                   ['name'  => 'is_mailfollowup_default',
                         'label' => __('Default for followup mail recipients'),
                         'type'  => 'bool'],
                   ['name'  => 'is_ticketheader',
                         'label' => __('Request source visible for tickets'),
                         'type'  => 'bool'],
                   ['name'  => 'is_itilfollowup',
                         'label' => __('Request source visible for followups'),
                         'type'  => 'bool'],
                         ];
   }


   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'                 => '14',
         'table'              => $this->getTable(),
         'field'              => 'is_helpdesk_default',
         'name'               => __('Default for tickets'),
         'datatype'           => 'bool',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '182',
         'table'              => $this->getTable(),
         'field'              => 'is_followup_default',
         'name'               => __('Default for followups'),
         'datatype'           => 'bool',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '15',
         'table'              => $this->getTable(),
         'field'              => 'is_mail_default',
         'name'               => __('Default for mail recipients'),
         'datatype'           => 'bool',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '183',
         'table'              => $this->getTable(),
         'field'              => 'is_mailfollowup_default',
         'name'               => __('Default for followup mail recipients'),
         'datatype'           => 'bool',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '8',
         'table'              => $this->getTable(),
         'field'              => 'is_active',
         'name'               => __('Active'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '180',
         'table'              => $this->getTable(),
         'field'              => 'is_ticketheader',
         'name'               => __('Request source visible for tickets'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '181',
         'table'              => $this->getTable(),
         'field'              => 'is_itilfollowup',
         'name'               => __('Request source visible for followups'),
         'datatype'           => 'bool'
      ];

      return $tab;
   }


   function post_addItem() {
      global $DB;

      $update = [];

      if (isset($this->input["is_helpdesk_default"]) && $this->input["is_helpdesk_default"]) {
         $update['is_helpdesk_default'] = 0;
      }

      if (isset($this->input["is_followup_default"]) && $this->input["is_followup_default"]) {
         $update['is_followup_default'] = 0;
      }

      if (isset($this->input["is_mail_default"]) && $this->input["is_mail_default"]) {
         $update['is_mail_default'] = 0;
      }

      if (isset($this->input["is_mailfollowup_default"]) && $this->input["is_mailfollowup_default"]) {
         $update['is_mailfollowup_default'] = 0;
      }

      if (count($update)) {
         $DB->update(
            $this->getTable(),
            $update, [
               'id' => ['<>', $this->fields['id']]
            ]
         );
      }
   }


   /**
    * @see CommonDBTM::post_updateItem()
   **/
   function post_updateItem($history = 1) {
      global $DB;
      $update =[];

      if (in_array('is_helpdesk_default', $this->updates)) {
         if ($this->input["is_helpdesk_default"]) {
            $update['is_helpdesk_default'] = 0;
         } else {
            Session::addMessageAfterRedirect(__('Be careful: there is no default value'), true);
         }
      }

      if (in_array('is_followup_default', $this->updates)) {
         if ($this->input["is_followup_default"]) {
            $update['is_followup_default'] = 0;
         } else {
            Session::addMessageAfterRedirect(__('Be careful: there is no default value'), true);
         }
      }

      if (in_array('is_mail_default', $this->updates)) {
         if ($this->input["is_mail_default"]) {
            $update['is_mail_default'] = 0;
         } else {
            Session::addMessageAfterRedirect(__('Be careful: there is no default value'), true);
         }
      }

      if (in_array('is_mailfollowup_default', $this->updates)) {
         if ($this->input["is_mailfollowup_default"]) {
            $update['is_mailfollowup_default'] = 0;
         } else {
            Session::addMessageAfterRedirect(__('Be careful: there is no default value'), true);
         }
      }

      if (count($update)) {
         $DB->update(
            $this->getTable(),
            $update, [
               'id' => ['<>', $this->fields['id']]
            ]
         );
      }
   }


   /**
    * Get the default request type for a given source (mail, helpdesk)
    *
    * @param $source string
    *
    * @return requesttypes_id
   **/
   static function getDefault($source) {
      global $DB;

      if (!in_array($source, ['mail', 'mailfollowup', 'helpdesk', 'followup'])) {
         return 0;
      }

      foreach ($DB->request('glpi_requesttypes', ['is_'.$source.'_default' => 1, 'is_active' => 1]) as $data) {
         return $data['id'];
      }
      return 0;
   }


   function cleanDBonPurge() {
      Rule::cleanForItemCriteria($this);
   }

}
