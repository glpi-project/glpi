<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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

   // From CommonDBTM
   public $table = 'glpi_requesttypes';
   public $type = 'RequestType';

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

      $tab[14]['table']         = $this->table;
      $tab[14]['field']         = 'is_helpdesk_default';
      $tab[14]['linkfield']     = '';
      $tab[14]['name']          = $LANG['tracking'][9];
      $tab[14]['datatype']      = 'bool';

      $tab[15]['table']         = $this->table;
      $tab[15]['field']         = 'is_mail_default';
      $tab[15]['linkfield']     = '';
      $tab[15]['name']          = $LANG['tracking'][10];
      $tab[15]['datatype']      = 'bool';

      return $tab;
   }

   function post_addItem($newID,$input) {
      global $DB;

      if (isset($input["is_helpdesk_default"]) && $input["is_helpdesk_default"]) {
         $query = "UPDATE ".
                   $this->table."
                   SET `is_helpdesk_default` = '0'
                   WHERE `id` <> '$newID'";
         $DB->query($query);
      }
      if (isset($input["is_mail_default"]) && $input["is_mail_default"]) {
         $query = "UPDATE ".
                   $this->table."
                   SET `is_mail_default` = '0'
                   WHERE `id` <> '$newID'";
         $DB->query($query);
      }
   }

   function post_updateItem($input,$updates,$history=1) {
      global $DB, $LANG;

      if (in_array('is_helpdesk_default',$updates)) {
         if ($input["is_helpdesk_default"]) {
            $query = "UPDATE ".
                      $this->table."
                      SET `is_helpdesk_default` = '0'
                      WHERE `id` <> '".$input['id']."'";
            $DB->query($query);
         } else {
            addMessageAfterRedirect($LANG['setup'][313], true);
         }
      }
      if (in_array('is_mail_default',$updates)) {
         if ($input["is_mail_default"]) {
            $query = "UPDATE ".
                      $this->table."
                      SET `is_mail_default` = '0'
                      WHERE `id` <> '".$input['id']."'";
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