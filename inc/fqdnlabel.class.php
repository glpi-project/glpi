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
// Purpose of file: Create an abstration layer for any kind of internet label
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Class FQDNLabel - any kind of internet label (computer name as well as alias)
/// Since version 0.84
abstract class FQDNLabel extends CommonDBChild {
   // Inherits from CommonDBChild as it must be attached to a specific element
   // (NetworkName, NetworkPort, ...)


   function getInternetName() {

      // get the full computer name of the current object (for instance : forge.indepnet.net)
      return self::getInternetNameFromLabelAndDomainID($this->fields["name"],
                                                       $this->fields["fqdns_id"]);
   }


   /**
    * Get the internet name from a label and a domain ID
    *
    * @param $label (string) the label of the computer or its alias
    * @param $domain (integer) id of the domain that owns the item
    *
    * @return result the full internet name
    **/
   static function getInternetNameFromLabelAndDomainID($label, $domain) {

      $domainName = FQDN::getFQDNFromID($domain);
      if (!empty($domainName)) {
         return $label.".".$domainName;
      }
      return $label;
   }


   /**
    * \brief Check FQDN label
    * Check a label regarding section 6.1.3.5 of RFC 1123 : 63 lengths and no other characters
    * than alphanumerics. Minus ('-') is allowed if it is not at the end or begin of the lable.
    *
    * @param $label the label to check
    * @param $canBeEmpty true if empty label is valid (name of NetworkName or NetworkAlias)
    **/
   static function checkFQDNLabel($label, $canBeEmpty = false) {

      if (empty($label)) {
         return $canBeEmpty;
      }

      if (strlen($label) >= 63) {
         return false;
      }

      if (!preg_match("/^[0-9A-Za-z][0-9A-Za-z\-]*[0-9A-Za-z]+$/", $label, $regs)) {
         return false;
      }

      return true;
   }


   /**
    * \brief Check if a label is unique
    * The check concerns NetworkNames as well as Networkaliases. We must care about the current
    * object. Otherwise, unicity will answer false in case of updatint with the same label and
    * domain
    *
    * @param $label the label to check
    * @param $fqdns_id ID of the domain
    * @param $labeltype the type of the current Label
    * @param $labels_id the if of the current Label
    **/
   static function checkFQDNLabelUnicity($label, $fqdns_id, $labeltype, $labels_id) {

      foreach (self::getIDsByLabelAndFQDNID($label, $fqdns_id) as $class => $IDs) {
         // First, remove the current label from the list of answers
         if ($class == $labeltype) {
            $key = array_search ($labels_id, $IDs);
            if ($key !== false) {
               unset($IDs[$key]);
            }
         }
         if (count($IDs) > 0) {
            return false;
         }
      }
      return true;
   }


   function prepareLabelInput($input) {
      // Before adding a name, we must unsure its is valid : conform to RFC and is unique
      global $LANG;

      if (isset($input['name'])) {

         $input['name'] = strtolower ( $input['name'] ) ;

         if (!self::checkFQDNLabel($input['name'], true)) {
            Session::addMessageAfterRedirect($LANG['Internet'][11]." (".$input['name'].")",
                                             false, ERROR);
            return false;
         }

         // TODO : is it usefull to check label unicity in the current domain ?
         // $labels_id = (isset($input['id']) ? $input['id'] : -1);
         // if (!self::checkFQDNLabelUnicity($input['name'], $input["fqdns_id"],
         //                                  $this->getType(), $labels_id)) {
         //    Session::addMessageAfterRedirect($LANG['Internet'][12]." (".$input['name'].")",
         //                                     false, ERROR);
         //    return false;
         // }
      }

      return $input;
   }


   function prepareInputForAdd($input) {

      $input = $this->prepareLabelInput($input);

      if (!is_array($input)) {
         return false;
      }

      return parent::prepareInputForAdd($input);
   }


   function prepareInputForUpdate($input) {

      $input = $this->prepareLabelInput($input);

      if (!is_array($input)) {
         return false;
      }

      return parent::prepareInputForUpdate($input);
   }

   /**
    * Get all label IDs corresponding to given string label and FQDN ID
    *
    * @param $label string label to search for
    * @param $fqdns_id the id of the FQDN that owns the label
    *
    * @return array two arrays (NetworkName and NetworkAlias) of the IDs
    **/
   static function getIDsByLabelAndFQDNID($label, $fqdns_id) {
      global $DB;

      $IDs = array();
      foreach(array('NetworkName' => 'glpi_networknames', 'NetworkAlias' => 'glpi_networkaliases')
              as $class => $table) {
         $query = "SELECT `id`
                   FROM `$table`
                   WHERE `name` = '$label'
                   AND `fqdns_id` = '$fqdns_id'";

         foreach ($DB->request($query) as $element) {
            $IDs[$class][] = $element['id'];
         }
      }
      return $IDs;
   }

   /**
    * Look for "computer name" inside all databases
    *
    * @param $fqdn name to search (for instance : forge.indepnet.net)
    *
    * @return (array) each value of the array (corresponding to one NetworkPort) is an array of the
    *                 items from the master item to the NetworkPort
    **/
   static function getItemsByFQDN($fqdn) {
      $FQNDs_with_Items = array();

      if (!FQDN::checkFQDN($fqdn)) {
         return array();
      }

      $position = strpos( $fqdn, "." );
      $label    = strtolower( substr( $fqdn, 0, $position ));
      $fqdns_id = FQDN::getFQDNIDByFQDN( substr( $fqdn, $position + 1 ));

      if ($fqdns_id < 0) {
         return array();
      }

      foreach (self::getIDsByLabelAndFQDNID($fqdn, $fqdns_id) as $class => $IDs) {
         $FQDNlabel = new $class();
         foreach ($IDs as $ID) {
            if ($FQDNlabel->getFromDB($ID)) {
               $FQNDs_with_Items[] = array_merge(array_reverse($FQDNlabel->recursivelyGetItems()),
                                                 array(clone $FQDNlabel));
            }
         }
      }

      return $FQNDs_with_Items;
   }

   /**
    * Get an Object ID by its name (only if one result is found in the entity)
    *
    * @param $value the name
    * @param $entity the entity to look for
    *
    * @return an array containing the object ID
    *         or an empty array is no value of serverals ID where found
    **/
   static function getUniqueItemByFQDN($value, $entity) {

      $labels_with_items = self::getItemsByFQDN($value);

      if (count($labels_with_items) == 1) {
         $label_with_items = $labels_with_items[0];
         $item = $label_with_items[0];
         if ($item->getEntityID() == $entity) {
            $result = array("id"       => $item->getID(),
                            "itemtype" => $item->getType());
            unset($labels_with_items);
            return $result;
         }

      }

      return array();
   }
}
?>
