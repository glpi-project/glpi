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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}


class DisplayPreference extends CommonDBTM {

   // From CommonDBTM
   var $auto_message_on_action = false;

   function prepareInputForAdd($input) {
      global $DB;

      $query = "SELECT MAX(`rank`)
                FROM `".$this->getTable()."`
                WHERE `itemtype` = '".$input["itemtype"]."'
                      AND `users_id` = '".$input["users_id"]."'";
      $result = $DB->query($query);

      $input["rank"] = $DB->result($result,0,0)+1;

      return $input;
   }


   /**
    * Get display preference for an user for an itemtype
    *
    * @param $itemtype itemtype
    * @param $user_id user ID
   **/
   static function getForTypeUser ($itemtype, $user_id) {
      global $DB;

      // Add default items for user
      $query = "SELECT *
                FROM `glpi_displaypreferences`
                WHERE `itemtype` = '$itemtype'
                      AND `users_id` = '$user_id'
                ORDER BY `rank`";
      $result = $DB->query($query);

      // GET default serach options
      if ($DB->numrows($result)==0) {
         $query = "SELECT *
                   FROM `glpi_displaypreferences`
                   WHERE `itemtype` = '$itemtype'
                         AND `users_id` = '0'
                   ORDER BY `rank`";
         $result = $DB->query($query);
      }

      $prefs = array();
      if ($DB->numrows($result)>0) {
         while ($data=$DB->fetch_array($result)) {
            array_push($prefs, $data["num"]);
         }
      }
      return $prefs;
   }


   /**
    * Active personal config based on global one
    *
    * @param $input parameter array (itemtype,users_id)
   **/
   function activatePerso($input) {
      global $DB;

      if (!haveRight("search_config", "w")) {
         return false;
      }

      $query = "SELECT *
                FROM `".$this->getTable()."`
                WHERE `itemtype` = '".$input["itemtype"]."'
                      AND `users_id` = '0'";
      $result = $DB->query($query);

      if ($DB->numrows($result)) {
         while ($data=$DB->fetch_assoc($result)) {
            unset($data["id"]);
            $data["users_id"] = $input["users_id"];
            $this->fields     = $data;
            $this->addToDB();
         }

      } else {
         // No items in the global config
         $searchopt = Search::getOptions($input["itemtype"]);
         if (count($searchopt)>1) {
            $done = false;

            foreach ($searchopt as $key => $val) {
               if (is_array($val) && $key!=1 && !$done) {
                  $data["users_id"] = $input["users_id"];
                  $data["itemtype"] = $input["itemtype"];
                  $data["rank"]     = 1;
                  $data["num"]      = $key;
                  $this->fields     = $data;
                  $this->addToDB();
                  $done = true;
               }
            }
         }
      }
   }


   /**
    * Order to move an item
    *
    * @param $input parameter array (id,itemtype,users_id)
    * @param $action up or down
   **/
   function orderItem($input, $action) {
      global $DB;

      // Get current item
      $query = "SELECT `rank`
                FROM `".$this->getTable()."`
                WHERE `id` = '".$input['id']."'";
      $result = $DB->query($query);
      $rank1  = $DB->result($result, 0, 0);

      // Get previous or next item
      $query = "SELECT `id`, `rank`
                FROM `".$this->getTable()."`
                WHERE `itemtype` = '".$input['itemtype']."'
                      AND `users_id` = '".$input["users_id"]."'";

      switch ($action) {
         case "up" :
            $query .= " AND `rank` < '$rank1'
                      ORDER BY `rank` DESC";
            break;

         case "down" :
            $query .= " AND `rank` > '$rank1'
                      ORDER BY `rank` ASC";
            break;

         default :
            return false;
      }

      $result = $DB->query($query);
      $rank2  = $DB->result($result, 0, "rank");
      $ID2    = $DB->result($result, 0, "id");

      // Update items
      $query = "UPDATE `".$this->getTable()."`
                SET `rank` = '$rank2'
                WHERE `id` = '".$input['id']."'";
      $DB->query($query);

      $query = "UPDATE `".$this->getTable()."`
                SET `rank` = '$rank1'
                WHERE `id` = '$ID2'";
      $DB->query($query);
   }


