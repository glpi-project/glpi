<?php
/*
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

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
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class DisplayPreference extends CommonDBTM {

   // From CommonGLPI
   public $taborientation          = 'horizontal';
   public $get_item_to_display_tab = false;

   // From CommonDBTM
   public $auto_message_on_action  = false;

   protected $displaylist          = false;


   static $rightname = 'search_config';

   const PERSONAL = 1024;
   const GENERAL  = 2048;



   /**
    * @see CommonDBTM::prepareInputForAdd()
   **/
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
    * @since version 0.85
    *
    * @see CommonDBTM::processMassiveActionsForOneItemtype()
   **/
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {

      switch ($ma->getAction()) {
         case 'delete_for_user' :
            $input = $ma->getInput();
            if (isset($input['users_id'])) {
               $user = new User();
               $user->getFromDB($input['users_id']);
               foreach ($ids as $id) {
                  if ($input['users_id'] == Session::getLoginUserID()) {
                     if ($item->deleteByCriteria(array('users_id' => $input['users_id'],
                                                       'itemtype' => $id))) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                     } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        $ma->addMessage($user->getErrorMessage(ERROR_ON_ACTION));
                     }
                  } else {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                     $ma->addMessage($user->getErrorMessage(ERROR_RIGHT));
                  }
               }
            } else {
               $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
            }
            return;
      }
      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }


   /**
    * Get display preference for a user for an itemtype
    *
    * @param $itemtype  itemtype
    * @param $user_id   user ID
   **/
   static function getForTypeUser($itemtype, $user_id) {
      global $DB;

      $query = "SELECT *
                FROM `glpi_displaypreferences`
                WHERE `itemtype` = '$itemtype'
                      AND (`users_id` = '$user_id' OR `users_id` = '0')
                ORDER BY `users_id`, `rank`";
      $result = $DB->query($query);

      $default_prefs = array();
      $user_prefs = array();

      while ($data = $DB->fetch_assoc($result)) {
         if ($data["users_id"] != 0) {
            $user_prefs[] = $data["num"];
         } else {
            $default_prefs[] = $data["num"];
         }
      }

      return count($user_prefs) ? $user_prefs : $default_prefs;
   }


   /**
    * Active personal config based on global one
    *
    * @param $input  array parameter (itemtype,users_id)
   **/
   function activatePerso(array $input) {
      global $DB;

      if (!Session::haveRight(self::$rightname, self::PERSONAL)) {
         return false;
      }

      $query = "SELECT *
                FROM `".$this->getTable()."`
                WHERE `itemtype` = '".$input["itemtype"]."'
                      AND `users_id` = '0'";
      $result = $DB->query($query);

      if ($DB->numrows($result)) {
         while ($data = $DB->fetch_assoc($result)) {
            unset($data["id"]);
            $data["users_id"] = $input["users_id"];
            $this->fields     = $data;
            $this->addToDB();
         }

      } else {
         // No items in the global config
         $searchopt = Search::getOptions($input["itemtype"]);
         if (count($searchopt) > 1) {
            $done = false;

            foreach ($searchopt as $key => $val) {
               if (is_array($val)
                   && ($key != 1)
                   && !$done) {

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
    * @param $input  array parameter (id,itemtype,users_id)
    * @param $action       up or down
   **/
   function orderItem(array $input, $action) {
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
    * @param $target    form target
    * @param $itemtype  item type
    *
    * @return nothing
   **/
   function showFormPerso($target, $itemtype) {
      global $CFG_GLPI, $DB;

      $searchopt = Search::getCleanedOptions($itemtype);
      if (!is_array($searchopt)) {
         return false;
      }

      $item = NULL;
      if ($itemtype != 'AllAssets') {
         $item = getItemForItemtype($itemtype);
      }

      $IDuser = Session::getLoginUserID();

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

      if ($numrows == 0) {
         Session::checkRight(self::$rightname, self::PERSONAL);
         echo "<table class='tab_cadre_fixe'><tr><th colspan='4'>";
         echo "<form method='post' action='$target'>";
         echo "<input type='hidden' name='itemtype' value='$itemtype'>";
         echo "<input type='hidden' name='users_id' value='$IDuser'>";
         echo __('No personal criteria. Create personal parameters?')."<span class='small_space'>";
         echo "<input type='submit' name='activate' value=\"".__('Create')."\"
                class='submit'>";
         echo "</span>";
         Html::closeForm();
         echo "</th></tr></table>\n";

      } else {
         $already_added = self::getForTypeUser($itemtype, $IDuser);

         echo "<table class='tab_cadre_fixe'><tr><th colspan='4'>";
         echo "<form method='post' action='$target'>";
         echo "<input type='hidden' name='itemtype' value='$itemtype'>";
         echo "<input type='hidden' name='users_id' value='$IDuser'>";
         echo __('Select default items to show')."<span class='small_space'>";
         echo "<input type='submit' name='disable' value=\"".__('Delete')."\"
                class='submit'>";
         echo "</span>";
         Html::closeForm();

         echo "</th></tr>";
         echo "<tr class='tab_bg_1'><td colspan='4' class='center'>";
         echo "<form method='post' action=\"$target\">";
         echo "<input type='hidden' name='itemtype' value='$itemtype'>";
         echo "<input type='hidden' name='users_id' value='$IDuser'>";
         $group  = '';
         $values = array();
         foreach ($searchopt as $key => $val) {
            if (!is_array($val)) {
               $group = $val;

            } else if (($key != 1)
                       && !in_array($key,$already_added)
                       && (!isset($val['nodisplay']) || !$val['nodisplay'])) {
               $values[$group][$key] = $val["name"];
            }
         }
         if ($values) {
            Dropdown::showFromArray('num', $values);
            echo "<span class='small_space'>";
            echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
            echo "</span>";
         }
         Html::closeForm();
         echo "</td></tr>\n";

         // print first element
         echo "<tr class='tab_bg_2'>";
         echo "<td class='center' width='50%'>".$searchopt[1]["name"]."</td>";
         echo "<td colspan='3'>&nbsp;</td>";
         echo "</tr>";


         // print entity
         if (Session::isMultiEntitiesMode()
             && (isset($CFG_GLPI["union_search_type"][$itemtype])
                 || ($item && $item->maybeRecursive())
                 || (count($_SESSION["glpiactiveentities"]) > 1))
             && isset($searchopt[80])) {

            echo "<tr class='tab_bg_2'>";
            echo "<td class='center' width='50%'>".$searchopt[80]["name"]."</td>";
            echo "<td colspan='3'>&nbsp;</td>";
            echo "</tr>";
         }

         $i = 0;
         if ($numrows) {
            while ($data = $DB->fetch_assoc($result)) {
               if (($data["num"] !=1) && isset($searchopt[$data["num"]])) {
                  echo "<tr class='tab_bg_2'>";
                  echo "<td class='center' width='50%' >";
                  echo $searchopt[$data["num"]]["name"]."</td>";

                  if ($i != 0) {
                     echo "<td class='center middle'>";
                     echo "<form method='post' action='$target'>";
                     echo "<input type='hidden' name='id' value='".$data["id"]."'>";
                     echo "<input type='hidden' name='users_id' value='$IDuser'>";
                     echo "<input type='hidden' name='itemtype' value='$itemtype'>";
                     echo "<input type='image' name='up' value=\"".__s('Bring up')."\" src='".
                            $CFG_GLPI["root_doc"]."/pics/puce-up.png' alt=\"".
                            __s('Bring up')."\" title=\"".__s('Bring up')."\">";
                     Html::closeForm();
                     echo "</td>\n";

                  } else {
                     echo "<td>&nbsp;</td>";
                  }

                  if ($i != ($numrows-1)) {
                     echo "<td class='center middle'>";
                     echo "<form method='post' action='$target'>";
                     echo "<input type='hidden' name='id' value='".$data["id"]."'>";
                     echo "<input type='hidden' name='users_id' value='$IDuser'>";
                     echo "<input type='hidden' name='itemtype' value='$itemtype'>";
                     echo "<input type='image' name='down' value=\"".__s('Bring down')."\" src='".
                            $CFG_GLPI["root_doc"]."/pics/puce-down.png' alt=\"".
                            __s('Bring down')."\" title=\"".__s('Bring down')."\">";
                     Html::closeForm();
                     echo "</td>\n";

                  } else {
                     echo "<td>&nbsp;</td>";
                  }

                  echo "<td class='center middle'>";
                  echo "<form method='post' action='$target'>";
                  echo "<input type='hidden' name='id' value='".$data["id"]."'>";
                  echo "<input type='hidden' name='users_id' value='$IDuser'>";
                  echo "<input type='hidden' name='itemtype' value='$itemtype'>";
                  echo "<input type='image' name='purge' value=\"".
                         _sx('button', 'Delete permanently')."\" src='".
                         $CFG_GLPI["root_doc"]."/pics/delete.png' alt=\"".
                         _sx('button', 'Delete permanently')."\" title=\"".
                         _sx('button', 'Delete permanently')."\">";
                  Html::closeForm();
                  echo "</td>\n";
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
    * @param $target    form target
    * @param $itemtype  item type
    *
    * @return nothing
   **/
   function showFormGlobal($target, $itemtype) {
      global $CFG_GLPI, $DB;

      $searchopt = Search::getOptions($itemtype);
      if (!is_array($searchopt)) {
         return false;
      }
      $IDuser = 0;

      $item = NULL;
      if ($itemtype != 'AllAssets') {
         $item = getItemForItemtype($itemtype);
      }

      $global_write = Session::haveRight(self::$rightname, self::GENERAL);

      echo "<div class='center' id='tabsbody' >";
      // Defined items
      $query = "SELECT *
                FROM `".$this->getTable()."`
                WHERE `itemtype` = '$itemtype'
                      AND `users_id` = '$IDuser'
                ORDER BY `rank`";

      $result  = $DB->query($query);
      $numrows = $DB->numrows($result);

      echo "<table class='tab_cadre_fixehov'><tr><th colspan='4'>";
      echo __('Select default items to show')."</th></tr>\n";

      if ($global_write) {
         $already_added = self::getForTypeUser($itemtype, $IDuser);
         echo "<tr class='tab_bg_1'><td colspan='4' class='center'>";
         echo "<form method='post' action='$target'>";
         echo "<input type='hidden' name='itemtype' value='$itemtype'>";
         echo "<input type='hidden' name='users_id' value='$IDuser'>";
         $group  = '';
         $values = array();
         $searchopt   = Search::getCleanedOptions($itemtype);
         foreach ($searchopt as $key => $val) {
            if (!is_array($val)) {
               $group = $val;
            } else if (($key != 1)
                       && !in_array($key,$already_added)
                       && (!isset($val['nodisplay']) || !$val['nodisplay'])) {
               $values[$group][$key] = $val["name"];
            }
         }
         if ($values) {
            Dropdown::showFromArray('num', $values);
            echo "<span class='small_space'>";
            echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
            echo "</span>";
         }
         Html::closeForm();
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
      if (Session::isMultiEntitiesMode()
          && (isset($CFG_GLPI["union_search_type"][$itemtype])
              || ($item && $item->maybeRecursive())
              || (count($_SESSION["glpiactiveentities"]) > 1))
          && isset($searchopt[80])) {

         echo "<tr class='tab_bg_2'>";
         echo "<td class='center' width='50%'>".$searchopt[80]["name"]."</td>";
         echo "<td colspan='3'>&nbsp;</td>";
         echo "</tr>";
      }

      $i = 0;

      if ($numrows) {
         while ($data=$DB->fetch_assoc($result)) {

            if (($data["num"] != 1)
                && isset($searchopt[$data["num"]])) {

               echo "<tr class='tab_bg_2'><td class='center' width='50%'>";
               echo $searchopt[$data["num"]]["name"];
               echo "</td>";

               if ($global_write) {
                  if ($i != 0) {
                     echo "<td class='center middle'>";
                     echo "<form method='post' action='$target'>";
                     echo "<input type='hidden' name='id' value='".$data["id"]."'>";
                     echo "<input type='hidden' name='users_id' value='$IDuser'>";
                     echo "<input type='hidden' name='itemtype' value='$itemtype'>";
                     echo "<input type='image' name='up' value=\"".__s('Bring up')."\" src='".
                            $CFG_GLPI["root_doc"]."/pics/puce-up.png' alt=\"".
                            __s('Bring up')."\"  title=\"".__s('Bring up')."\" class='pointer'>";
                     Html::closeForm();
                     echo "</td>";

                  } else {
                     echo "<td>&nbsp;</td>\n";
                  }

                  if ($i != ($numrows-1)) {
                     echo "<td class='center middle'>";
                     echo "<form method='post' action='$target'>";
                     echo "<input type='hidden' name='id' value='".$data["id"]."'>";
                     echo "<input type='hidden' name='users_id' value='$IDuser'>";
                     echo "<input type='hidden' name='itemtype' value='$itemtype'>";
                     echo "<input type='image' name='down' value=\"".__s('Bring down')."\" src='".
                            $CFG_GLPI["root_doc"]."/pics/puce-down.png' alt=\"".
                            __s('Bring down')."\" title=\"".__s('Bring down')."\" class='pointer'>";
                     Html::closeForm();
                     echo "</td>";

                  } else {
                     echo "<td>&nbsp;</td>\n";
                  }

                  echo "<td class='center middle'>";
                  echo "<form method='post' action='$target'>";
                  echo "<input type='hidden' name='id' value='".$data["id"]."'>";
                  echo "<input type='hidden' name='users_id' value='$IDuser'>";
                  echo "<input type='hidden' name='itemtype' value='$itemtype'>";
                  echo "<input type='image' name='purge' value=\"".
                         _sx('button', 'Delete permanently')."\" src='".
                         $CFG_GLPI["root_doc"]."/pics/delete.png' alt=\"".
                         __s('Delete permanently')."\" title=\"". __s('Delete permanently')."\"  class='pointer'>";
                  Html::closeForm();
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


   /**
    * show defined display preferences for a user
    *
    * @param $users_id integer user ID
   **/
   static function showForUser($users_id) {
      global $DB;

      $url = Toolbox::getItemTypeFormURL(__CLASS__);

      $query = "SELECT `itemtype`,
                       COUNT(*) AS nb
                FROM `glpi_displaypreferences`
                WHERE `users_id` = '$users_id'
                GROUP BY `itemtype`";

      $req = $DB->request($query);
      if ($req->numrows() > 0) {
         $rand = mt_rand();
         echo "<div class='spaced'>";
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams = array('width'            => 400,
                           'height'           => 200,
                           'container'        => 'mass'.__CLASS__.$rand,
                           'specific_actions' => array(__CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'delete_for_user'
                                                       => _x('button', 'Delete permanently')),
                           'extraparams'      => array('massive_action_fields' => array('users_id')));

         Html::showMassiveActions($massiveactionparams);

         echo Html::hidden('users_id', array('value'                 => $users_id,
                                             'data-glpicore-ma-tags' => 'common'));
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr>";
         echo "<th width='10'>";
         Html::checkAllAsCheckbox('mass'.__CLASS__.$rand);
         echo "</th>";
         echo "<th colspan='2'>".__('Type')."</th></tr>";
         foreach ($req as $data) {
            echo "<tr class='tab_bg_1'><td width='10'>";
            Html::showMassiveActionCheckBox(__CLASS__, $data["itemtype"]);
            echo "</td>";
            if ($item = getItemForItemtype($data["itemtype"])) {
               $name = $item->getTypeName(1);
            } else {
               $name = $data["itemtype"];
            }
            echo "<td>$name</td><td class='numeric'>".$data['nb']."</td>";
            echo "</tr>";
         }
         echo "</table>";
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
         echo "</div>";

      } else {
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><td class='b center'>".__('No item found')."</td></tr>";
         echo "</table>";
      }
   }


   /**
    * For tab management : force isNewItem
    *
    * @since version 0.83
   **/
   function isNewItem() {
      return false;
   }


   function defineTabs($options=array()) {

      $ong = array();
      $this->addStandardTab(__CLASS__, $ong, $options);
      $ong['no_all_tab'] = true;
      return $ong;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      switch ($item->getType()) {
         case 'Preference' :
            if (Session::haveRight(self::$rightname, self::PERSONAL)) {
               return __('Personal View');
            }
            break;

         case __CLASS__:
            $ong = array();
            $ong[1] = __('Global View');
            if (Session::haveRight(self::$rightname, self::PERSONAL)) {
               $ong[2] = __('Personal View');
            }
            return $ong;
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
         case 'Preference' :
            self::showForUser(Session::getLoginUserID());
            return true;

         case __CLASS__ :
            switch ($tabnum) {
               case 1 :
                  $item->showFormGlobal($_GET['_target'], $_GET["displaytype"]);
                  return true;

               case 2 :
                  Session::checkRight(self::$rightname, self::PERSONAL);
                  $item->showFormPerso($_GET['_target'], $_GET["displaytype"]);
                  return true;
            }
      }
      return false;
   }


   /**
    * @since version 0.85
    *
    * @see commonDBTM::getRights()
   **/
   function getRights($interface='central') {

      //TRANS: short for : Search result user display
      $values[self::PERSONAL]  = array('short' => __('User display'),
                                       'long'  => __('Search result user display'));
      //TRANS: short for : Search result default display
      $values[self::GENERAL]  =  array('short' => __('Default display'),
                                       'long'  => __('Search result default display'));

      return $values;
   }

}
