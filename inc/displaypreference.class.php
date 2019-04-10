<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
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

      $result = $DB->request([
         'SELECT' => ['MAX' => 'rank AS maxrank'],
         'FROM'   => $this->getTable(),
         'WHERE'  => [
            'itemtype'  => $input['itemtype'],
            'users_id'  => $input['users_id']
         ]
      ])->next();
      $input['rank'] = $result['maxrank'] + 1;
      return $input;
   }


   /**
    * @since 0.85
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
                     if ($item->deleteByCriteria(['users_id' => $input['users_id'],
                                                       'itemtype' => $id])) {
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

      $iterator = $DB->request([
         'FROM'   => self::getTable(),
         'WHERE'  => [
            'itemtype'  => $itemtype,
            'OR'        => [
               ['users_id' => $user_id],
               ['users_id' => 0]
            ]
         ],
         'ORDER'  => ['users_id', 'rank']
      ]);

      $default_prefs = [];
      $user_prefs = [];

      while ($data = $iterator->next()) {
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

      $iterator = $DB->request([
         'FROM'   => self::getTable(),
         'WHERE'  => [
            'itemtype'  => $input['itemtype'],
            'users_id'  => 0
         ]
      ]);

      if (count($iterator)) {
         while ($data = $iterator->next()) {
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
      $result = $DB->request([
         'SELECT' => 'rank',
         'FROM'   => $this->getTable(),
         'WHERE'  => ['id' => $input['id']]
      ])->next();
      $rank1  = $result['rank'];

      // Get previous or next item
      $where = [];
      $order = 'rank ';
      switch ($action) {
         case "up" :
            $where['rank'] = ['<', $rank1];
            $order .= 'DESC';
            break;

         case "down" :
            $where['rank'] = ['>', $rank1];
            $order .= 'ASC';
            break;

         default :
            return false;
      }

      $result = $DB->request([
         'SELECT' => ['id', 'rank'],
         'FROM'   => $this->getTable(),
         'WHERE'  => [
            'itemtype'  => $input['itemtype'],
            'users_id'  => $input["users_id"]
         ] + $where,
         'ORDER'  => $order,
         'LIMIT'  => 1
      ])->next();

      $rank2  = $result['rank'];
      $ID2    = $result['id'];

      // Update items
      $DB->update(
         $this->getTable(),
         ['rank' => $rank2],
         ['id' => $input['id']]
      );

      $DB->update(
         $this->getTable(),
         ['rank' => $rank1],
         ['id' => $ID2]
      );
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

      $item = null;
      if ($itemtype != 'AllAssets') {
         $item = getItemForItemtype($itemtype);
      }

      $IDuser = Session::getLoginUserID();

      echo "<div class='center' id='tabsbody' >";
      // Defined items
      $iterator = $DB->request([
         'FROM'   => $this->getTable(),
         'WHERE'  => [
            'itemtype'  => $itemtype,
            'users_id'  => $IDuser
         ],
         'ORDER'  => 'rank'
      ]);
      $numrows = count($iterator);

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
         $values = [];
         foreach ($searchopt as $key => $val) {
            if (!is_array($val)) {
               $group = $val;
            } else if (count($val) === 1) {
               $group = $val['name'];
            } else if ($key != 1
                       && !in_array($key, $already_added)
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
            while ($data = $iterator->next()) {
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
                     echo "<button type='submit' name='up'".
                         " title=\"".__s('Bring up')."\"".
                         " class='unstyled pointer'><i class='fa fa-arrow-up'></i></button>";
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
                     echo "<button type='submit' name='down'".
                         " title=\"".__s('Bring down')."\"".
                         " class='unstyled pointer'><i class='fa fa-arrow-down'></i></button>";
                     Html::closeForm();
                     echo "</td>\n";

                  } else {
                     echo "<td>&nbsp;</td>";
                  }

                  if (!isset($searchopt[$data["num"]]["noremove"]) || $searchopt[$data["num"]]["noremove"] !== true) {
                     echo "<td class='center middle'>";
                     echo "<form method='post' action='$target'>";
                     echo "<input type='hidden' name='id' value='".$data["id"]."'>";
                     echo "<input type='hidden' name='users_id' value='$IDuser'>";
                     echo "<input type='hidden' name='itemtype' value='$itemtype'>";
                     echo "<button type='submit' name='purge'".
                           " title=\""._sx('button', 'Delete permanently')."\"".
                           " class='unstyled pointer'><i class='fa fa-times-circle'></i></button>";
                     Html::closeForm();
                     echo "</td>\n";
                  } else {
                     echo "<td>&nbsp;</td>\n";
                  }
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

      $searchopt = Search::getCleanedOptions($itemtype);
      if (!is_array($searchopt)) {
         return false;
      }
      $IDuser = 0;

      $item = null;
      if ($itemtype != 'AllAssets') {
         $item = getItemForItemtype($itemtype);
      }

      $global_write = Session::haveRight(self::$rightname, self::GENERAL);

      echo "<div class='center' id='tabsbody' >";
      // Defined items
      $iterator = $DB->request([
         'FROM'   => $this->getTable(),
         'WHERE'  => [
            'itemtype'  => $itemtype,
            'users_id'  => $IDuser
         ],
         'ORDER'  => 'rank'
      ]);
      $numrows = count($iterator);

      echo "<table class='tab_cadre_fixehov'><tr><th colspan='4'>";
      echo __('Select default items to show')."</th></tr>\n";

      if ($global_write) {
         $already_added = self::getForTypeUser($itemtype, $IDuser);
         echo "<tr class='tab_bg_1'><td colspan='4' class='center'>";
         echo "<form method='post' action='$target'>";
         echo "<input type='hidden' name='itemtype' value='$itemtype'>";
         echo "<input type='hidden' name='users_id' value='$IDuser'>";
         $group  = '';
         $values = [];
         foreach ($searchopt as $key => $val) {
            if (!is_array($val)) {
               $group = $val;
            } else if (count($val) === 1) {
               $group = $val['name'];
            } else if ($key != 1
                       && !in_array($key, $already_added)
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
         while ($data = $iterator->next()) {

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
                     echo "<button type='submit' name='up'".
                         " title=\"".__s('Bring up')."\"".
                         " class='unstyled pointer'><i class='fa fa-arrow-up'></i></button>";
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
                     echo "<button type='submit' name='down'".
                         " title=\"".__s('Bring down')."\"".
                         " class='unstyled pointer'><i class='fa fa-arrow-down'></i></button>";
                     Html::closeForm();
                     echo "</td>";

                  } else {
                     echo "<td>&nbsp;</td>\n";
                  }

                  if (!isset($searchopt[$data["num"]]["noremove"]) || $searchopt[$data["num"]]["noremove"] !== true) {
                     echo "<td class='center middle'>";
                     echo "<form method='post' action='$target'>";
                     echo "<input type='hidden' name='id' value='".$data["id"]."'>";
                     echo "<input type='hidden' name='users_id' value='$IDuser'>";
                     echo "<input type='hidden' name='itemtype' value='$itemtype'>";
                     echo "<button type='submit' name='purge'".
                           " title=\""._sx('button', 'Delete permanently')."\"".
                           " class='unstyled pointer'><i class='fa fa-times-circle'></i></button>";
                     Html::closeForm();
                     echo "</td>\n";
                  } else {
                     echo "<td>&nbsp;</td>\n";
                  }
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

      $iterator = $DB->request([
         'SELECT'  => ['itemtype'],
         'COUNT'   => 'nb',
         'FROM'    => self::getTable(),
         'WHERE'   => [
            'users_id'  => $users_id
         ],
         'GROUPBY' => 'itemtype'
      ]);

      if (count($iterator) > 0) {
         $rand = mt_rand();
         echo "<div class='spaced'>";
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams = ['width'            => 400,
                           'height'           => 200,
                           'container'        => 'mass'.__CLASS__.$rand,
                           'specific_actions' => [__CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'delete_for_user'
                                                       => _x('button', 'Delete permanently')],
                           'extraparams'      => ['massive_action_fields' => ['users_id']]];

         Html::showMassiveActions($massiveactionparams);

         echo Html::hidden('users_id', ['value'                 => $users_id,
                                             'data-glpicore-ma-tags' => 'common']);
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr>";
         echo "<th width='10'>";
         echo Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
         echo "</th>";
         echo "<th colspan='2'>".__('Type')."</th></tr>";
         while ($data = $iterator->next()) {
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
    * @since 0.83
   **/
   function isNewItem() {
      return false;
   }


   function defineTabs($options = []) {

      $ong = [];
      $this->addStandardTab(__CLASS__, $ong, $options);
      $ong['no_all_tab'] = true;
      return $ong;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      switch ($item->getType()) {
         case 'Preference' :
            if (Session::haveRight(self::$rightname, self::PERSONAL)) {
               return __('Personal View');
            }
            break;

         case __CLASS__:
            $ong = [];
            $ong[1] = __('Global View');
            if (Session::haveRight(self::$rightname, self::PERSONAL)) {
               $ong[2] = __('Personal View');
            }
            return $ong;
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

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
    * @since 0.85
    *
    * @see commonDBTM::getRights()
   **/
   function getRights($interface = 'central') {

      //TRANS: short for : Search result user display
      $values[self::PERSONAL]  = ['short' => __('User display'),
                                       'long'  => __('Search result user display')];
      //TRANS: short for : Search result default display
      $values[self::GENERAL]  =  ['short' => __('Default display'),
                                       'long'  => __('Search result default display')];

      return $values;
   }

}
