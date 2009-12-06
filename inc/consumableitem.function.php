<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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

/**
 * Print out a link to add directly a new consumable from a consumable item.
 *
 * Print out the link witch make a new consumable from consumable item idetified by $ID
 *
 *@param $ID Consumable item identifier.
 *
 *
 *@return Nothing (displays)
 **/
function showConsumableAdd($ID) {
   global $CFG_GLPI,$LANG;

   if (!haveRight("consumable","w")) {
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
      dropdownInteger('to_add',1,1,100);
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
 * Print out all the consumables that are issued from the consumable item identified by $ID
 *
 *@param $tID integer : Consumable item identifier.
 *@param $show_old boolean : show old consumables or not.
 *
 *@return Nothing (displays)
 **/
function showConsumables ($tID,$show_old=0) {
   global $DB,$CFG_GLPI,$LANG;

   if (!haveRight("consumable","r")) {
      return false;
   }
   $canedit=haveRight("consumable","w");

   $cartype=new ConsumableItem();

   if ($cartype->getFromDB($tID)) {
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
               echo countConsumables($tID,-1);
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
               dropdownAllUsers("users_id",0,1,$cartype->fields["entities_id"]);
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
            echo getConsumableStatus($data["id"]);
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
            Infocom::showDisplayLink(CONSUMABLE_TYPE,$data["id"],1);
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
}

/**
 * Print the consumable count HTML array for a defined consumable type
 *
 * Print the consumable count HTML array for the consumable item $tID
 *
 *@param $tID integer: consumable item identifier.
 *@param $alarm_threshold integer: threshold alarm value.
 *@param $nohtml integer: Return value without HTML tags.
 *
 *@return string to display
 *
 **/
function countConsumables($tID,$alarm_threshold,$nohtml=0) {
   global $DB,$CFG_GLPI, $LANG;

   $out="";
   // Get total
   $total = getConsumablesNumber($tID);

   if ($total!=0) {
      $unused=getUnusedConsumablesNumber($tID);
      $old=getOldConsumablesNumber($tID);

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
 * count how many consumable for a consumable type
 *
 * count how many consumable for the consumable item $tID
 *
 *@param $tID integer: consumable item identifier.
 *
 *@return integer : number of consumable counted.
 *
 **/
function getConsumablesNumber($tID) {
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
function getOldConsumablesNumber($tID) {
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
function getUnusedConsumablesNumber($tID) {
   global $DB;

   $query = "SELECT `id`
             FROM `glpi_consumables`
             WHERE (`consumableitems_id` = '$tID'
                    AND `date_out` IS NULL)";
   $result = $DB->query($query);
   return $DB->numrows($result);
}

/**
 * To be commented
 *
 *
 *
 *@param $cID integer : consumable ID.
 *
 *@return
 *
 **/
function isNewConsumable($cID) {
   global $DB;

   $query = "SELECT `id`
             FROM `glpi_consumables`
             WHERE (`id` = '$cID'
                    AND `date_out` IS NULL)";
   $result = $DB->query($query);
   return ($DB->numrows($result)==1);
}

/**
 * To be commented
 *
 *
 *
 *@param $cID integer : consumable ID.
 *
 *@return
 *
 **/
function isOldConsumable($cID) {
   global $DB;

   $query = "SELECT `id`
             FROM `glpi_consumables`
             WHERE (`id` = '$cID'
                    AND `date_out` IS NOT NULL)";
   $result = $DB->query($query);
   return ($DB->numrows($result)==1);
}

/**
 * Get the dict value for the status of a consumable
 *
 *
 *
 *@param $cID integer : consumable ID.
 *
 *@return string : dict value for the consumable status.
 *
 **/
function getConsumableStatus($cID) {
   global $LANG;

   if (isNewConsumable($cID)) {
      return $LANG['consumables'][20];
   } else if (isOldConsumable($cID)) {
      return $LANG['consumables'][22];
   }
}

/**
 * Show the usage summary of consumables by user
 *
 **/
function showConsumableSummary(){
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
                                                 ".getEntitiesRestrictRequest(
                                                 "WHERE","`glpi_consumableitems`").")
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

?>