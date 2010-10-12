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

// ----------------------------------------------------------------------
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

/// Class RequestType
class RequestType extends CommonDropdown {


   static function getTypeName() {
      global $LANG;

      return $LANG['job'][44];
   }

   function getAdditionalFields() {
      global $LANG;

      return array(array('name'  => 'is_helpdesk_default',
                         'label' => $LANG['tracking'][9],
                         'type'  => 'bool'),
                   array('name'  => 'is_mail_default',
                         'label' => $LANG['tracking'][10],
                         'type'  => 'bool'));
   }

   function getSearchOptions() {
      global $LANG;

      $tab = parent::getSearchOptions();

      $tab[14]['table']         = $this->getTable();
      $tab[14]['field']         = 'is_helpdesk_default';
      $tab[14]['name']          = $LANG['tracking'][9];
      $tab[14]['datatype']      = 'bool';
      $tab[14]['massiveaction'] = false;

      $tab[15]['table']         = $this->getTable();
      $tab[15]['field']         = 'is_mail_default';
      $tab[15]['name']          = $LANG['tracking'][10];
      $tab[15]['datatype']      = 'bool';
      $tab[15]['massiveaction'] = false;

      return $tab;
   }

   function post_addItem() {
      global $DB;

      if (isset($this->input["is_helpdesk_default"]) && $this->input["is_helpdesk_default"]) {
         $query = "UPDATE ".
                   $this->getTable()."
                   SET `is_helpdesk_default` = '0'
                   WHERE `id` <> '".$this->fields['id']."'";
         $DB->query($query);
      }
      if (isset($this->input["is_mail_default"]) && $this->input["is_mail_default"]) {
         $query = "UPDATE ".
                   $this->getTable()."
                   SET `is_mail_default` = '0'
                   WHERE `id` <> '".$this->fields['id']."'";
         $DB->query($query);
      }
   }

   function post_updateItem($history=1) {
      global $DB, $LANG;

      if (in_array('is_helpdesk_default',$this->updates)) {
         if ($this->input["is_helpdesk_default"]) {
            $query = "UPDATE ".
                      $this->getTable()."
                      SET `is_helpdesk_default` = '0'
                      WHERE `id` <> '".$this->input['id']."'";
            $DB->query($query);
         } else {
            addMessageAfterRedirect($LANG['setup'][313], true);
         }
      }
      if (in_array('is_mail_default',$this->updates)) {
         if ($this->input["is_mail_default"]) {
            $query = "UPDATE ".
                      $this->getTable()."
                      SET `is_mail_default` = '0'
                      WHERE `id` <> '".$this->input['id']."'";
            $DB->query($query);
         } else {
            addMessageAfterRedirect($LANG['setup'][313], true);
         }
      }
   }

   /**
    * Get the default request type for a given source (mail, helpdesk)
    *
    * @param $source string
    *
    * @return requesttypes_id
    */
   static function getDefault($source) {
      global $DB;

      if (!in_array($source, array('mail','helpdesk'))) {
         return 0;
      }
      foreach ($DB->request('glpi_requesttypes', array('is_'.$source.'_default'=>1)) as $data) {
         return $data['id'];
      }
      return 0;
   }
}

?>