   /**
    * Print the search config form
    *
    * @param $target form target
    * @param $itemtype item type
    *
    * @return nothing
   **/
   function showFormPerso($target,$itemtype) {
      global $CFG_GLPI, $LANG, $DB;

      $searchopt = Search::getCleanedOptions($itemtype);
      if (!is_array($searchopt)) {
         return false;
      }

      $item = NULL;
      if ($itemtype!='States' && class_exists($itemtype)) {
         $item = new $itemtype();
      }

      $IDuser = getLoginUserID();

      echo "<div class='center' id='tabsbody' >";
      // Defined items
      $query = "SELECT *
                FROM `".$this->getTable()."`
                WHERE `itemtype` = '$itemtype'
                      AND `users_id` = '$IDuser'
                ORDER BY `rank`";
      $result  = $DB->query($query);
      $numrows = 0;
      $numrows = $DB->numrows($result);

      if ($numrows==0) {
         checkRight("search_config", "w");
         echo "<table class='tab_cadre_fixe'><tr><th colspan='4'>";
         echo "<form method='post' action='$target'>";
         echo "<input type='hidden' name='itemtype' value='$itemtype'>";
         echo "<input type='hidden' name='users_id' value='$IDuser'>";
         echo $LANG['setup'][241]."<span class='small_space'>";
         echo "<input type='submit' name='activate' value='".$LANG['buttons'][2]."' class='submit' >";
         echo "</span></form></th></tr></table>\n";

      } else {
         $already_added = self::getForTypeUser($itemtype, $IDuser);

         echo "<table class='tab_cadre_fixe'><tr><th colspan='4'>";
         echo $LANG['setup'][252]."&nbsp;: </th></tr>";
         echo "<tr class='tab_bg_1'><td colspan='4' class='center'>";
         echo "<form method='post' action=\"$target\">";
         echo "<input type='hidden' name='itemtype' value='$itemtype'>";
         echo "<input type='hidden' name='users_id' value='$IDuser'>";
         echo "<select name='num'>";
         $first_group = true;

         foreach ($searchopt as $key => $val) {
            if (!is_array($val)) {
               if (!$first_group) {
                  echo "</optgroup>\n";
               } else {
                  $first_group = false;
               }
               echo "<optgroup label='$val'>";

            } else if ($key!=1 && !in_array($key,$already_added)) {
               echo "<option value='$key'>".$val["name"]."</option>\n";
            }
         }

         if (!$first_group) {
            echo "</optgroup>\n";
         }
         echo "</select><span class='small_space'>";
         echo "<input type='submit' name='add' value='".$LANG['buttons'][8]."' class='submit' >";
         echo "</span></form>";
         echo "</td></tr>\n";

         // print first element
         echo "<tr class='tab_bg_2'>";
         echo "<td class='center' width='50%'>".$searchopt[1]["name"]."</td>";
         echo "<td colspan='3'>&nbsp;</td>";
         echo "</tr>";


         // print entity
         if (isMultiEntitiesMode()
             && (isset($CFG_GLPI["union_search_type"][$itemtype])
                 || ($item && $item->maybeRecursive())
                 || count($_SESSION["glpiactiveentities"])>1)
             && isset($searchopt[80])) {

            echo "<tr class='tab_bg_2'>";
            echo "<td class='center' width='50%'>".$searchopt[80]["name"]."</td>";
            echo "<td colspan='3'>&nbsp;</td>";
            echo "</tr>";
         }

         $i = 0;
         if ($numrows) {
            while ($data=$DB->fetch_array($result)) {
               if ($data["num"]!=1 && isset($searchopt[$data["num"]])) {
                  echo "<tr class='tab_bg_2'>";
                  echo "<td class='center' width='50%' >";
                  echo $searchopt[$data["num"]]["name"]."</td>";

                  if ($i!=0) {
                     echo "<td class='center middle'>";
                     echo "<form method='post' action='$target'>";
                     echo "<input type='hidden' name='id' value='".$data["id"]."'>";
                     echo "<input type='hidden' name='users_id' value='$IDuser'>";
                     echo "<input type='hidden' name='itemtype' value='$itemtype'>";
                     echo "<input type='image' name='up' value=\"".$LANG['buttons'][24]."\" src='".
                            $CFG_GLPI["root_doc"]."/pics/puce-up2.png' alt=\"".
                            $LANG['buttons'][24]."\" title=\"".$LANG['buttons'][24]."\">";
                     echo "</form></td>\n";

                  } else {
                     echo "<td>&nbsp;</td>";
                  }

                  if ($i!=$numrows-1) {
                     echo "<td class='center middle'>";
                     echo "<form method='post' action='$target'>";
                     echo "<input type='hidden' name='id' value='".$data["id"]."'>";
                     echo "<input type='hidden' name='users_id' value='$IDuser'>";
                     echo "<input type='hidden' name='itemtype' value='$itemtype'>";
                     echo "<input type='image' name='down' value=\"".$LANG['buttons'][25]."\" src='".
                            $CFG_GLPI["root_doc"]."/pics/puce-down2.png' alt=\"".
                            $LANG['buttons'][25]."\" title=\"".$LANG['buttons'][25]."\">";
                     echo "</form></td>\n";

                  } else {
                     echo "<td>&nbsp;</td>";
                  }

                  echo "<td class='center middle'>";
                  echo "<form method='post' action='$target'>";
                  echo "<input type='hidden' name='id' value='".$data["id"]."'>";
                  echo "<input type='hidden' name='users_id' value='$IDuser'>";
                  echo "<input type='hidden' name='itemtype' value='$itemtype'>";
                  echo "<input type='image' name='delete' value=\"".$LANG['buttons'][6]."\" src='".
                         $CFG_GLPI["root_doc"]."/pics/puce-delete2.png' alt=\"".
                         $LANG['buttons'][6]."\" title=\"".$LANG['buttons'][6]."\">";
                  echo "</form></td>\n";
                  echo "</tr>";
                  $i++;
               }
            }
         }
         echo "</table>";
      }
      echo "</div>";
   }


