<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
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
   protected $forward_entity_to=array('Infocom');

   static function getTypeName() {
      global $LANG;

      return $LANG['consumables'][0];
   }

   function canCreate() {
      return haveRight('consumable', 'w');
   }

   function canView() {
      return haveRight('consumable', 'r');
   }

   function cleanDBonPurge() {
      global $DB;

      $query = "DELETE
                FROM `glpi_infocoms`
                WHERE (`items_id` = '".$this->fields['id']."'
                       AND `itemtype`='".$this->getType()."')";
      $result = $DB->query($query);
   }

   function prepareInputForAdd($input) {
      $item=new ConsumableItem();
      if ($item->getFromDB($input["tID"])) {
         return array("consumableitems_id"=>$item->fields["id"],
                     "entities_id"=>$item->getEntityID(),
                     "date_in"=>date("Y-m-d"));
      } else {
         return array();
      }
   }

   function post_addItem() {

      // Add infocoms if exists for the licence
      $ic=new Infocom();

      if ($ic->getFromDBforDevice('ConsumableItem',$this->fields["consumableitems_id"])) {
         unset($ic->fields["id"]);
         $ic->fields["items_id"]=$this->fields['id'];
         $ic->fields["itemtype"]=$this->getType();
         if (empty($ic->fields['use_date'])) {
            unset($ic->fields['use_date']);
         }
         if (empty($ic->fields['buy_date'])) {
            unset($ic->fields['buy_date']);
         }
         $ic->addToDB();
      }
   }

   function restore($input,$history=1) {
      global $DB;

      $query = "UPDATE
                `".$this->getTable()."`
                SET `date_out` = NULL
                WHERE `id`='".$input["id"]."'";

      if ($result = $DB->query($query)) {
         return true;
      } else {
         return false;
      }
   }

   /**
    * UnLink a consumable linked to a printer
    *
    * UnLink the consumable identified by $ID
    *
    *@param $ID : consumable identifier
    *@param $users_id : ID of the user giving the consumable
    *
    *@return boolean
    *
    **/
   function out($ID,$users_id=0) {
      global $DB;

      $query = "UPDATE
                `".$this->getTable()."`
                SET `date_out` = '".date("Y-m-d")."',
                    `users_id` = '$users_id'
                WHERE `id` = '$ID'";

      if ($result = $DB->query($query)) {
         return true;
      } else {
         return false;
      }
   }


//    function isEntityAssign() {
//       return true;
//    }

   /**
    * Get the ID of entity assigned to the Consumable
    *
    * @return ID of the entity
   **/
