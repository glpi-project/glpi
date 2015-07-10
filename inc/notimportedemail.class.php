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
 * NotImportedEmail Class
**/
class NotImportedEmail extends CommonDBTM {

   static $rightname = 'config';

   const MATCH_NO_RULE = 0;
   const USER_UNKNOWN  = 1;
   const FAILED_INSERT = 2;


   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'delete';
      $forbidden[] = 'purge';
      $forbidden[] = 'restore';
      return $forbidden;
   }


   static function getTypeName($nb=0) {
      return _n('Refused email', 'Refused emails', $nb);
   }


   /**
    * @see CommonDBTM::getSpecificMassiveActions()
    **/
   function getSpecificMassiveActions($checkitem=NULL) {

      $isadmin = static::canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);

      if ($isadmin) {
         $prefix                          = __CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR;
         $actions[$prefix.'delete_email'] = __('Delete emails');
         $actions[$prefix.'import_email'] = _x('button', 'Import');
      }
      return $actions;
   }


   /**
    * @since version 0.85
    *
    * @see CommonDBTM::showMassiveActionsSubForm()
   **/
   static function showMassiveActionsSubForm(MassiveAction $ma) {

      switch ($ma->getAction()) {
         case 'import_email' :
            Entity::dropdown();
            echo "<br><br>";
            echo Html::submit(_x('button', 'Import'), array('name' => 'massiveaction'));
            return true;
      }
      return parent::showMassiveActionsSubForm($ma);
   }


   /**
    * @since version 0.85
    *
    * @see CommonDBTM::processMassiveActionsForOneItemtype()
   **/
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {

      switch ($ma->getAction()) {
         case 'delete_email' :
         case 'import_email' :
            if (!$item->canUpdate()) {
               $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_NORIGHT);
            } else {
               $input = $ma->getInput();
               if (count($ids)) {
                  $mailcollector = new MailCollector();
                  if ($ma->getAction() == 'delete_email') {
                     $mailcollector->deleteOrImportSeveralEmails($ids, 0);
                  } else {
                     $mailcollector->deleteOrImportSeveralEmails($ids, 1, $input['entities_id']);
                  }
               }
               $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_OK);
            }
            return;
      }
      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }


   function getSearchOptions() {

      $tab                       = array();

      $tab[1]['table']           = 'glpi_notimportedemails';
      $tab[1]['field']           = 'from';
      $tab[1]['name']            = __('From email header');
      $tab[1]['massiveaction']   = false;
      $tab[1]['datatype']        = 'string';

      $tab[2]['table']           = 'glpi_notimportedemails';
      $tab[2]['field']           = 'to';
      $tab[2]['name']            = __('To email header');
      $tab[2]['massiveaction']   = false;
      $tab[2]['datatype']        = 'string';

      $tab[3]['table']           = 'glpi_notimportedemails';
      $tab[3]['field']           = 'subject';
      $tab[3]['name']            = __('Subject email header');
      $tab[3]['massiveaction']   = false;
      $tab[3]['datatype']        = 'string';

      $tab[4]['table']           = 'glpi_mailcollectors';
      $tab[4]['field']           = 'name';
      $tab[4]['name']            = __('Mails receiver');
      $tab[4]['datatype']        = 'itemlink';

      $tab[5]['table']           = 'glpi_notimportedemails';
      $tab[5]['field']           = 'messageid';
      $tab[5]['name']            = __('Message-ID email header');
      $tab[5]['massiveaction']   = false;
      $tab[5]['datatype']        = 'string';

      $tab[6]['table']           = 'glpi_users';
      $tab[6]['field']           = 'name';
      $tab[6]['name']            = __('Requester');
      $tab[6]['datatype']        = 'dropdown';
      $tab[6]['right']           = 'all';

      $tab[16]['table']          = 'glpi_notimportedemails';
      $tab[16]['field']          = 'reason';
      $tab[16]['name']           = __('Reason of rejection');
      $tab[16]['datatype']       = 'specific';
      $tab[16]['massiveaction']  = false;

      $tab[19]['table']          = 'glpi_notimportedemails';
      $tab[19]['field']          = 'date';
      $tab[19]['name']           = __('Date');
      $tab[19]['datatype']       = 'datetime';
      $tab[19]['massiveaction']  = false;

      return $tab;
   }


   static function deleteLog() {
      global $DB;

      $query = "TRUNCATE `glpi_notimportedemails`";
      $DB->query($query);
   }


   /**
    * @param $reason_id
   **/
   static function getReason($reason_id) {

      $tab = self::getAllReasons();
      if (isset($tab[$reason_id])) {
         return $tab[$reason_id];
      }
      return NOT_AVAILABLE;
   }


   /**
    * @since versin 0.84
    *
    * Get All possible reasons array
   **/
   static function getAllReasons() {

      return array(self::MATCH_NO_RULE => __('Unable to affect the email to an entity'),
                   self::USER_UNKNOWN  => __('Email not found. Impossible import'),
                   self::FAILED_INSERT => __('Failed operation'));
   }


   /**
    * @since version 0.84
    *
    * @param $field
    * @param $values
    * @param $options   array
   **/
   static function getSpecificValueToDisplay($field, $values, array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      switch ($field) {
         case 'reason' :
            return self::getReason($values[$field]);

         case 'messageid' :
            $clean = array('<' => '',
                           '>' => '');
            return strtr($values[$field], $clean);
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   /**
    * @since version 0.84
    *
    * @param $field
    * @param $name               (default '')
    * @param $values             (default '')
    * @param $options      array
   **/
   static function getSpecificValueToSelect($field, $name='', $values='', array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      $options['display'] = false;

      switch ($field) {
         case 'reason' :
            $options['value'] = $values[$field];
            return Dropdown::showFromArray($name, self::getAllReasons(), $options);
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }


}
?>
