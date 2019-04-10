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
* */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/// Class FQDN : Fully Qualified Domain Name
/// since version 0.84
class FQDN extends CommonDropdown {

   public $dohistory = true;

   static $rightname = 'internet';

   public $can_be_translated = false;


   static function getTypeName($nb = 0) {
      return _n('Internet domain', 'Internet domains', $nb);
   }


   function getAdditionalFields() {

      return [['name'    => 'fqdn',
                         'label'   => __('FQDN'),
                         'type'    => 'text',
                         'comment'
                          => __('Fully Qualified Domain Name. Use the classical notation (labels separated by dots). For example: indepnet.net'),
                         'list'    => true]];
   }


   /**
    * \brief Prepare the input before adding or updating
    * Checking suppose that each FQDN is compose of dot separated array of labels and its unique
    * \see (FQDNLabel)
    *
    * @param $input fields of the record to check
    *
    * @return false or fields checks and update (lowercase for the fqdn field)
   **/
   function prepareInput($input) {

      if (isset($input['fqdn'])
          || $this->isNewID($this->getID())) {

         // Check that FQDN is not empty
         if (empty($input['fqdn'])) {
            Session::addMessageAfterRedirect(__('FQDN must not be empty'), false, ERROR);
            return false;
         }

         // Transform it to lower case
         $input["fqdn"] = strtolower($input['fqdn']);

         // Then check its validity
         if (!self::checkFQDN($input["fqdn"])) {
            Session::addMessageAfterRedirect(__('FQDN is not valid'), false, ERROR);
            return false;
         }

      }
      return $input;
   }


   function prepareInputForAdd($input) {
      return $this->prepareInput(parent::prepareInputForAdd($input));
   }


   function prepareInputForUpdate($input) {
      return $this->prepareInput(parent::prepareInputForUpdate($input));
   }


   function defineTabs($options = []) {

      $ong = [];
      $this->addStandardTab('NetworkName', $ong, $options);
      $this->addStandardTab('NetworkAlias', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   /**
    * @return the FQDN of the element, or "" if invalid FQDN
   **/
   function getFQDN() {

      if ($this->can($this->getID(), READ)) {
         return $this->fields["fqdn"];
      }
      return "";
   }


   /**
    * Search FQDN id from string FDQDN
    *
    * @param $fqdn            string   value of the fdqn (for instance : indeptnet.net)
    * @param $wildcard_search boolean  true if we search with wildcard (false by default)
    *
    * @return if $wildcard_search == false : the id of the fqdn, -1 if not found or several answers
    *         if $wildcard_search == true : an array of the id of the fqdn
   **/
   static function getFQDNIDByFQDN($fqdn, $wildcard_search = false) {
      global $DB;

      if (empty($fqdn)) {
         return 0;
      }

      $fqdn = strtolower($fqdn);
      if ($wildcard_search) {
         $count = 0;
         $fqdn  = str_replace('*', '%', $fqdn, $count);
         if ($count == 0) {
            $fqdn = '%'.$fqdn.'%';
         }
         $relation = ['LIKE', $fqdn];
      } else {
         $relation = $fqdn;
      }

      $iterator = $DB->request([
         'SELECT' => 'id',
         'FROM'   => self::getTable(),
         'WHERE'  => ['fqdn' => $relation]
      ]);

      $fqdns_id_list = [];
      while ($line = $iterator->next()) {
         $fqdns_id_list[] = $line['id'];
      }

      if (!$wildcard_search) {
         if (count($fqdns_id_list) != 1) {
            return -1;
         }
         return $fqdns_id_list[0];
      }

      return $fqdns_id_list;
   }


   /**
    * @param $ID id of the FQDN
    *
    * @return the FQDN of the element, or "" if invalid FQDN
   **/
   static function getFQDNFromID($ID) {

      $thisDomain = new self();
      if ($thisDomain->getFromDB($ID)) {
         return $thisDomain->getFQDN();
      }
      return "";
   }


   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'fqdn',
         'name'               => __('FQDN'),
         'datatype'           => 'string'
      ];

      return $tab;
   }


   /**
    * Check FQDN Validity
    *
    * @param $fqdn the FQDN to check
    *
    * @return true if the FQDN is valid
   **/
   static function checkFQDN($fqdn) {

      // The FQDN must be compose of several labels separated by dots '.'
      $labels = explode(".", $fqdn);
      foreach ($labels as $label) {
         if (($label == "") || (!FQDNLabel::checkFQDNLabel($label))) {
            return false;
         }
      }
      return true;
   }
}