//    function getEntityID () {
//       $ci=new ConsumableItem();
//       $ci->getFromDB($this->fields["consumableitems_id"]);
//
//       return $ci->getEntityID();
//    }

   /**
    * count how many consumable for a consumable type
    *
    * count how many consumable for the consumable item $tID
    *
    *@param $tID integer: consumable item identifier.
    *
    *@return integer : number of consumable counted.
    *
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
    * count how many old consumable for a consumable type
    *
    * count how many old consumable for the consumable item $tID
    *
    *@param $tID integer: consumable item identifier.
    *
    *@return integer : number of old consumable counted.
    *
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
    * count how many consumable unused for a consumable type
    *
    * count how many consumable unused for the consumable item $tID
    *
    *@param $tID integer: consumable item identifier.
    *
    *@return integer : number of consumable unused counted.
    *
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
    * @param $tID integer: consumable item identifier.
    * @param $alarm_threshold integer: threshold alarm value.
    * @param $nohtml integer: Return value without HTML tags.
    *
    * @return string to display
    *
    **/
   static function getCount($tID, $alarm_threshold, $nohtml=0) {
      global $DB,$CFG_GLPI, $LANG;

      $out="";
      // Get total
      $total = Consumable::getTotalNumber($tID);

      if ($total!=0) {
         $unused = Consumable::getUnusedNumber($tID);
         $old = Consumable::getOldNumber($tID);

         $highlight="";
         if ($unused<=$alarm_threshold) {
            $highlight="class='tab_bg_1_2'";
         }
         if (!$nohtml) {
            $out.= "<div $highlight>".$LANG['common'][33]."&nbsp;:&nbsp;$total&nbsp;&nbsp;&nbsp;<strong>".
                     $LANG['consumables'][13]."&nbsp;: $unused</strong>&nbsp;&nbsp;&nbsp;".
                     $LANG['consumables'][15]."&nbsp;: $old</div>";
         } else {
            $out.= $LANG['common'][33]."&nbsp;: $total   ".$LANG['consumables'][13]."&nbsp;: $unused   ".
                   $LANG['consumables'][15]."&nbsp;: $old";
         }
      } else {
         if (!$nohtml) {
            $out.= "<div class='tab_bg_1_2'><i>".$LANG['consumables'][9]."</i></div>";
         } else {
           $out.= $LANG['consumables'][9];
         }
      }
      return $out;
   }

   /**
    * Check if a Consumable is New (not used, in stock)
    *
    * @param $cID integer : consumable ID.
    *
    * @return
    *
    **/
   static function isNew($cID) {
      global $DB;

      $query = "SELECT `id`
                FROM `glpi_consumables`
                WHERE (`id` = '$cID'
                       AND `date_out` IS NULL)";
      $result = $DB->query($query);
      return ($DB->numrows($result)==1);
   }

   /**
    * Check if a consumable is Old (used, not in stock)
    *
    *@param $cID integer : consumable ID.
    *
    *@return
    *
    **/
   static function isOld($cID) {
      global $DB;

      $query = "SELECT `id`
                FROM `glpi_consumables`
                WHERE (`id` = '$cID'
                       AND `date_out` IS NOT NULL)";
      $result = $DB->query($query);
      return ($DB->numrows($result)==1);
   }

   /**
    * Get the localized string for the status of a consumable
    *
    *@param $cID integer : consumable ID.
    *
    *@return string : dict value for the consumable status.
    *
    **/
   static function getStatus($cID) {
      global $LANG;

      if (self::isNew($cID)) {
         return $LANG['consumables'][20];

      } else if (self::isOld($cID)) {
         return $LANG['consumables'][22];
      }
   }

   /**
    * Print out a link to add directly a new consumable from a consumable item.
    *
    * @param $consitem oject of ConsumableItem class
    *
    *
    * @return Nothing (displays)
    **/
   static function showAddForm(ConsumableItem $consitem) {
      global $CFG_GLPI,$LANG;

      $ID = $consitem->getField('id');

      if (!$consitem->can($ID,'w')) {
         return false;
      }

      if ($ID > 0) {
         echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/front/consumable.form.php\">";
         echo "<div class='center'>&nbsp;<table class='tab_cadre_fixe'>";
         echo "<tr>";
         echo "<td class='tab_bg_2 center'>";
         echo "<input type='submit' name='add_several' value=\"".$LANG['buttons'][8]."\" class='submit'>";
         echo "<input type='hidden' name='tID' value=\"$ID\">\n";
         echo "&nbsp;&nbsp;";
         Dropdown::showInteger('to_add',1,1,100);
         echo "&nbsp;&nbsp;";
         echo $LANG['consumables'][16];
         echo "</td></tr>";
         echo "</table></div>";
         echo "</form><br>";
      }
   }

   /**
    * Print out the consumables of a defined type
    *
    *@param $consitem oject of ConsumableItem class
    *@param $show_old boolean : show old consumables or not.
    *
    *@return Nothing (displays)
    **/
   static function showForItem (ConsumableItem $consitem, $show_old=0) {
      global $DB,$CFG_GLPI,$LANG;

      $tID = $consitem->getField('id');
      if (!$consitem->can($tID,'r')) {
         return false;
      }
      $canedit = $consitem->can($tID,'w');

      $query = "SELECT count(*) AS COUNT
                FROM `glpi_consumables`
                WHERE (`consumableitems_id` = '$tID')";

      if ($result = $DB->query($query)) {
         if ($DB->result($result,0,0)!=0) {
            if (!$show_old&&$canedit) {
               echo "<form method='post' action='".
                      $CFG_GLPI["root_doc"]."/front/consumable.form.php'>";
               echo "<input type='hidden' name='tID' value=\"$tID\">\n";
            }
            echo "<br><div class='center'><table class='tab_cadre_fixe'>";
            if (!$show_old) {
               echo "<tr><th colspan='7'>";
               echo self::getCount($tID,-1);
               echo "</th></tr>";
            } else { // Old
               echo "<tr><th colspan='8'>";
               echo $LANG['consumables'][35];
               echo "</th></tr>";
            }
            $i=0;
            echo "<tr><th>".$LANG['common'][2]."</th><th>".$LANG['consumables'][23]."</th>";
            echo "<th>".$LANG['cartridges'][24]."</th><th>".$LANG['consumables'][26]."</th>";

            if ($show_old) {
               echo "<th>".$LANG['common'][34]."</th>";
            }
            echo "<th>".$LANG['financial'][3]."</th>";

            if (!$show_old && $canedit) {
               echo "<th>";
               User::dropdown(array('value'  => $consitem->fields["entities_id"],
                                    'right'  => 'all'));
               echo "&nbsp;<input type='submit' class='submit' name='give' value='".
                            $LANG['consumables'][32]."'>";
               echo "</th>";
            } else {
               echo "<th>&nbsp;</th>";
            }
            if ($canedit){
               echo "<th>&nbsp;</th>";
            }
            echo "</tr>";
         } else {
            echo "<br>";
            echo "<div class='center'><strong>".$LANG['consumables'][7]."</strong></div>";
            return;
         }
      }

      $where="";
      $leftjoin="";
      $addselect="";
      if (!$show_old) { // NEW
         $where= " AND `date_out` IS NULL
                  ORDER BY `date_in`, `id`";
      } else { //OLD
         $where= " AND `date_out` IS NOT NULL
                  ORDER BY `date_out` DESC,
                           `date_in`,
                           `id`";
         $leftjoin=" LEFT JOIN `glpi_users` ON (`glpi_users`.`id` = `glpi_consumables`.`users_id`) ";
         $addselect= ", `glpi_users`.`realname` AS REALNAME,
                        `glpi_users`.`firstname` AS FIRSTNAME,
                        `glpi_users`.`name` AS USERNAME ";
      }
      $query = "SELECT `glpi_consumables`.* $addselect
                FROM `glpi_consumables` $leftjoin
                WHERE (`consumableitems_id` = '$tID') $where";

      if ($result = $DB->query($query)) {
         $number=$DB->numrows($result);
         while ($data=$DB->fetch_array($result)) {
            $date_in=convDate($data["date_in"]);
            $date_out=convDate($data["date_out"]);

            echo "<tr class='tab_bg_1'><td class='center'>";
            echo $data["id"];
            echo "</td><td class='center'>";
            echo self::getStatus($data["id"]);
            echo "</td><td class='center'>";
            echo $date_in;
            echo "</td><td class='center'>";
            echo $date_out;
            echo "</td>";

            if ($show_old) {
               echo "<td class='center'>";
               if (!empty($data["REALNAME"])) {
                  echo $data["REALNAME"];
                  if (!empty($data["FIRSTNAME"])) {
                     echo " ".$data["FIRSTNAME"];
                  }
               } else {
                  echo $data["USERNAME"];
               }
               echo "</td>";
            }
            echo "<td class='center'>";
            Infocom::showDisplayLink('Consumable',$data["id"],1);
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
                      $data["id"]."&amp;tID=$tID'>".$LANG['consumables'][37]."</a>";
               echo "</td>";
            }
            echo "<td class='center'>";
            echo "<a href='".
                   $CFG_GLPI["root_doc"]."/front/consumable.form.php?delete=delete&amp;id=".
                   $data["id"]."&amp;tID=$tID'>".$LANG['buttons'][6]."</a>";
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
    *
    **/
   static function showSummary(){
      global $DB,$LANG;

      if (!haveRight("consumable","r")) {
         return false;
      }

      $query = "SELECT COUNT(*) AS COUNT, `consumableitems_id`, `users_id`
                FROM `glpi_consumables`
                WHERE `date_out` IS NOT NULL
                      AND `consumableitems_id` IN (SELECT `id`
                                                    FROM `glpi_consumableitems`
                                                    ".getEntitiesRestrictRequest(
                                                    "WHERE","glpi_consumableitems").")
                GROUP BY `users_id`, `consumableitems_id`";
      $used=array();

      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)) {
            while ($data=$DB->fetch_array($result)) {
               $used[$data["users_id"]][$data["consumableitems_id"]]=$data["COUNT"];
            }
         }
      }
      $query = "SELECT COUNT(*) AS COUNT, `consumableitems_id`
                FROM `glpi_consumables`
                WHERE `date_out` IS NULL
                  AND `consumableitems_id` IN (SELECT `id`
                                               FROM `glpi_consumableitems`
                                                ".getEntitiesRestrictRequest("WHERE","glpi_consumableitems").")
                GROUP BY `consumableitems_id`";
      $new=array();

      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)) {
            while ($data=$DB->fetch_array($result)) {
               $new[$data["consumableitems_id"]]=$data["COUNT"];
            }
         }
      }

      $types=array();
      $query="SELECT *
              FROM `glpi_consumableitems`
              ".getEntitiesRestrictRequest("WHERE","glpi_consumableitems");
      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)) {
            while ($data=$DB->fetch_array($result)) {
               $types[$data["id"]]=$data["name"];
            }
         }
      }
      asort($types);
      $total=array();
      if (count($types)>0) {
         // Produce headline
         echo "<div class='center'><table  class='tab_cadrehov'><tr>";

         // Type
         echo "<th>";;
         echo $LANG['common'][34]."</th>";

         foreach ($types as $key => $type) {
            echo "<th>$type</th>";
            $total[$key]=0;
         }
         echo "<th>".$LANG['common'][33]."</th>";
         echo "</tr>";

         // new
         echo "<tr class='tab_bg_2'><td><strong>".$LANG['consumables'][1]."</strong></td>";
         $tot=0;
         foreach ($types as $id_type => $type) {
            if (!isset($new[$id_type])) {
               $new[$id_type]=0;
            }
            echo "<td class='center'>".$new[$id_type]."</td>";
            $total[$id_type]+=$new[$id_type];
            $tot+=$new[$id_type];
         }
         echo "<td class='center'>".$tot."</td>";
         echo "</tr>";

         foreach ($used as $users_id => $val) {
            echo "<tr class='tab_bg_2'><td>".getUserName($users_id)."</td>";
            $tot=0;
            foreach ($types as $id_type => $type) {
               if (!isset($val[$id_type])) {
                  $val[$id_type]=0;
               }
               echo "<td class='center'>".$val[$id_type]."</td>";
               $total[$id_type]+=$val[$id_type];
               $tot+=$val[$id_type];
            }
         echo "<td class='center'>".$tot."</td>";
         echo "</tr>";
         }
         echo "<tr class='tab_bg_1'><td><strong>".$LANG['common'][33]."</strong></td>";
         $tot=0;
         foreach ($types as $id_type => $type) {
            $tot+=$total[$id_type];
            echo "<td class='center'>".$total[$id_type]."</td>";
         }
         echo "<td class='center'>".$tot."</td>";
         echo "</tr>";
         echo "</table></div>";
      } else {
         echo "<div class='center'><strong>".$LANG['consumables'][7]."</strong></div>";
      }
   }
}

?>