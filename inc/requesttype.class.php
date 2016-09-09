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

/// Class RequestType
class RequestType extends CommonDropdown {


   static function getTypeName($nb=0) {
      return _n('Request source', 'Request sources', $nb);
   }


   function getAdditionalFields() {

      return array(array('name'  => 'is_active',
                         'label' => __('Active'),
                         'type'  => 'bool'),
                   array('name'  => 'is_helpdesk_default',
                         'label' => __('Default for tickets'),
                         'type'  => 'bool'),
                   array('name'  => 'is_followup_default',
                         'label' => __('Default for followups'),
                         'type'  => 'bool'),
                   array('name'  => 'is_mail_default',
                         'label' => __('Default for mail recipients'),
                         'type'  => 'bool'),
                   array('name'  => 'is_mailfollowup_default',
                         'label' => __('Default for followup mail recipients'),
                         'type'  => 'bool'),
                   array('name'  => 'is_ticketheader',
                         'label' => __('Request source visible for tickets'),
                         'type'  => 'bool'),
                   array('name'  => 'is_ticketfollowup',
                         'label' => __('Request source visible for followups'),
                         'type'  => 'bool'),
                         );
   }


   function getSearchOptions() {

      $tab                      = parent::getSearchOptions();

      $tab[14]['table']         = $this->getTable();
      $tab[14]['field']         = 'is_helpdesk_default';
      $tab[14]['name']          = __('Default for tickets');
      $tab[14]['datatype']      = 'bool';
      $tab[14]['massiveaction'] = false;

      $tab[182]['table']         = $this->getTable();
      $tab[182]['field']         = 'is_followup_default';
      $tab[182]['name']          = __('Default for followups');
      $tab[182]['datatype']      = 'bool';
      $tab[182]['massiveaction'] = false;

      $tab[15]['table']         = $this->getTable();
      $tab[15]['field']         = 'is_mail_default';
      $tab[15]['name']          = __('Default for mail recipients');
      $tab[15]['datatype']      = 'bool';
      $tab[15]['massiveaction'] = false;

      $tab[183]['table']         = $this->getTable();
      $tab[183]['field']         = 'is_mailfollowup_default';
      $tab[183]['name']          = __('Default for followup mail recipients');
      $tab[183]['datatype']      = 'bool';
      $tab[183]['massiveaction'] = false;

      $tab[8]['table']          = $this->getTable();
      $tab[8]['field']          = 'is_active';
      $tab[8]['name']           = __('Active');
      $tab[8]['datatype']       = 'bool';

      $tab[180]['table']         = $this->getTable();
      $tab[180]['field']         = 'is_ticketheader';
      $tab[180]['name']          = __('Request source visible for tickets');
      $tab[180]['datatype']      = 'bool';

      $tab[181]['table']         = $this->getTable();
      $tab[181]['field']         = 'is_ticketfollowup';
      $tab[181]['name']          = __('Request source visible for followups');
      $tab[181]['datatype']      = 'bool';

      return $tab;
   }


   function post_addItem() {
      global $DB;

      if (isset($this->input["is_helpdesk_default"]) && $this->input["is_helpdesk_default"]) {
         $query = "UPDATE `".$this->getTable()."`
                   SET `is_helpdesk_default` = '0'
                   WHERE `id` <> '".$this->fields['id']."'";
         $DB->query($query);
      }

      if (isset($this->input["is_followup_default"]) && $this->input["is_followup_default"]) {
         $query = "UPDATE `".$this->getTable()."`
                   SET `is_followup_default` = '0'
                   WHERE `id` <> '".$this->fields['id']."'";
         $DB->query($query);
      }

      if (isset($this->input["is_mail_default"]) && $this->input["is_mail_default"]) {
         $query = "UPDATE `".$this->getTable()."`
                   SET `is_mail_default` = '0'
                   WHERE `id` <> '".$this->fields['id']."'";
         $DB->query($query);
      }

      if (isset($this->input["is_mailfollowup_default"]) && $this->input["is_mailfollowup_default"]) {
         $query = "UPDATE `".$this->getTable()."`
                   SET `is_mailfollowup_default` = '0'
                   WHERE `id` <> '".$this->fields['id']."'";
         $DB->query($query);
      }
   }


   /**
    * @see CommonDBTM::post_updateItem()
   **/
   function post_updateItem($history=1) {
      global $DB;

      if (in_array('is_helpdesk_default',$this->updates)) {

         if ($this->input["is_helpdesk_default"]) {
            $query = "UPDATE `".$this->getTable()."`
                      SET `is_helpdesk_default` = '0'
                      WHERE `id` <> '".$this->input['id']."'";
            $DB->query($query);

         } else {
            Session::addMessageAfterRedirect(__('Be careful: there is no default value'), true);
         }
      }

      if (in_array('is_followup_default',$this->updates)) {

         if ($this->input["is_followup_default"]) {
            $query = "UPDATE `".$this->getTable()."`
                      SET `is_followup_default` = '0'
                      WHERE `id` <> '".$this->input['id']."'";
            $DB->query($query);

         } else {
            Session::addMessageAfterRedirect(__('Be careful: there is no default value'), true);
         }
      }

      if (in_array('is_mail_default',$this->updates)) {

         if ($this->input["is_mail_default"]) {
            $query = "UPDATE `".$this->getTable()."`
                      SET `is_mail_default` = '0'
                      WHERE `id` <> '".$this->input['id']."'";
            $DB->query($query);

         } else {
            Session::addMessageAfterRedirect(__('Be careful: there is no default value'), true);
         }
      }

      if (in_array('is_mailfollowup_default',$this->updates)) {

         if ($this->input["is_mailfollowup_default"]) {
            $query = "UPDATE `".$this->getTable()."`
                      SET `is_mailfollowup_default` = '0'
                      WHERE `id` <> '".$this->input['id']."'";
            $DB->query($query);

         } else {
            Session::addMessageAfterRedirect(__('Be careful: there is no default value'), true);
         }
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

      if (!in_array($source, array('mail', 'mailfollowup', 'helpdesk', 'followup'))) {
         return 0;
      }

      foreach ($DB->request('glpi_requesttypes', array('is_'.$source.'_default' => 1, 'is_active' => 1)) as $data) {
         return $data['id'];
      }
      return 0;
   }


   function cleanDBonPurge() {
      Rule::cleanForItemCriteria($this);
   }

}
?>