   /**
    * Print the search config form
    *
    * @param $target form target
    * @param $itemtype item type
    *
    * @return nothing
   **/
   function showFormGlobal($target,$itemtype) {
      global $CFG_GLPI, $LANG, $DB;

      $searchopt = Search::getOptions($itemtype);
      if (!is_array($searchopt)) {
         return false;
      }
      $IDuser = 0;

      $item = NULL;
      if ($itemtype!='States' && class_exists($itemtype)) {
         $item = new $itemtype();
      }

      $global_write = haveRight("search_config_global", "w");

      echo "<div class='center' id='tabsbody' >";
      // Defined items
      $query = "SELECT *
                FROM `".$this->getTable()."`
                WHERE `itemtype` = '$itemtype'
                      AND `users_id` = '$IDuser'
                ORDER BY `rank`";

      $result  = $DB->query($query);
      $numrows = $DB->numrows($result);

      echo "<table class='tab_cadre_fixe'><tr><th colspan='4'>";
      echo $LANG['setup'][252]."&nbsp;: </th></tr>\n";

      if ($global_write) {
         $already_added = self::getForTypeUser($itemtype, $IDuser);
         echo "<tr class='tab_bg_1'><td colspan='4' class='center'>";
         echo "<form method='post' action='$target'>";
         echo "<input type='hidden' name='itemtype' value='$itemtype'>";
         echo "<input type='hidden' name='users_id' value='$IDuser'>";
         echo "<select name='num'>";
         $first_group = true;
         $searchopt   = Search::getCleanedOptions($itemtype);

         foreach ($searchopt as $key => $val) {
            if (!is_array($val)) {
               if (!$first_group) {
                  echo "</optgroup>\n";
               } else {
                  $first_group = false;
               }
               echo "<optgroup label='$val'>";

            } else if ($key!=1 && !in_array($key,$already_added)) {
               echo "<option value='$key'>".$val["name"]."</option>";
            }
         }

         if (!$first_group) {
            echo "</optgroup>\n";
         }

         echo "</select><span class='small_space'>";
         echo "<input type='submit' name='add' value='".$LANG['buttons'][8]."' class='submit' >";
         echo "</span></form>";
         echo "</td></tr>";
      }

      // print first element
      echo "<tr class='tab_bg_2'>";
      echo "<td class='center' width='50%'>".$searchopt[1]["name"];

      if ($global_write) {
         echo "</td><td colspan='3'>&nbsp;";
      }
      echo "</td></tr>";

      // print entity
      if (isMultiEntitiesMode()
          && (isset($CFG_GLPI["union_search_type"][$itemtype])
              || ($item && $item->maybeRecursive())
              || count($_SESSION["glpiactiveentities"])>1)
          && isset($searchopt[80])) {

         echo "<tr class='tab_bg_2'>";
         echo "<td class='center' width='50%'>".$searchopt[80]["name"]."</td>";
         echo "<td colspan='3'>&nbsp;</td>";
         echo "</tr>";
      }

      $i = 0;

      if ($numrows) {
         while ($data=$DB->fetch_array($result)) {

            if ($data["num"]!=1 && isset($searchopt[$data["num"]])) {

               echo "<tr class='tab_bg_2'><td class='center' width='50%'>";
               echo $searchopt[$data["num"]]["name"].$data['num'];
               echo "</td>";

               if ($global_write) {
                  if ($i!=0) {
                     echo "<td class='center middle'>";
                     echo "<form method='post' action='$target'>";
                     echo "<input type='hidden' name='id' value='".$data["id"]."'>";
                     echo "<input type='hidden' name='users_id' value='$IDuser'>";
                     echo "<input type='hidden' name='itemtype' value='$itemtype'>";
                     echo "<input type='image' name='up' value=\"".$LANG['buttons'][24]."\" src='".
                            $CFG_GLPI["root_doc"]."/pics/puce-up2.png' alt=\"".
                            $LANG['buttons'][24]."\"  title=\"".$LANG['buttons'][24]."\">";
                     echo "</form>";
                     echo "</td>";

                  } else {
                     echo "<td>&nbsp;</td>\n";
                  }

                  if ($i!=$numrows-1) {
                     echo "<td class='center middle'>";
                     echo "<form method='post' action='$target'>";
                     echo "<input type='hidden' name='id' value='".$data["id"]."'>";
                     echo "<input type='hidden' name='users_id' value='$IDuser'>";
                     echo "<input type='hidden' name='itemtype' value='$itemtype'>";
                     echo "<input type='image' name='down' value=\"".$LANG['buttons'][25]."\" src='".
                            $CFG_GLPI["root_doc"]."/pics/puce-down2.png' alt=\"".
                            $LANG['buttons'][25]."\" title=\"".$LANG['buttons'][25]."\">";
                     echo "</form>";
                     echo "</td>";

                  } else {
                     echo "<td>&nbsp;</td>\n";
                  }

                  echo "<td class='center middle'>";
                  echo "<form method='post' action='$target'>";
                  echo "<input type='hidden' name='id' value='".$data["id"]."'>";
                  echo "<input type='hidden' name='users_id' value='$IDuser'>";
                  echo "<input type='hidden' name='itemtype' value='$itemtype'>";
                  echo "<input type='image' name='delete' value=\"".$LANG['buttons'][6]."\" src='".
                         $CFG_GLPI["root_doc"]."/pics/puce-delete2.png' alt=\"".
                         $LANG['buttons'][6]."\" title=\"".$LANG['buttons'][6]."\">";
                  echo "</form>";
                  echo "</td>\n";
               }

               echo "</tr>";
               $i++;
            }
         }
      }
      echo "</table>";
      echo "</div>";
   }

}

?>
