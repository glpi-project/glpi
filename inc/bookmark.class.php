<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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

/**
 * Bookmark class
 */
class Bookmark extends CommonDBTM {

   var       $auto_message_on_action = false;
   protected $displaylist            = false;

   const WIDTH  = 750;
   const SEARCH = 1; //SEARCH SYSTEM bookmark
   const URI    = 2;


   /**
    * @since version 0.84
    *
    * @return string
   **/
   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }


   static function canCreate() {
      return true;
   }

   static function canView() {
      return true;
   }


   function canViewItem() {
      return ($this->fields['users_id'] == Session::getLoginUserID()
              || (Session::haveRight('bookmark_public', 'r')
                  && Session::haveAccessToEntity($this->fields['entities_id'], $this->fields['is_recursive'])));
   }
   function canUpdateItem() {

      return ($this->fields['users_id'] == Session::getLoginUserID()
              || (!$this->fields['is_private']
                  && Session::haveRight('bookmark_public', 'w')
                  && Session::haveAccessToEntity($this->fields['entities_id'])));
   }
   
   function isNewItem() {
      /// For tabs management : force isNewItem
      return false;
   }


   function defineTabs($options=array()) {

      $ong               = array();
      $this->addStandardTab(__CLASS__, $ong, $options);
      $ong['no_all_tab'] = true;
      return $ong;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      switch ($item->getType()) {
         case __CLASS__:
            $ong     = array();
            $ong[1]  = __('Personal');
            if (Session::haveRight('bookmark_public','r')) {
               $ong[2] = __('Public');
            }
            return $ong;
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
         case __CLASS__ :
            $is_private = 1;
            if ($tabnum == 2) {
               $is_private = 0;
            }
            $item->showBookmarkList($_POST['target'], $is_private);
            return true;
      }
      return false;
   }


   function prepareInputForAdd($input) {

      if (!isset($input['url']) || !isset($input['type'])) {
         return false;
      }

      $taburl = parse_url(rawurldecode($input['url']));

      $index  = strpos($taburl["path"], "plugins");
      if (!$index) {
         $index = strpos($taburl["path"], "front");
      }
      $input['path'] = Toolbox::substr($taburl["path"], $index,
                                       Toolbox::strlen($taburl["path"]) - $index);

      $query_tab = array();

      if (isset($taburl["query"])) {
         parse_str($taburl["query"], $query_tab);
      }

      $input['query'] = Toolbox::append_params($this->prepareQueryToStore($input['type'],
                                               $query_tab, $input['itemtype']));

      return $input;
   }


   function pre_updateInDB() {

      // Set new user if initial user have been deleted
      if (($this->fields['users_id'] == 0)
          && $uid=Session::getLoginUserID()) {
         $this->input['users_id']  = $uid;
         $this->fields['users_id'] = $uid;
         $this->updates[]          = "users_id";
      }
   }


   function post_getEmpty() {

      $this->fields["users_id"]     = Session::getLoginUserID();
      $this->fields["is_private"]   = 1;
      $this->fields["is_recursive"] = 0;
      $this->fields["entities_id"]  = $_SESSION["glpiactive_entity"];
   }


   function cleanDBonPurge() {
      global $DB;

      $query="DELETE
              FROM `glpi_bookmarks_users`
              WHERE `bookmarks_id` = '".$this->fields['id']."'";
      $DB->query($query);
   }


   /**
    * Print the bookmark form
    *
    * @param $ID        integer ID of the item
    * @param $options   array
    *     - target for the Form
    *     - type bookmark type when adding a new bookmark
    *     - url when adding a new bookmark
    *     - itemtype when adding a new bookmark
   **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI;

      $ID = $this->fields['id'];

      // Only an edit form : always check w right
      if ($ID > 0) {
         $this->check($ID,'w');
      } else {
         $this->check(-1,'w');
      }

      echo '<br>';
      echo "<form method='post' name='form_save_query' action='".$CFG_GLPI['root_doc'].
             "/front/popup.php'>";
      echo "<div class='center'>";
      if (isset($options['itemtype'])) {
         echo "<input type='hidden' name='itemtype' value='".$options['itemtype']."'>";
      }
      if (isset($options['type']) && ($options['type'] != 0)) {
         echo "<input type='hidden' name='type' value='".$options['type']."'>";
      }

      if (isset($options['url'])) {
         echo "<input type='hidden' name='url' value='" . rawurlencode($options['url']) . "'>";
      }

      echo "<table class='tab_cadre_report' width='".self::WIDTH."px'>";
      echo "<tr><th>&nbsp;</th><th>";
      if ($ID > 0) {
         //TRANS: %1$s is the Itemtype name and $2$d the ID of the item
         printf(__('%1$s - ID %2$d'), $this->getTypeName(1), $ID);
      } else {
         _e('New item');
      }
      echo "</th></tr>";

      echo "<tr><td class='tab_bg_1'>".__('Name')."</td>";
      echo "<td class='tab_bg_1'>";
      Html::autocompletionTextField($this, "name", array('user' => $this->fields["users_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td>".__('Type')."</td>";
      echo "<td>";

      if (Session::haveRight('bookmark_public', 'w')) {
         Dropdown::showPrivatePublicSwitch($this->fields["is_private"],
                                           $this->fields["entities_id"],
                                           $this->fields["is_recursive"]);
      } else {
         if ($this->fields["is_private"]) {
            _e('Private');
         } else {
            _e('Public');
         }
      }
      echo "</td></tr>";

      if ($ID <= 0) { // add
         echo "<tr>";
         echo "<td class='tab_bg_2 top' colspan='2'>";
         echo "<input type='hidden' name='users_id' value='".$this->fields['users_id']."'>";
         echo "<div class='center'>";
         echo "<input type='submit' name='add' value=\""._sx('button','Add')."\" class='submit'>";
         echo "</div></td></tr>";

      } else {
         echo "<tr>";
         echo "<td class='tab_bg_2 top' colspan='2'>";
         echo "<input type='hidden' name='id' value='$ID'>";
         echo "<input type='submit' name='update' value=\"".__s('Save')."\" class='submit'>";
         echo "</td></tr><tr><td class='tab_bg_2 right' colspan='2'>";
         echo "<input type='submit' name='delete' value=\""._sx('button', 'Delete permanently')."\"
                class='submit'>";
         echo "</td></tr>";
      }
      echo "</table></div>";
      Html::closeForm();
   }


   /**
    * Prepare query to store depending of the type
    *
    * @param $type         bookmark type
    * @param $query_tab    parameters array
    * @param $itemtype     device type (default 0)
    *
    * @return clean query array
   **/
   function prepareQueryToStore($type, $query_tab, $itemtype=0) {

      switch ($type) {
         case self::SEARCH :
            $fields_toclean = array('add_search_count', 'add_search_count2', 'delete_search_count',
                                    'delete_search_count2', 'glpisearchcount', 'glpisearchcount2',
                                    'start', '_glpi_csrf_token');
            foreach ($fields_toclean as $field) {
               if (isset($query_tab[$field])) {
                  unset($query_tab[$field]);
               }
            }

            // Manage glpisearchcount / dclean if needed + store
            if (isset($_SESSION["glpisearchcount"][$itemtype])) {
               $query_tab['glpisearchcount'] = $_SESSION["glpisearchcount"][$itemtype];
            } else {
               $query_tab['glpisearchcount'] = 1;
            }
            // Manage glpisearchcount2 / dclean if needed + store
            if (isset($_SESSION["glpisearchcount2"][$itemtype])) {
               $query_tab['glpisearchcount2'] = $_SESSION["glpisearchcount2"][$itemtype];
            } else {
               $query_tab['glpisearchcount2'] = 0;
            }
            break;
      }
      return $query_tab;
   }


   /**
    * Prepare query to use depending of the type
    *
    * @param $type         bookmark type
    * @param $query_tab    parameters array
    *
    * @return prepared query array
   **/
   function prepareQueryToUse($type, $query_tab) {

      switch ($type) {
         case self::SEARCH :
            // Check if all datas are valid
            $opt = Search::getCleanedOptions($this->fields['itemtype']);

            $query_tab_save = $query_tab;
            $partial_load   = false;
            // Standard search
            if (isset($query_tab_save['field']) && count($query_tab_save['field'])) {
               unset($query_tab['field']);
               unset($query_tab['searchtype']);
               unset($query_tab['contains']);
               unset($query_tab['link']);
               $new_key = 0;
               foreach ($query_tab_save['field'] as $key => $val) {
                  if (($val != 'view') && ($val != 'all')
                      && (!isset($opt[$val])
                          || (isset($opt[$val]['nosearch']) && $opt[$val]['nosearch']))) {
                     $query_tab['glpisearchcount']--;
                     $partial_load = true;
                  } else {
                     $query_tab['field'][$new_key] = $val;
                     if (isset($query_tab_save['searchtype'])
                         && isset($query_tab_save['searchtype'][$key])) {
                        $query_tab['searchtype'][$new_key] = $query_tab_save['searchtype'][$key];
                     }
                     $query_tab['contains'][$new_key] = $query_tab_save['contains'][$key];
                     if (isset($query_tab_save['link'][$key])) {
                        $query_tab['link'][$new_key] = $query_tab_save['link'][$key];
                     }
                     $new_key++;
                  }
               }
            }
            if ($query_tab['glpisearchcount'] == 0) {
               $query_tab['glpisearchcount'] = 1;
            }

            // Meta search
            if (isset($query_tab_save['itemtype2']) && count($query_tab_save['itemtype2'])
                && isset($query_tab_save['field2'])) {
               $meta_ok = Search::getMetaItemtypeAvailable($query_tab['itemtype']);

               unset($query_tab['field2']);
               unset($query_tab['searchtype2']);
               unset($query_tab['contains2']);
               unset($query_tab['link2']);
               unset($query_tab['itemtype2']);
               $new_key = 0;
               foreach ($query_tab_save['field2'] as $key => $val) {
                  $opt = Search::getCleanedOptions($query_tab_save['itemtype2'][$key]);
                  // Use if meta type is valid and option available
                  if (!in_array($query_tab_save['itemtype2'][$key], $meta_ok)
                      || !isset($opt[$val])) {
                     $query_tab['glpisearchcount2']--;
                     $partial_load = true;
                  } else {
                     $query_tab['field2'][$new_key] = $val;
                     if (isset($query_tab_save['searchtype2'])
                         && isset($query_tab_save['searchtype2'][$key])) {
                        $query_tab['searchtype2'][$new_key] = $query_tab_save['searchtype2'][$key];
                     }
                     $query_tab['contains2'][$new_key] = $query_tab_save['contains2'][$key];
                     $query_tab['link2'][$new_key]     = $query_tab_save['link2'][$key];
                     $query_tab['itemtype2'][$new_key] = $query_tab_save['itemtype2'][$key];
                     $new_key++;
                  }
               }
            }

            // Display message
            if ($partial_load) {
               Session::addMessageAfterRedirect(__('Partial load of the bookmark.'), false, ERROR);
            }
            // add reset value
            $query_tab['reset'] = 'reset';
            break;
      }
      return $query_tab;
   }


   /**
    * load a bookmark
    *
    * @param $ID                 ID of the bookmark
    * @param $opener    boolean  load bookmark in opener window ? false -> current window
    *                            (true by default)
    *
    * @return nothing
   **/
   function load($ID, $opener=true) {
      global $CFG_GLPI;

      if ($params = $this->getParameters($ID)) {
         $url  = $CFG_GLPI['root_doc']."/".rawurldecode($this->fields["path"]);
         $url .= "?".Toolbox::append_params($params);

         if ($opener) {
            echo "<script type='text/javascript' >\n";
            echo "window.opener.location.href='$url';";
            echo "</script>";
         } else {
            Html::redirect($url);
         }
      }
   }


   /**
    * get bookmark parameters
    *
    * @param $ID ID of the bookmark
    *
    * @return nothing
   **/
   function getParameters($ID) {

      if ($this->getFromDB($ID)) {
         $query_tab = array();
         parse_str($this->fields["query"], $query_tab);
         return $this->prepareQueryToUse($this->fields["type"], $query_tab);
      }
      return false;
   }


   /**
    * Mark bookmark as default view for the currect user
    *
    * @param $ID ID of the bookmark
    *
    * @return nothing
   **/
   function mark_default($ID) {
      global $DB;

      // Get bookmark / Only search bookmark
      if ($this->getFromDB($ID)
          && ($this->fields['type'] == self::SEARCH)) {
         $dd = new Bookmark_User();
         // Is default view for this itemtype already exists ?
         $query = "SELECT `id`
                   FROM `glpi_bookmarks_users`
                   WHERE `users_id` = '".Session::getLoginUserID()."'
                         AND `itemtype` = '".$this->fields['itemtype']."'";

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result) > 0) {
               // already exists update it
               $updateID = $DB->result($result, 0, 0);
               $dd->update(array('id'           => $updateID,
                                 'bookmarks_id' => $ID));
            } else {
               $dd->add(array('bookmarks_id' => $ID,
                              'users_id'     => Session::getLoginUserID(),
                              'itemtype'     => $this->fields['itemtype']));
            }
         }
      }
   }


   /**
    * Mark bookmark as default view for the currect user
    *
    * @param $ID ID of the bookmark
    *
    * @return nothing
   **/
   function unmark_default($ID) {
      global $DB;

      // Get bookmark / Only search bookmark
      if ($this->getFromDB($ID)
          && ($this->fields['type'] == self::SEARCH)) {
         $dd = new Bookmark_User();
         // Is default view for this itemtype already exists ?
         $query = "SELECT `id`
                   FROM `glpi_bookmarks_users`
                   WHERE `users_id` = '".Session::getLoginUserID()."'
                         AND `bookmarks_id` = '$ID'
                         AND `itemtype` = '".$this->fields['itemtype']."'";

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result) > 0) {
               // already exists delete it
               $deleteID = $DB->result($result,0,0);
               $dd->delete(array('id' => $deleteID));
            }
         }
      }
   }


   /**
    * Show bookmarks list
    *
    * @param $target       target to use for links
    * @param $is_private   show private of public bookmarks ? (default 1)
    *
    * @return nothing
   **/
   function showBookmarkList($target, $is_private=1) {
      global $DB, $CFG_GLPI;

      if (!$is_private && !static::canView()) {
         return false;
      }

      $query = "SELECT `".$this->getTable()."`.*,
                       `glpi_bookmarks_users`.`id` AS IS_DEFAULT
                FROM `".$this->getTable()."`
                LEFT JOIN `glpi_bookmarks_users`
                  ON (`".$this->getTable()."`.`itemtype` = `glpi_bookmarks_users`.`itemtype`
                      AND `".$this->getTable()."`.`id` = `glpi_bookmarks_users`.`bookmarks_id`)
                WHERE ";

      if ($is_private) {
         $query .= "(`".$this->getTable()."`.`is_private`='1'
                     AND `".$this->getTable()."`.`users_id`='".Session::getLoginUserID()."') ";
      } else {
         $query .= "(`".$this->getTable()."`.`is_private`='0' ".
                     getEntitiesRestrictRequest("AND", $this->getTable(), "", "", true) . ")";
      }

      $query .= " ORDER BY `itemtype`, `name`";

      if ($result = $DB->query($query)) {
         $rand = mt_rand();
         $numrows = $DB->numrows($result);
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);

         echo "<div class='center' id='tabsbody' >";
         $massiveactionparams = array('num_displayed'  => $numrows,
                                      'width'          => 600,
                                      'height'         => 200,
                                      'rand'           => $rand);

