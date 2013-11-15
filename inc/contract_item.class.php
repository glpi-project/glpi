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

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

// Relation between Contracts and Items
class Contract_Item extends CommonDBRelation{

   // From CommonDBRelation
   static public $itemtype_1 = 'Contract';
   static public $items_id_1 = 'contracts_id';

   static public $itemtype_2 = 'itemtype';
   static public $items_id_2 = 'items_id';



   /**
    * @since version 0.84
   **/
   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }


   /**
    * Don't create a Contract_Item on contract that is alreay max used
    * Was previously done (until 0.83.*) by Contract_Item::can()
    *
    * @see CommonDBRelation::canCreateItem()
    *
    * @since version 0.84
   **/
   function canCreateItem() {

      // Try to load the contract
      $contract = $this->getConnexityItem(static::$itemtype_1, static::$items_id_1);
      if ($contract === false) {
         return false;
      }
      if (($contract->fields['max_links_allowed'] > 0)
          && (countElementsInTable($this->getTable(),
                                   "`contracts_id`='".$this->input['contracts_id']."'")
                >= $contract->fields['max_links_allowed'])) {
         return false;
      }

      return parent::canCreateItem();
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
         case 'items_id' :
            if (isset($values['itemtype']) && !empty($values['itemtype'])) {
               $options['name']  = $name;
               $options['value'] = $values[$field];
               return Dropdown::show($values['itemtype'], $options);
            }
            break;
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }


   function getSearchOptions() {

      $tab                        = array();

      $tab[2]['table']            = $this->getTable();
      $tab[2]['field']            = 'id';
      $tab[2]['name']             = __('ID');
      $tab[2]['massiveaction']    = false;
      $tab[2]['datatype']         = 'number';

      $tab[3]['table']            = $this->getTable();
      $tab[3]['field']            = 'items_id';
      $tab[3]['name']             = __('Associated item ID');
      $tab[3]['massiveaction']    = false;
      $tab[3]['datatype']         = 'specific';
      $tab[3]['additionalfields'] = array('itemtype');

      $tab[4]['table']            = $this->getTable();
      $tab[4]['field']            = 'itemtype';
      $tab[4]['name']             = __('Type');
      $tab[4]['massiveaction']    = false;
      $tab[4]['datatype']         = 'itemtypename';
      $tab[4]['itemtype_list']    = 'contract_types';

      return $tab;
   }


   /**
    * @param $item    CommonDBTM object
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
    * @since version 0.84
    *
    * @param $contract_id   contract ID
    * @param $entities_id   entity ID
    *
    * @return array of items linked to contracts
   **/
   static function getItemsForContract($contract_id, $entities_id) {
      global $DB;

      $items = array();

      $query = "SELECT DISTINCT `itemtype`
                FROM `glpi_contracts_items`
                WHERE `glpi_contracts_items`.`contracts_id` = '$contract_id'
                ORDER BY `itemtype`";

      $result = $DB->query($query);
      $number = $DB->numrows($result);

      $data    = array();
      $totalnb = 0;
      for ($i=0 ; $i<$number ; $i++) {
         $itemtype = $DB->result($result, $i, "itemtype");
         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }
         $itemtable = getTableForItemType($itemtype);
         $query     = "SELECT `$itemtable`.*,
                              `glpi_contracts_items`.`id` AS IDD,
                              `glpi_entities`.`id` AS entity
                        FROM `glpi_contracts_items`,
                              `$itemtable`";
         if ($itemtype != 'Entity') {
            $query .= " LEFT JOIN `glpi_entities`
                              ON (`$itemtable`.`entities_id`=`glpi_entities`.`id`) ";
         }
         $query .= " WHERE `$itemtable`.`id` = `glpi_contracts_items`.`items_id`
                           AND `glpi_contracts_items`.`itemtype` = '$itemtype'
                           AND `glpi_contracts_items`.`contracts_id` = '$contract_id'";

         if ($item->maybeTemplate()) {
            $query .= " AND `$itemtable`.`is_template` = '0'";
         }
         $query .= getEntitiesRestrictRequest(" AND",$itemtable, '', $entities_id,
                                                $item->maybeRecursive())."
                     ORDER BY `glpi_entities`.`completename`, `$itemtable`.`name`";

         $result_linked = $DB->query($query);
         $nb            = $DB->numrows($result_linked);

         while ($objdata = $DB->fetch_assoc($result_linked)) {
            $items[$itemtype][$objdata['id']] = $objdata;
         }
      }
      return $items;
   }


   /**
    * @see CommonGLPI::getTabNameForItem()
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
            self::showForContract($item);

         default :
            if (in_array($item->getType(), $CFG_GLPI["contract_types"])) {
               self::showForItem($item, $withtemplate);
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


   /**
    * Print an HTML array of contract associated to an object
    *
    * @since version 0.84
    *
    * @param $item            CommonDBTM object wanted
    * @param $withtemplate    not used (to be deleted) (default '')
    *
    * @return Nothing (display)
   **/
   static function showForItem(CommonDBTM $item, $withtemplate='') {
      global $DB, $CFG_GLPI;

      $itemtype = $item->getType();
      $ID       = $item->fields['id'];

      if (!Session::haveRight("contract","r") || !$item->can($ID,"r")) {
         return false;
      }

      $canedit = $item->can($ID,"w");
      $rand = mt_rand();

      $query = "SELECT `glpi_contracts_items`.*
                FROM `glpi_contracts_items`,
                     `glpi_contracts`
                LEFT JOIN `glpi_entities` ON (`glpi_contracts`.`entities_id`=`glpi_entities`.`id`)
                WHERE `glpi_contracts`.`id`=`glpi_contracts_items`.`contracts_id`
                      AND `glpi_contracts_items`.`items_id` = '$ID'
                      AND `glpi_contracts_items`.`itemtype` = '$itemtype'".
                      getEntitiesRestrictRequest(" AND","glpi_contracts",'','',true)."
                ORDER BY `glpi_contracts`.`name`";

      $result = $DB->query($query);

      $contracts = array();
      $used      = array();
      if ($number = $DB->numrows($result)) {
         while ($data = $DB->fetch_assoc($result)) {
            $contracts[$data['id']] = $data;
            $used[$data['id']]      = $data['id'];
         }
      }

      if ($canedit && ($withtemplate != 2)) {
         echo "<div class='firstbloc'>";
         echo "<form name='contractitem_form$rand' id='contractitem_form$rand' method='post'
                action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
         echo "<input type='hidden' name='items_id' value='$ID'>";
         echo "<input type='hidden' name='itemtype' value='$itemtype'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='2'>".__('Add a contract')."</th></tr>";

         echo "<tr class='tab_bg_1'><td class='right'>";
         Contract::dropdown(array('entity' => $item->getEntityID(),
                                  'used'   => $used));

         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";
      if ($withtemplate != 2) {
         if ($canedit && $number) {
            Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
            $massiveactionparams = array('num_displayed' => $number);
            Html::showMassiveActions(__CLASS__, $massiveactionparams);
         }
      }
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr>";
      if ($canedit && $number && ($withtemplate != 2)) {
         echo "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand)."</th>";
      }

      echo "<th>".__('Name')."</th>";
      echo "<th>".__('Entity')."</th>";
      echo "<th>"._x('phone', 'Number')."</th>";
      echo "<th>".__('Contract type')."</th>";
      echo "<th>".__('Supplier')."</th>";
      echo "<th>".__('Start date')."</th>";
      echo "<th>".__('Initial contract period')."</th>";
      echo "</tr>";

      if ($number > 0) {
         Session::initNavigateListItems(__CLASS__,
                              //TRANS : %1$s is the itemtype name,
                              //         %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'),
                                                $item->getTypeName(1), $item->getName()));
         foreach ($contracts as $data) {
            $cID         = $data["contracts_id"];
            Session::addToNavigateListItems(__CLASS__,$cID);
            $contracts[] = $cID;
            $assocID     = $data["id"];
            $con         = new Contract();
            $con->getFromDB($cID);
            echo "<tr class='tab_bg_1".($con->fields["is_deleted"]?"_2":"")."'>";
            if ($canedit && ($withtemplate != 2)) {
               echo "<td width='10'>";
               Html::showMassiveActionCheckBox(__CLASS__, $data["id"]);
               echo "</td>";
            }
            echo "<td class='center b'>";
            $name = $con->fields["name"];
            if ($_SESSION["glpiis_ids_visible"]
                || empty($con->fields["name"])) {
               $name = sprintf(__('%1$s (%2$s)'), $name, $con->fields["id"]);
            }
            echo "<a href='".$CFG_GLPI["root_doc"]."/front/contract.form.php?id=$cID'>".$name;
            echo "</a></td>";
            echo "<td class='center'>";
            echo Dropdown::getDropdownName("glpi_entities", $con->fields["entities_id"])."</td>";
            echo "<td class='center'>".$con->fields["num"]."</td>";
            echo "<td class='center'>";
            echo Dropdown::getDropdownName("glpi_contracttypes", $con->fields["contracttypes_id"]).
               "</td>";
            echo "<td class='center'>".$con->getSuppliersNames()."</td>";
            echo "<td class='center'>".Html::convDate($con->fields["begin_date"])."</td>";

            echo "<td class='center'>".sprintf(__('%1$s %2$s'), $con->fields["duration"],
                                               _n('month', 'months', $con->fields["duration"]));
            if (($con->fields["begin_date"] != '')
                && !empty($con->fields["begin_date"])) {
               echo " -> ".Infocom::getWarrantyExpir($con->fields["begin_date"],
                                                     $con->fields["duration"], 0, true);
            }
            echo "</td>";
            echo "</tr>";
         }
      }

      echo "</table>";
      if ($canedit && $number && ($withtemplate != 2)) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions(__CLASS__, $massiveactionparams);
         Html::closeForm();
      }
      echo "</div>";
   }


   /**
    * Print the HTML array for Items linked to current contract
    *
    * @since version 0.84
    *
    * @param $contract   Contract object
    *
    * @return Nothing (display)
   **/
   static function showForContract(Contract $contract) {
      global $DB, $CFG_GLPI;

      $instID = $contract->fields['id'];

      if (!$contract->can($instID,'r')) {
         return false;
      }
      $canedit = $contract->can($instID,'w');
      $rand    = mt_rand();

      $query = "SELECT DISTINCT `itemtype`
                FROM `glpi_contracts_items`
                WHERE `glpi_contracts_items`.`contracts_id` = '$instID'
                ORDER BY `itemtype`";

      $result = $DB->query($query);
      $number = $DB->numrows($result);

      $data = array();
      $totalnb = 0;
      for ($i=0 ; $i<$number ; $i++) {
         $itemtype = $DB->result($result, $i, "itemtype");
         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }
         if ($item->canView()) {
            $itemtable = getTableForItemType($itemtype);
            $query     = "SELECT `$itemtable`.*,
                                 `glpi_contracts_items`.`id` AS IDD,
                                 `glpi_entities`.`id` AS entity
                          FROM `glpi_contracts_items`,
                               `$itemtable`";
            if ($itemtype != 'Entity') {
               $query .= " LEFT JOIN `glpi_entities`
                                 ON (`$itemtable`.`entities_id`=`glpi_entities`.`id`) ";
            }
            $query .= " WHERE `$itemtable`.`id` = `glpi_contracts_items`.`items_id`
                              AND `glpi_contracts_items`.`itemtype` = '$itemtype'
                              AND `glpi_contracts_items`.`contracts_id` = '$instID'";

            if ($item->maybeTemplate()) {
               $query .= " AND `$itemtable`.`is_template` = '0'";
            }
            $query .= getEntitiesRestrictRequest(" AND",$itemtable, '', '',
                                                 $item->maybeRecursive())."
                      ORDER BY `glpi_entities`.`completename`, `$itemtable`.`name`";

            $result_linked = $DB->query($query);
            $nb            = $DB->numrows($result_linked);

            if ($nb > $_SESSION['glpilist_limit']) {
               $link = "<a href='". Toolbox::getItemTypeSearchURL($itemtype) . "?" .
                         rawurlencode("contains[0]") . "=" . rawurlencode('$$$$'.$instID) . "&amp;" .
                         rawurlencode("field[0]") . "=29&amp;sort=80&amp;order=ASC&amp;is_deleted=0".
                         "&amp;start=0". "'>" . __('Device list')."</a>";

               $data[$itemtype] = array('longlist' => true,
                                        'name'     => sprintf(__('%1$s: %2$s'),
                                                              $item->getTypeName($nb), $nb),
                                        'link'     => $link);
            } else if ($nb > 0) {
               for ($prem=true ; $objdata=$DB->fetch_assoc($result_linked) ; $prem=false) {
                  $data[$itemtype][$objdata['id']] = $objdata;
               }
            }
            $totalnb += $nb;
         }
      }

      if ($canedit
          && (($contract->fields['max_links_allowed'] == 0)
              || ($contract->fields['max_links_allowed'] > $totalnb))) {
         echo "<div class='firstbloc'>";
         echo "<form name='contract_form$rand' id='contract_form$rand' method='post'
                action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='2'>".__('Add an item')."</th></tr>";

         echo "<tr class='tab_bg_1'><td class='right'>";
         Dropdown::showAllItems("items_id", 0, 0,
                                ($contract->fields['is_recursive']?-1:$contract->fields['entities_id']),
                                $CFG_GLPI["contract_types"], false, true);
         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
         echo "<input type='hidden' name='contracts_id' value='$instID'>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";
      if ($canedit && $totalnb) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams = array();
         Html::showMassiveActions(__CLASS__, $massiveactionparams);
      }
      echo "<table class='tab_cadre_fixe'>";
       echo "<tr>";

      if ($canedit && $totalnb) {
         echo "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand)."</th>";
      }
      echo "<th>".__('Type')."</th>";
      echo "<th>".__('Entity')."</th>";
      echo "<th>".__('Name')."</th>";
      echo "<th>".__('Serial number')."</th>";
      echo "<th>".__('Inventory number')."</th>";
      echo "<th>".__('Status')."</th>";
      echo "</tr>";

      $totalnb = 0;
      foreach ($data as $itemtype => $datas) {

         if (isset($datas['longlist'])) {
            echo "<tr class='tab_bg_1'>";
            if ($canedit) {
               echo "<td>&nbsp;</td>";
            }
            echo "<td class='center'>".$datas['name']."</td>";
            echo "<td class='center' colspan='2'>".$datas['link']."</td>";
            echo "<td class='center'>-</td><td class='center'>-</td></tr>";

         } else {
            $prem = true;
            $nb   = count($datas);
            foreach ($datas as $id => $objdata) {
               $name = $objdata["name"];
               if ($_SESSION["glpiis_ids_visible"]
                   || empty($data["name"])) {
                  $name = sprintf(__('%1$s (%2$s)'), $name, $objdata["id"]);
               }
               $link = Toolbox::getItemTypeFormURL($itemtype);
               $name = "<a href=\"".$link."?id=".$objdata["id"]."\">".$name."</a>";

               echo "<tr class='tab_bg_1'>";
               if ($canedit) {
                  echo "<td width='10'>";
                  Html::showMassiveActionCheckBox(__CLASS__, $objdata["IDD"]);
                  echo "</td>";
               }
               if ($prem) {
                  $item     = new $itemtype();
                  $typename = $item->getTypeName($nb);
                  echo "<td class='center top' rowspan='$nb'>".
                         ($nb  >1 ? sprintf(__('%1$s: %2$s'), $typename, $nb): $typename)."</td>";
                  $prem = false;
               }
               echo "<td class='center'>";
               echo Dropdown::getDropdownName("glpi_entities",$objdata['entity'])."</td>";
               echo "<td class='center".
                      (isset($objdata['is_deleted']) && $objdata['is_deleted'] ? " tab_bg_2_2'" : "'");
               echo ">".$name."</td>";
               echo"<td class='center'>".
                      (isset($objdata["serial"])? "".$objdata["serial"]."" :"-")."</td>";
               echo "<td class='center'>".
                      (isset($objdata["otherserial"])? "".$objdata["otherserial"]."" :"-")."</td>";
               echo "<td class='center'>";
               if (isset($objdata["states_id"])) {
                  echo Dropdown::getDropdownName("glpi_states", $objdata['states_id']);
               } else {
                  echo '&nbsp;';
               }
               echo "</td></tr>";

            }
         }
      }
      echo "<tr class='tab_bg_2'>";
      echo "<td class='center' colspan='2'>".
            ($totalnb > 0 ? sprintf(__('%1$s = %2$s'), __('Total'), $totalnb) : "&nbsp;");
      echo "</td><td colspan='5'>&nbsp;</td></tr> ";

      echo "</table>";
      if ($canedit && $number) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions(__CLASS__, $massiveactionparams);
         Html::closeForm();
      }
      echo "</div>";
   }

}
?>