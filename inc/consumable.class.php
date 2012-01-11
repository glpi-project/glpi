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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

//!  Consumable Class
/**
  This class is used to manage the consumables.
  @see ConsumableItem
  @author Julien Dombre
 */
class Consumable extends CommonDBTM {

   // From CommonDBTM
   protected $forward_entity_to = array('Infocom');

   var $no_form_page            = false;


   static function getTypeName($nb=0) {
      return _n('Consumable', 'Consumables', $nb);
   }


   function canCreate() {
      return Session::haveRight('consumable', 'w');
   }


   function canView() {
      return Session::haveRight('consumable', 'r');
   }


   function cleanDBonPurge() {
      global $DB;

      $query = "DELETE
                FROM `glpi_infocoms`
                WHERE (`items_id` = '".$this->fields['id']."'
                       AND `itemtype` = '".$this->getType()."')";
      $result = $DB->query($query);
   }


   function prepareInputForAdd($input) {

      $item = new ConsumableItem();
      if ($item->getFromDB($input["tID"])) {
         return array("consumableitems_id" => $item->fields["id"],
                      "entities_id"        => $item->getEntityID(),
                      "date_in"            => date("Y-m-d"));
      }
      return array();
   }


   function post_addItem() {

      $ic = new Infocom();
      $ic->cloneItem('ConsumableItem', $this->fields["consumableitems_id"], $this->fields['id'],
                     $this->getType());
   }


   function restore($input, $history=1) {
      global $DB;

      $query = "UPDATE `".$this->getTable()."`
                SET `date_out` = NULL
                WHERE `id` = '".$input["id"]."'";

      if ($result = $DB->query($query)) {
         return true;
      }
      return false;
   }


   /**
    * UnLink a consumable linked to a printer
    *
    * UnLink the consumable identified by $ID
    *
    * @param $ID           consumable identifier
    * @param $itemtype     itemtype of who we give the consumable (default '')
    * @param $items_id     ID of the item giving the consumable (default 0)
    *
    * @return boolean
   **/
   function out($ID, $itemtype='', $items_id=0) {
      global $DB;

      if (!empty($itemtype) && $items_id > 0) {
         $query = "UPDATE `".$this->getTable()."`
                   SET `date_out` = '".date("Y-m-d")."',
                       `itemtype` = '$itemtype',
                       `items_id` = '$items_id'
                   WHERE `id` = '$ID'";

         if ($result = $DB->query($query)) {
            return true;
         }
      }
      return false;
   }


   /**
    * count how many consumable for the consumable item $tID
    *
    * @param $tID integer  consumable item identifier.
    *
    * @return integer : number of consumable counted.
    **/
   static function getTotalNumber($tID) {
      global $DB;

      $query = "SELECT `id`
                FROM `glpi_consumables`
                WHERE `consumableitems_id` = '$tID'";
      $result = $DB->query($query);

      return $DB->numrows($result);
   }


   /**
    * count how many old consumable for the consumable item $tID
    *
    * @param $tID integer  consumable item identifier.
    *
    * @return integer : number of old consumable counted.
   **/
   static function getOldNumber($tID) {
      global $DB;

      $query = "SELECT `id`
                FROM `glpi_consumables`
                WHERE (`consumableitems_id` = '$tID'
                       AND `date_out` IS NOT NULL)";
      $result = $DB->query($query);

      return $DB->numrows($result);
   }


   /**
    * count how many consumable unused for the consumable item $tID
    *
    * @param $tID integer  consumable item identifier.
    *
    * @return integer : number of consumable unused counted.
   **/
   static function getUnusedNumber($tID) {
      global $DB;

      $query = "SELECT `id`
                FROM `glpi_consumables`
                WHERE (`consumableitems_id` = '$tID'
                       AND `date_out` IS NULL)";
      $result = $DB->query($query);

      return $DB->numrows($result);
   }