//          Html::showMassiveActions(__CLASS__, $massiveactionparams);

         echo "<table class='tab_cadre_fixehov'>";
         echo "<tr>";
         echo "<th>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand)."</th>";
         echo "<th class='center' colspan='2'>".__('Bookmarks')."</th>";
         echo "<th width='20px'>&nbsp;</th>";
         echo "<th>".__('Default view')."</th></tr>";

         if ($numrows) {
            $current_type      = -1;
            $current_type_name = NOT_AVAILABLE;
            while ($this->fields = $DB->fetch_assoc($result)) {
               if ($current_type != $this->fields['itemtype']) {
                  $current_type      = $this->fields['itemtype'];
                  $current_type_name = NOT_AVAILABLE;

                  if ($current_type == "AllAssets") {
                     $current_type_name = __('Global');
                  } else if ($item = getItemForItemtype($current_type)) {
                     $current_type_name = $item->getTypeName(1);
                  }
               }
               $canedit = $this->can($this->fields["id"],"w");

               echo "<tr class='tab_bg_1'>";
               echo "<td width='10px'>";
               if ($canedit) {
                  Html::showMassiveActionCheckBox(__CLASS__, $this->fields["id"]);
               } else {
                  echo "&nbsp;";
               }
               echo "</td>";
               echo "<td>$current_type_name</td>";
               echo "<td>";
               if ($canedit) {
                  echo "<a href=\"".$CFG_GLPI['root_doc']."/front/popup.php?popup=edit_bookmark&amp;id=".
                           $this->fields["id"]."\" alt='".__s('Update')."'>".
                           $this->fields["name"]."</a>";
               } else {
                  echo $this->fields["name"];
               }
               echo "</td>";

               echo "<td><a href=\"".$CFG_GLPI['root_doc']."/front/popup.php?popup=load_bookmark&amp;id=".
                           $this->fields["id"]."\" class='vsubmit'>".__('Load')."</a>";
               echo "</td>";
               echo "<td class='center'>";
               if ($this->fields['type'] == self::SEARCH) {
                  if (is_null($this->fields['IS_DEFAULT'])) {
                     echo "<a href=\"".$CFG_GLPI['root_doc']."/front/popup.php?popup=edit_bookmark&amp;".
                            "mark_default=1&amp;id=".$this->fields["id"]."\" alt=\"".
                            __s('Not default search')."\" itle=\"".__s('Not default search')."\">".
                            "<img src=\"".$CFG_GLPI['root_doc']."/pics/bookmark_grey.png\"></a>";
                  } else {
                     echo "<a href=\"".$CFG_GLPI['root_doc']."/front/popup.php?popup=edit_bookmark&amp;".
                            "mark_default=0&amp;id=".$this->fields["id"]."\" alt=\"".
                            __s('Default search')."\" title=\"".__s('Default search')."\">".
                            "<img src=\"".$CFG_GLPI['root_doc']."/pics/bookmark.png\"></a>";
                  }
               }
               echo "</td></tr>";
            }
            echo "</table></div>";

            if ($is_private || Session::haveRight('bookmark_public', 'w')) {
               $massiveactionparams['ontop']       = false;
               $massiveactionparams['forcecreate'] = true;
               Html::showMassiveActions(__CLASS__, $massiveactionparams);
            }
         } else {
            echo "<tr class='tab_bg_1'><td colspan='5'>";
            _e('You have not recorded any bookmarks yet');
            echo "</td></tr></table>";
         }
         Html::closeForm();
      }
   }


   /**
    * Display bookmark buttons
    *
    * @param $type      bookmark type to use
    * @param $itemtype  device type of item where is the bookmark (default 0)
   **/
   static function showSaveButton($type, $itemtype=0) {
      global $CFG_GLPI;

      echo " <a href='#' onClick=\"var w = window.open('".$CFG_GLPI["root_doc"].
              "/front/popup.php?popup=edit_bookmark&amp;type=$type&amp;itemtype=$itemtype&amp;url=".
              rawurlencode($_SERVER["REQUEST_URI"])."' ,'glpipopup', 'height=500, width=".
              (self::WIDTH+250).", top=100, left=100, scrollbars=yes' );w.focus();\">";
      echo "<img src='".$CFG_GLPI["root_doc"]."/pics/bookmark_record.png'
             title=\"".__s('Save as bookmark')."\" alt=\"".__s('Save as bookmark')."\"
             class='calendrier'>";
      echo "</a>";
   }

}
?>
