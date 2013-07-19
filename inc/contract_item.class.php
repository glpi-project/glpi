<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

// Relation between Contracts and Items
class Contract_Item extends CommonDBRelation{

   // From CommonDBRelation
   public $itemtype_1 = 'Contract';
   public $items_id_1 = 'contracts_id';

   public $itemtype_2 = 'itemtype';
   public $items_id_2 = 'items_id';

   var $no_form_page = true;
 
   /**
    * Check right on an contract - overloaded to check max_links_allowed
    *
    * @param $ID ID of the item (-1 if new item)
    * @param $right Right to check : r / w / recursive
    * @param $input array of input data (used for adding item)
    *
    * @return boolean
   **/
   function can($ID, $right, &$input=NULL) {

      if ($ID<0) {
         // Ajout
         $contract = new Contract();

         if (!$contract->getFromDB($input['contracts_id'])) {
            return false;
         }
         if ($contract->fields['max_links_allowed'] > 0
             && countElementsInTable($this->getTable(), "`contracts_id`='".$input['contracts_id']."'")
                                     >= $contract->fields['max_links_allowed']) {
               return false;
         }
      }
      return parent::can($ID,$right,$input);
   }


   static function getTypeName($nb=0) {
      global $LANG;

      if ($nb>1) {
         return $LANG['setup'][624].'-'.$LANG['financial'][1];
      }
      return $LANG['setup'][620].'-'.$LANG['financial'][1];
   }


   function getSearchOptions() {
      global $LANG;

      $tab = array();

      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'id';
      $tab[2]['name']          = $LANG['common'][2];
      $tab[2]['massiveaction'] = false;

      $tab[3]['table']         = $this->getTable();
      $tab[3]['field']         = 'items_id';
      $tab[3]['name']          = $LANG['common'][2].' '.$LANG['financial'][104];
      $tab[3]['massiveaction'] = false;

      $tab[4]['table']         = $this->getTable();
      $tab[4]['field']         = 'itemtype';
      $tab[4]['name']          = $LANG['common'][17];
      $tab[4]['massiveaction'] = false;

      return $tab;
   }


   static function countForItem(CommonDBTM $item) {

      return countElementsInTable('glpi_contracts_items',
                                  "`itemtype` = '".$item->getType()."'
                                   AND `items_id` ='".$item->getField('id')."'");
   }


   /**
    * @param $item   Contract object
   **/
   static function countForContract(Contract $item) {

      $restrict = "`glpi_contracts_items`.`contracts_id` = '".$item->getField('id')."'";

      return countElementsInTable(array('glpi_contracts_items'), $restrict);
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG, $CFG_GLPI;

      // Can exists on template
      if (Session::haveRight("contract","r")) {
         switch ($item->getType()) {
            case 'Contract' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  return self::createTabEntry($LANG['common'][96], self::countForContract($item));
               }
               return $LANG['common'][96];

            default :
               if ($_SESSION['glpishow_count_on_tabs']
                   && in_array($item->getType(), $CFG_GLPI["contract_types"])) {
                  return self::createTabEntry($LANG['Menu'][25], self::countForItem($item));
               }
               return $LANG['Menu'][25];

         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      global $CFG_GLPI;

      switch ($item->getType()) {
         case 'Contract' :
            $item->showItems();

         default :
            if (in_array($item->getType(), $CFG_GLPI["contract_types"])) {
               Contract::showAssociated($item, $withtemplate);
            }
      }
      return true;
   }

}

?>