   /**
    * Get the consumable count HTML array for a defined consumable type
    *
    * @param $tID             integer  consumable item identifier.
    * @param $alarm_threshold integer  threshold alarm value.
    * @param $nohtml          integer  Return value without HTML tags. (default 0)
    *
    * @return string to display
   **/
   static function getCount($tID, $alarm_threshold, $nohtml=0) {

      // Get total
      $total = self::getTotalNumber($tID);

      if ($total!=0) {
         $unused = self::getUnusedNumber($tID);
         $old    = self::getOldNumber($tID);

         $highlight="";
         if ($unused<=$alarm_threshold) {
            $highlight = "class='tab_bg_1_2'";
         }
         //TRANS: %1$d is total number, %2$d is unused number, %3$d is old number
         $tmptxt = sprintf(__('Total: %1$d, New: %2$d, Used: %3$d'), $total, $unused, $old);
         if ($nohtml) {
            $out = $tmptxt;
         } else {
            $out = "<div $highlight>".$tmptxt."</div";
         }
      } else {
         if (!$nohtml) {
            $out = __('No consumable');
         } else {
            $out = "<div class='tab_bg_1_2'><i>".__('No consumable')."</i></div>";
         }
      }
      return $out;
   }


   /**
    * Check if a Consumable is New (not used, in stock)
    *
    * @param $cID integer  consumable ID.
   **/
   static function isNew($cID) {
      global $DB;

      $query = "SELECT `id`
                FROM `glpi_consumables`
                WHERE (`id` = '$cID'
                       AND `date_out` IS NULL)";
      $result = $DB->query($query);

      return ($DB->numrows($result) == 1);
   }


   /**
    * Check if a consumable is Old (used, not in stock)
    *
    * @param $cID integer  consumable ID.
   **/
   static function isOld($cID) {
      global $DB;

      $query = "SELECT `id`
                FROM `glpi_consumables`
                WHERE (`id` = '$cID'
                       AND `date_out` IS NOT NULL)";
      $result = $DB->query($query);

      return ($DB->numrows($result) == 1);
   }


   /**
    * Get the localized string for the status of a consumable
    *
    * @param $cID integer  consumable ID.
    *
    * @return string : dict value for the consumable status.
   **/
   static function getStatus($cID) {

      if (self::isNew($cID)) {
         return __('New');

      } else if (self::isOld($cID)) {
         return __('Used');
      }
   }


   /**
    * Print out a link to add directly a new consumable from a consumable item.
    *
    * @param $consitem  ConsumableItem object
    *
    * @return Nothing (displays)
   **/
   static function showAddForm(ConsumableItem $consitem) {
      global $CFG_GLPI;

      $ID = $consitem->getField('id');

      if (!$consitem->can($ID,'w')) {
         return false;
      }

      if ($ID > 0) {
         echo "<div class='firstbloc'>";
         echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/front/consumable.form.php\">";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><td class='tab_bg_2 center'>";
         echo "<input type='hidden' name='tID' value='$ID'>\n";
         Dropdown::showInteger('to_add',1,1,100);
         echo " <input type='submit' name='add_several' value=\"".__s('Add consumables')."\"
                class='submit'>";
         echo "</td></tr>";
         echo "</table></form></div>";
      }
   }


