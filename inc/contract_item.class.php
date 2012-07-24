<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2012 by the INDEPNET Development Team.

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


   /**
    * Check right on an contract - overloaded to check max_links_allowed
    *
    * @param $ID              ID of the item (-1 if new item)
    * @param $right           Right to check : r / w / recursive
    * @param &$input    array of input data (used for adding item) (default NULL)
    *
    * @return boolean
   **/
   function can($ID, $right, array &$input=NULL) {

      if ($ID < 0) {
         // Ajout
         $contract = new Contract();

         if (!$contract->getFromDB($input['contracts_id'])) {
            return false;
         }
         if ($contract->fields['max_links_allowed'] > 0
             && countElementsInTable($this->getTable(),
                                     "`contracts_id`='".$input['contracts_id']."'")
                                       >= $contract->fields['max_links_allowed']) {
               return false;
         }
      }
      return parent::can($ID,$right,$input);
   }


   static function getTypeName($nb=0) {
      return _n('Link Contract/Item','Links Contract/Item',$nb);
   }

   static function getSpecificValueToDisplay($field, $values, array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      switch ($field) {
         case 'items_id':
            if (isset($values['itemtype'])) {
               if (isset($options['comments']) && $options['comments']) {
                  $tmp = Dropdown::getDropdownName(getTableForItemtype($values['itemtype']),
                                                   $values[$field], 1);
                  return sprintf(__('%1$s %2$s'), $tmp['name'],
                                 Html::showToolTip($tmp['comment'], array('display' => false)));

               }
               return Dropdown::getDropdownName(getTableForItemtype($values['itemtype']),
                                                $values[$field]);
            }
            break;
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }

   static function getSpecificValueToSelect($field, $name='', $values = '', array $options=array()) {
      if (!is_array($values)) {
         $values = array($field => $values);
      }
      $options['display'] = false;
      switch ($field) {
         case 'items_id' :
            if (isset($options['itemtype_used']) && !empty($options['itemtype_used'])) {
               $options['name']  = $name;
               $options['value'] = $values[$field];
               return Dropdown::show($options['itemtype_used'], $options);
            }
            break;
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }
   
   function getSearchOptions() {

      $tab                     = array();

      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'id';
      $tab[2]['name']          = __('ID');
      $tab[2]['massiveaction'] = false;
      $tab[2]['datatype']        = 'number';

      $tab[3]['table']         = $this->getTable();
      $tab[3]['field']         = 'items_id';
      $tab[3]['name']          = __('Associated item ID');
      $tab[3]['massiveaction'] = false;
      $tab[3]['datatype']      = 'specific';

      $tab[4]['table']         = $this->getTable();
      $tab[4]['field']         = 'itemtype';
      $tab[4]['name']          = __('Type');
      $tab[4]['massiveaction'] = false;
      $tab[4]['datatype']      = 'itemtypename';
      $tab[4]['itemtype_list'] = 'contract_types';

      return $tab;
   }


   /**
    * @pram $item    CommonDBTM object
   **/
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


   /**
    * @see inc/CommonGLPI::getTabNameForItem()
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $CFG_GLPI;

      // Can exists on template
      if (Session::haveRight("contract","r")) {
         switch ($item->getType()) {
            case 'Contract' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  return self::createTabEntry(_n('Item', 'Items', 2), self::countForContract($item));
               }
               return _n('Item', 'Items', 2);

            default :
               if ($_SESSION['glpishow_count_on_tabs']
                   && in_array($item->getType(), $CFG_GLPI["contract_types"])) {
                  return self::createTabEntry(Contract::getTypeName(2), self::countForItem($item));
               }
               return _n('Contract', 'Contracts', 2);

         }
      }
      return '';
   }


   /**
    * @param $item         CommonGLPI object
    * @param $tabnum       (default 1)
    * @param $withtemplate (default 0)
   **/
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


   /**
    * Duplicate contracts from an item template to its clone
    *
    * @since version 0.84
    *
    * @param $itemtype     itemtype of the item
    * @param $oldid        ID of the item to clone
    * @param $newid        ID of the item cloned
    * @param $newitemtype  itemtype of the new item (= $itemtype if empty) (default '')
   **/
   static function cloneItem($itemtype, $oldid, $newid, $newitemtype='') {
      global $DB;

      if (empty($newitemtype)) {
         $newitemtype = $itemtype;
      }

      $query  = "SELECT `contracts_id`
                 FROM `glpi_contracts_items`
                 WHERE `items_id` = '$oldid'
                        AND `itemtype` = '$itemtype';";

      foreach ($DB->request($query) as $data) {
         $contractitem = new self();
         $contractitem->add(array('contracts_id' => $data["contracts_id"],
                                  'itemtype'     => $newitemtype,
                                  'items_id'     => $newid));
      }
   }
}
?>