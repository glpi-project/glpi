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
* @brief Create an abstration layer for any kind of internet label
*/

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
    * @param $label   string   the label of the computer or its alias
    * @param $domain  integer  id of the domain that owns the item
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
    * Check a label regarding section 2.1 of RFC 1123 : 63 lengths and no other characters
    * than alphanumerics. Minus ('-') is allowed if it is not at the end or begin of the lable.
    *
    * @param $label        the label to check
   **/
   static function checkFQDNLabel($label) {

      if (strlen($label) >= 63) {
         return false;
      }

      if (strlen($label) == 1) {
         if (!preg_match("/^[0-9A-Za-z]$/", $label, $regs)) {
            return false;
         }
      } else if (!preg_match("/^[0-9A-Za-z][0-9A-Za-z\-]*[0-9A-Za-z]+$/", $label, $regs)) {
         return false;
      }

      return true;
   }


   /**
    * @param $input
   **/
   function prepareLabelInput($input) {

      if (isset($input['name']) && !empty($input['name'])) {
         // Empty names are allowed

         $input['name'] = strtolower($input['name']);

         // Before adding a name, we must unsure its is valid : it conforms to RFC
         if (!self::checkFQDNLabel($input['name'])) {
            Session::addMessageAfterRedirect(sprintf(__('Invalid internet name: %s'),
                                                     $input['name']),
                                             false, ERROR);
            return false;
         }

      }
      return $input;
   }


   function prepareInputForAdd($input) {
      return parent::prepareInputForAdd($this->prepareLabelInput($input));
   }


   function prepareInputForUpdate($input) {
      return parent::prepareInputForUpdate($this->prepareLabelInput($input));
   }


   /**
    * Get all label IDs corresponding to given string label and FQDN ID
    *
    * @param $label           string   label to search for
    * @param $fqdns_id        integer  the id of the FQDN that owns the label
    * @param $wildcard_search boolean  true if we search with wildcard (false by default)
    *
    * @return array two arrays (NetworkName and NetworkAlias) of the IDs
    **/
   static function getIDsByLabelAndFQDNID($label, $fqdns_id, $wildcard_search=false) {
      global $DB;

      $label = strtolower($label);
      if ($wildcard_search) {
         $count = 0;
         $label = str_replace('*', '%', $label, $count);
         if ($count == 0) {
            $label = '%'.$label.'%';
         }
         $relation = "LIKE '$label'";
      } else {
         $relation = "= '$label'";
      }

      $IDs = array();
      foreach (array('NetworkName'  => 'glpi_networknames',
                     'NetworkAlias' => 'glpi_networkaliases') as $class => $table) {
         $query = "SELECT `id`
                   FROM `$table`
                   WHERE `name` $relation ";

         if ((is_array($fqdns_id)) && (count($fqdns_id) > 0)) {
            $query .= " AND `fqdns_id` IN ('". implode('\', \'', $fqdns_id). "')";
         } else if ((is_int($fqdns_id)) && ($fqdns_id > 0)) {
            $query .= " AND `fqdns_id` = '$fqdns_id'";
         }

         foreach ($DB->request($query) as $element) {
            $IDs[$class][] = $element['id'];
         }
      }
      return $IDs;
   }


   /**
    * Look for "computer name" inside all databases
    *
    * @param $fqdn                     name to search (for instance : forge.indepnet.net)
    * @param $wildcard_search boolean  true if we search with wildcard (false by default)
    *
    * @return (array) each value of the array (corresponding to one NetworkPort) is an array of the
    *                 items from the master item to the NetworkPort
    **/
   static function getItemsByFQDN($fqdn, $wildcard_search=false) {

      $FQNDs_with_Items = array();

      if (!$wildcard_search) {
         if (!FQDN::checkFQDN($fqdn)) {
            return array();
         }
      }

      $position = strpos($fqdn, ".");
      if ($position !== false) {
         $label    = strtolower(substr( $fqdn, 0, $position));
         $fqdns_id = FQDN::getFQDNIDByFQDN(substr( $fqdn, $position + 1), $wildcard_search);
      } else {
         $label    = $fqdn;
         $fqdns_id = -1;
      }

      foreach (self::getIDsByLabelAndFQDNID($label, $fqdns_id, $wildcard_search) as $class => $IDs) {
         if ($FQDNlabel = getItemForItemtype($class)) {
            foreach ($IDs as $ID) {
               if ($FQDNlabel->getFromDB($ID)) {
                  $FQNDs_with_Items[] = array_merge(array_reverse($FQDNlabel->recursivelyGetItems()),
                                                    array(clone $FQDNlabel));
               }
            }
         }
      }

      return $FQNDs_with_Items;
   }


   /**
    * Get an Object ID by its name (only if one result is found in the entity)
    *
    * @param $value  the name
    * @param $entity the entity to look for
    *
    * @return an array containing the object ID
    *         or an empty array is no value of serverals ID where found
    **/
   static function getUniqueItemByFQDN($value, $entity) {

      $labels_with_items = self::getItemsByFQDN($value);
      // Filter : Do not keep ip not linked to asset
      if (count($labels_with_items)) {
         foreach ($labels_with_items as $key => $tab) {
            if (isset($tab[0])
                && (($tab[0] instanceof NetworkName)
                    || ($tab[0] instanceof NetworkPort)
                    || $tab[0]->isDeleted()
                    || $tab[0]->isTemplate()
                    || ($tab[0]->getEntityID() != $entity))) {
               unset($labels_with_items[$key]);
            }
         }
      }

      if (count($labels_with_items) == 1) {
         $label_with_items = current($labels_with_items);
         $item             = $label_with_items[0];
         $result           = array("id"       => $item->getID(),
                                   "itemtype" => $item->getType());
         unset($labels_with_items);
         return $result;
      }

      return array();
   }
}
?>