   /**
    * Print out the consumables of a defined type
    *
    * @param $consitem           ConsumableItem object
    * @param $show_old  boolean  show old consumables or not. (default 0)
    *
    * @return Nothing (displays)
   **/
   static function showForConsumableItem(ConsumableItem $consitem, $show_old=0) {
      global $DB, $CFG_GLPI;

      $tID = $consitem->getField('id');
      if (!$consitem->can($tID,'r')) {
         return false;
      }
      $canedit = $consitem->can($tID,'w');

      $query = "SELECT COUNT(*)
                FROM `glpi_consumables`
                WHERE (`consumableitems_id` = '$tID')";

      if ($result = $DB->query($query)) {
         if (!$show_old && $canedit) {
            echo "<form method='post' action='".$CFG_GLPI["root_doc"]."/front/consumable.form.php'>";
            echo "<input type='hidden' name='tID' value='$tID'>\n";
         }
         echo "<div class='spaced'><table class='tab_cadre_fixe'>";
         if (!$show_old) {
            echo "<tr><th colspan='7'>";
            echo self::getCount($tID, -1);
            echo "</th></tr>";
         } else { // Old
            echo "<tr><th colspan='8'>".__('Used consumables')."</th></tr>";
         }
         $i = 0;
         echo "<tr><th>".__('ID')."</th><th>".__('State')."</th>";
         echo "<th>".__('Add date')."</th><th>".__('Use date')."</th>";
         if ($show_old) {
            echo "<th>".__('Give to')."</th>";
         }
         echo "<th width='200px'>".__('Financial and administrative information')."</th>";

         if (!$show_old && $canedit && $DB->result($result,0,0)!=0) {
            echo "<th colspan='".($canedit?'2':'1')."'>";

            Dropdown::showAllItems("items_id", 0, 0,$consitem->fields["entities_id"],
                                   $CFG_GLPI["consumables_types"]);

/*            User::dropdown(array('value'  => $consitem->fields["entities_id"],
                                 'right'  => 'all'));*/
            echo "&nbsp;<input type='submit' class='submit' name='give' value='".__s('Give')."'>";
            echo "</th>";
         } else {
            echo "<th colspan='".($canedit?'2':'1')."'>&nbsp;</th>";
         }
         echo "</tr>";

      }

      $where     = "";
      if (!$show_old) { // NEW
         $where = " AND `date_out` IS NULL
                  ORDER BY `date_in`, `id`";
      } else { //OLD
         $where = " AND `date_out` IS NOT NULL
                  ORDER BY `date_out` DESC,
                           `date_in`,
                           `id`";
      }
      $query = "SELECT `glpi_consumables`.*
                FROM `glpi_consumables`
                WHERE `consumableitems_id` = '$tID'
                      $where";

      if ($result = $DB->query($query)) {
         $number = $DB->numrows($result);
         while ($data=$DB->fetch_array($result)) {
            $date_in  = Html::convDate($data["date_in"]);
            $date_out = Html::convDate($data["date_out"]);

            echo "<tr class='tab_bg_1'><td class='center'>".$data["id"]."</td>";
            echo "<td class='center'>".self::getStatus($data["id"])."</td>";
            echo "<td class='center'>".$date_in."</td>";
            echo "<td class='center'>".$date_out."</td>";

            if ($show_old) {
               echo "<td class='center'>";
               if ($item = getItemForItemtype($data['itemtype'])
                   && $item->getFromDB($data['items_id'])) {
                  echo $item->getLink();
               }
               echo "</td>";
            }
            echo "<td class='center'>";
            Infocom::showDisplayLink('Consumable', $data["id"],1);
            echo "</td>";

            if (!$show_old && $canedit) {
               echo "<td class='center'>";
               echo "<input type='checkbox' name='out[".$data["id"]."]'>";
               echo "</td>";
            }
            if ($show_old && $canedit) {
               echo "<td class='center'>";
               echo "<a href='".
                      $CFG_GLPI["root_doc"]."/front/consumable.form.php?restore=restore&amp;id=".
                      $data["id"]."&amp;tID=$tID'>".__('Back to stock')."</a>";
               echo "</td>";
            }
            echo "<td class='center'>";
            echo "<a href='".
                   $CFG_GLPI["root_doc"]."/front/consumable.form.php?delete=delete&amp;id=".
                   $data["id"]."&amp;tID=$tID'>".__('Delete')."</a>";
            echo "</td></tr>";
         }
      }
      echo "</table></div>";
      if (!$show_old && $canedit) {
         echo "</form>";
      }
   }


