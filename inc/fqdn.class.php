<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
*/

// ----------------------------------------------------------------------
// Original Author of file: Damien Touraine
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Class FQDN : Fully Qualified Domain Name
/// since version 0.84
class FQDN extends CommonDropdown {

   public $dohistory = true;


   function canCreate() {
      return Session::haveRight('internet', 'w');
   }


   function canView() {
      return Session::haveRight('internet', 'r');
   }


   static function getTypeName($nb=0) {
      return _n('Internet domain', 'Internet domains', $nb);
   }


   function getAdditionalFields() {
      return array(array('name'    => 'fqdn',
                         'label'   => __('FQDN'),
                         'type'    => 'text',
                         'comment'
                          => __('Fully Qualified Domain Name. Use the classical notation (labels separated by dots). For example: indepnet.net'),
                         'list'    => true));
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

      // First, we check FQDN validity
      if (empty($input["fqdn"]) || !self::checkFQDN($input["fqdn"])) {
         Session::addMessageAfterRedirect(__('Internet domain name is invalid'), false, ERROR);
         return false;
      }

      // Then, we ensure the FQDN is unique
      if (isset($input["entities_id"])) {
         $entityID = $input["entities_id"];
      } else {
         $entityID = $_SESSION['glpiactive_entity'];
      }

      if (!self::checkFQDNUnicity($input["fqdn"], (isset($input["id"]) ? $input["id"] : -1),
                                  $entityID)) {
         Session::addMessageAfterRedirect(__('Internet domain already defined in the visible entities'),
                                          false, ERROR);
         return false;
      }

      $input["fqdn"] = strtolower($input["fqdn"]) ;

      return $input;
   }


   function prepareInputForAdd($input) {
      return $this->prepareInput(parent::prepareInputForAdd($input));
   }


   function prepareInputForUpdate($input) {
      return $this->prepareInput(parent::prepareInputForUpdate($input));
   }


   function defineTabs($options=array()) {

      $ong = array();
      $this->addStandardTab('NetworkName', $ong, $options);
      $this->addStandardTab('NetworkAlias', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   /**
    * Get SQL searching criterion to know if a NetworkName uses this as FQDN
    *
    * @return SQL request "WHERE" element
   **/
   function getCriterionForMatchingNetworkNames() {

      if (!empty($this->fields["id"])) {
         return "`fqdns_id` = '".$this->fields["id"]."'";
      }
      return "";
   }


   /**
    * @return the FQDN of the element, or "" if invalid FQDN
   **/
   function getFQDN() {

      if ($this->can($this->getID(), 'r')) {
         return $this->fields["fqdn"];
      }
      return "";
   }


   /**
    * Search FQDN id from string FDQDN
    *
    * @param $fqdn string value of the fdqn (for instance : indeptnet.net)
    *
    * @return the FQDN of the element, or "" if invalid FQDN
   **/
   static function getFQDNIDByFQDN($fqdn) {
      global $DB;

      if (empty($fqdn)) {
         return 0;
      }

      $query = "SELECT `id`
                FROM `glpi_fqdns`
                WHERE  `fqdn`='".strtolower ($fqdn)."'";

      $result = $DB->query($query);

      if ($DB->numrows($result) != 1) {
         return -1;
      }

      return $DB->result($result, 0, "id");
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


   function getSearchOptions() {

      $tab = parent::getSearchOptions();

      $tab[11]['table']    = $this->getTable();
      $tab[11]['field']    = 'fqdn';
      $tab[11]['name']     = __('FQDN');

      return $tab;
   }


   /**
    * Check to see if an FQDN is unique
    *
    * @param $fqdn      the FQDN to check
    * @param $ID        its id to don't check itself
    * @param $entityID  the entity containing the FQDN
    *
    * We check all the entities that are visible.
    * That allows two sisters entities to define the same FQDN
    *
    * @return boolean value : true if already found
   **/
   static function checkFQDNUnicity($fqdn, $ID, $entityID) {
      global $DB;

      $entitiesID = getSonsAndAncestorsOf('glpi_entities', $entityID);
      $query      = "SELECT `id`
                     FROM `glpi_fqdns`
                     WHERE `fqdn` = '$fqdn'
                           AND `entities_id` IN ('".implode("', '", $entitiesID)."')";

      if ($ID > 0) {
         $query .= "\nAND `id` <> '$ID'";
      }
      $result = $DB->query($query);
      if ($DB->numrows($result) > 0) {
         return false;
      }
      return true;
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
      $labels = explode("." , $fqdn);
      foreach ($labels as $label) {
         if (($label == "") || (!FQDNLabel::checkFQDNLabel($label))) {
            return false;
         }
      }
      return true;
   }
}
?>