   /**
    * Show the usage summary of consumables by user
    **/
   static function showSummary() {
      global $DB;

      if (!Session::haveRight("consumable","r")) {
         return false;
      }

      $query = "SELECT COUNT(*) AS count, `consumableitems_id`, `itemtype`, `items_id`
                FROM `glpi_consumables`
                WHERE `date_out` IS NOT NULL
                      AND `consumableitems_id` IN (SELECT `id`
                                                   FROM `glpi_consumableitems`
                                                   ".getEntitiesRestrictRequest("WHERE",
                                                                           "glpi_consumableitems").")
                GROUP BY `itemtype`, `items_id`, `consumableitems_id`";
      $used = array();

      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)) {
            while ($data=$DB->fetch_array($result)) {
               $used[$data['itemtype'].'####'.$data['items_id']][$data["consumableitems_id"]]
                  = $data["count"];
            }
         }
      }
      $query = "SELECT COUNT(*) AS count, `consumableitems_id`
                FROM `glpi_consumables`
                WHERE `date_out` IS NULL
                      AND `consumableitems_id` IN (SELECT `id`
                                                   FROM `glpi_consumableitems`
                                                   ".getEntitiesRestrictRequest("WHERE",
                                                                           "glpi_consumableitems").")
                GROUP BY `consumableitems_id`";
      $new = array();

      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)) {
            while ($data=$DB->fetch_array($result)) {
               $new[$data["consumableitems_id"]] = $data["count"];
            }
         }
      }

      $types = array();
      $query = "SELECT *
                FROM `glpi_consumableitems`
                ".getEntitiesRestrictRequest("WHERE","glpi_consumableitems");

      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)) {
            while ($data=$DB->fetch_array($result)) {
               $types[$data["id"]] = $data["name"];
            }
         }
      }
      asort($types);
      $total = array();
      if (count($types)>0) {
         // Produce headline
         echo "<div class='center'><table class='tab_cadrehov'><tr>";

         // Type
         echo "<th>".__('Give to')."</th>";

         foreach ($types as $key => $type) {
            echo "<th>$type</th>";
            $total[$key] = 0;
         }
         echo "<th>".__('Total')."</th>";
         echo "</tr>";

         // new
         echo "<tr class='tab_bg_2'><td class='b'>".__('In stock')."</td>";
         $tot = 0;
         foreach ($types as $id_type => $type) {
            if (!isset($new[$id_type])) {
               $new[$id_type] = 0;
            }
            echo "<td class='center'>".$new[$id_type]."</td>";
            $total[$id_type] += $new[$id_type];
            $tot += $new[$id_type];
         }
         echo "<td class='center'>".$tot."</td>";
         echo "</tr>";

         foreach ($used as $itemtype_items_id => $val) {
            echo "<tr class='tab_bg_2'><td>";
            list($itemtype,$items_id) = explode('####',$itemtype_items_id);
            $item = new $itemtype();
            if ($item->getFromDB($items_id)) {
               echo $item->getTypeName().' - '.$item->getNameID();
            }
            echo "</td>";
            $tot = 0;
            foreach ($types as $id_type => $type) {
               if (!isset($val[$id_type])) {
                  $val[$id_type] = 0;
               }
               echo "<td class='center'>".$val[$id_type]."</td>";
               $total[$id_type] += $val[$id_type];
               $tot += $val[$id_type];
            }
         echo "<td class='center'>".$tot."</td>";
         echo "</tr>";
         }
         echo "<tr class='tab_bg_1'><td class='b'>".__('Total')."</td>";
         $tot = 0;
         foreach ($types as $id_type => $type) {
            $tot += $total[$id_type];
            echo "<td class='center'>".$total[$id_type]."</td>";
         }
         echo "<td class='center'>".$tot."</td>";
         echo "</tr>";
         echo "</table></div>";

      } else {
         echo "<div class='center b'>".__('No consumable found')."</div>";
      }
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate && Session::haveRight("consumable","r")) {
         switch ($item->getType()) {
            case 'ConsumableItem' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  return self::createTabEntry(self::getTypeName(2),
                                              self::countForConsumableItem($item));
               }
               return self::getTypeName(2);
         }
      }
      return '';
   }


   /**
    * @param $item   string  ConsumableItem object
   **/
   static function countForConsumableItem(ConsumableItem $item) {

      $restrict = "`glpi_consumables`.`consumableitems_id` = '".$item->getField('id') ."'";

      return countElementsInTable(array('glpi_consumables'), $restrict);
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

         switch ($item->getType()) {
            case 'ConsumableItem' :
               self::showAddForm($item);
               self::showForConsumableItem($item);
               self::showForConsumableItem($item, 1);
               return true;
         }
   }

}
?>