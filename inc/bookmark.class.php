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

/**
 * Bookmark class
**/
class Bookmark extends CommonDBTM {

   // From CommonGLPI
   public $taborientation          = 'horizontal';
   public  $auto_message_on_action = false;
   protected $displaylist          = false;

   static $rightname               = 'bookmark_public';

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


   /**
    * @since version 0.85
    *
    * @see CommonDBTM::getSpecificMassiveActions()
   **/
   function getSpecificMassiveActions($checkitem=NULL) {

      $actions[__CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'move_bookmark'] = __('Move');
      return $actions;
   }


   /**
    * @since version 0.85
    *
    * @see CommonDBTM::showMassiveActionsSubForm()
   **/
   static function showMassiveActionsSubForm(MassiveAction $ma) {
      global $CFG_GLPI;

      switch ($ma->getAction()) {
         case 'move_bookmark' :
            $values             = array('after'  => __('After'),
                                        'before' => __('Before'));
            Dropdown::showFromArray('move_type', $values, array('width' => '20%'));

            $param              = array('name'  => "bookmarks_id_ref",
                                        'width' => '50%');
            $param['condition'] = "(`is_private`='1' AND `users_id`='".Session::getLoginUserID()."') ";
            $param['entity']    = -1;
            Bookmark::dropdown($param);
            echo "<br><br>\n";
            echo Html::submit(_x('button', 'Move'), array('name' => 'massiveaction'))."</span>";
            return true;
      }
      return parent::showMassiveActionsSubForm($ma);
   }


   /**
    * @since version 0.85
    *
    * @see CommonDBTM::processMassiveActionsForOneItemtype()
   **/
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {
      global $DB;

      switch ($ma->getAction()) {
         case 'move_bookmark' :
            $input = $ma->getInput();
            if ($item->moveBookmark($ids, $input['bookmarks_id_ref'],
                                    $input['move_type'])) {
               $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_OK);
            } else {
               $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
            }
            return;
      }
      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }


   /**
    * Special case: a private bookmark has entities_id==-1 => we cannot check it
    * @see CommonDBTM::canCreateItem()
    *
    * @since version 0.85
   **/
   function canCreateItem() {

      if (($this->fields['is_private'] == 1)
          && ($this->fields['users_id'] == Session::getLoginUserID())) {
         return true;
      }
      return parent::canCreateItem();
   }


   /**
    *  Special case: a private bookmark has entities_id==-1 => we cannot check it
    * @see CommonDBTM::canViewItem()
    *
    * @since version 0.85
   **/
   function canViewItem() {

      if (($this->fields['is_private'] == 1)
          && ($this->fields['users_id'] == Session::getLoginUserID())) {
         return true;
      }
      return parent::canViewItem();
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
            if (self::canView()) {
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
            $item->showBookmarkList($_GET['_target'], $is_private);
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
         $this->check($ID, UPDATE);
      } else {
         $this->check(-1, CREATE);
      }

      echo '<br>';
      echo "<form method='post' name='form_save_query' action='".$_SERVER['PHP_SELF']."'>";
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

      echo "<table class='tab_cadre' width='".self::WIDTH."px'>";
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

      if (static::canCreate()) {
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
         echo "<input type='submit' name='purge' value=\""._sx('button', 'Delete permanently')."\"
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
                                    'delete_search_count2',
                                    'start', '_glpi_csrf_token');
            foreach ($fields_toclean as $field) {
               if (isset($query_tab[$field])) {
                  unset($query_tab[$field]);
               }
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
            if (isset($query_tab_save['criteria']) && count($query_tab_save['criteria'])) {
               unset($query_tab['criteria']);
               $new_key = 0;
               foreach ($query_tab_save['criteria'] as $key => $val) {
                  if (($val['field'] != 'view') && ($val['field'] != 'all')
                      && (!isset($opt[$val['field']])
                          || (isset($opt[$val['field']]['nosearch'])
                              && $opt[$val['field']]['nosearch']))) {
                     $partial_load = true;
                  } else {
                     $query_tab['criteria'][$new_key] = $val;
                     $new_key++;
                  }
               }
            }

            // Meta search
            if (isset($query_tab_save['metacriteria']) && count($query_tab_save['metacriteria'])) {
               $meta_ok = Search::getMetaItemtypeAvailable($query_tab['itemtype']);

               unset($query_tab['metacriteria']);

               $new_key = 0;
               foreach ($query_tab_save['metacriteria'] as $key => $val) {
                  $opt = Search::getCleanedOptions($val['itemtype']);
                  // Use if meta type is valid and option available
                  if (!in_array($val['itemtype'], $meta_ok)
                      || !isset($opt[$val['field']])) {
                     $partial_load = true;
                  } else {
                     $query_tab['metacriteria'][$new_key] = $val;
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
            echo "window.parent.location.href='$url';";
            echo "</script>";
            exit();
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
                      AND `".$this->getTable()."`.`id` = `glpi_bookmarks_users`.`bookmarks_id`
                      AND `glpi_bookmarks_users`.`users_id` = '".Session::getLoginUserID()."')
                WHERE ";

      if ($is_private) {
         $query .= "(`".$this->getTable()."`.`is_private`='1'
                     AND `".$this->getTable()."`.`users_id`='".Session::getLoginUserID()."') ";
      } else {
         $query .= "(`".$this->getTable()."`.`is_private`='0' ".
                     getEntitiesRestrictRequest("AND", $this->getTable(), "", "", true) . ")";
      }

      $query .= " ORDER BY `itemtype`, `name`";

      // get bookmarks
      $bookmarks = array();
      if ($result = $DB->query($query)) {
         if ($numrows = $DB->numrows($result)) {
            while ($data = $DB->fetch_assoc($result)) {
               $bookmarks[$data['id']] = $data;
            }
         }
      }

      $ordered_bookmarks = array();

      // get personal order
      if ($is_private) {
         $user = new User();
         $personalorderfield = 'privatebookmarkorder';

         if ($user->getFromDB(Session::getLoginUserID())) {
            $personalorder = importArrayFromDB($user->fields[$personalorderfield]);
         }
         if (!is_array($personalorder)) {
            $personalorder = array();
         }

         // Add bookmarks on personal order
         if (count($personalorder)) {
            foreach ($personalorder as $val) {
               if (isset($bookmarks[$val])) {
                  $ordered_bookmarks[$val] = $bookmarks[$val];
                  unset($bookmarks[$val]);
               }
            }
         }
      }
      // Add unsaved in order bookmarks
      if (count($bookmarks)) {
         foreach ($bookmarks as $key => $val) {
            $ordered_bookmarks[$key] = $val;
         }
      }
      if ($is_private) {
         // New bookmark : save order
         $store_bookmark = array_keys($ordered_bookmarks);
         $user->update(array('id'                => Session::getLoginUserID(),
                             $personalorderfield => exportArrayToDB($store_bookmark)));
      }

      $rand    = mt_rand();
      $numrows = $DB->numrows($result);
      Html::openMassiveActionsForm('mass'.__CLASS__.$rand);

      echo "<div class='center' id='tabsbody' >";
      $maactions = array('purge' => _x('button', 'Delete permanently'));
      if ($is_private) {
         $maactions[__CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'move_bookmark'] = __('Move');
      }
      $massiveactionparams = array('num_displayed'     => min($_SESSION['glpilist_limit'], $numrows),
                                    'container'        => 'mass'.__CLASS__.$rand,
                                    'width'            => 600,
                                    'extraparams'      => array('is_private' => $is_private),
                                    'height'           => 200,
                                    'specific_actions' => $maactions);

      // No massive action on bottom

      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr>";
      echo "<th>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand)."</th>";
      echo "<th class='center' colspan='2'>"._n('Bookmark', 'Bookmarks', Session::getPluralNumber())."</th>";
      echo "<th width='20px'>&nbsp;</th>";
      echo "<th>".__('Default view')."</th>";
      $colspan = 5;
      if ($is_private) {
         $colspan+=2;
         echo "<th colspan='2'>&nbsp;</th>";
      }
      echo "</tr>";

      if ($totalcount = count($ordered_bookmarks)) {
         $current_type      = -1;
         $number            = 0;
         $current_type_name = NOT_AVAILABLE;
         foreach ($ordered_bookmarks as $key => $this->fields) {
            $number ++;
            if ($current_type != $this->fields['itemtype']) {
               $current_type      = $this->fields['itemtype'];
               $current_type_name = NOT_AVAILABLE;

               if ($current_type == "AllAssets") {
                  $current_type_name = __('Global');
               } else if ($item = getItemForItemtype($current_type)) {
                  $current_type_name = $item->getTypeName(1);
               }
            }
            $canedit = $this->canEdit($this->fields["id"]);

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
               echo "<a href=\"".$CFG_GLPI['root_doc']."/front/bookmark.php?action=edit&amp;id=".
                      $this->fields["id"]."\" alt='"._sx('button', 'Update')."'>".
                      $this->fields["name"]."</a>";
            } else {
               echo $this->fields["name"];
            }
            echo "</td>";

            echo "<td><a href=\"".$CFG_GLPI['root_doc']."/front/bookmark.php?action=load&amp;id=".
                       $this->fields["id"]."\" class='vsubmit'>".__('Load')."</a>";
            echo "</td>";
            echo "<td class='center'>";
            if ($this->fields['type'] == self::SEARCH) {
               if (is_null($this->fields['IS_DEFAULT'])) {
                  echo "<a href=\"".$CFG_GLPI['root_doc']."/front/bookmark.php?action=edit&amp;".
                         "mark_default=1&amp;id=".$this->fields["id"]."\" alt=\"".
                         __s('Not default search')."\" itle=\"".__s('Not default search')."\">".
                         "<img src=\"".$CFG_GLPI['root_doc']."/pics/bookmark_record.png\" class='pointer'></a>";
               } else {
                  echo "<a href=\"".$CFG_GLPI['root_doc']."/front/bookmark.php?action=edit&amp;".
                         "mark_default=0&amp;id=".$this->fields["id"]."\" alt=\"".
                         __s('Default search')."\" title=\"".__s('Default search')."\">".
                         "<img src=\"".$CFG_GLPI['root_doc']."/pics/bookmark_default.png\" class='pointer'></a>";
               }
            }
            echo "</td>";
            if ($is_private) {
               if ($number != 1) {
                  echo "<td>";
                  Html::showSimpleForm($this->getSearchURL(), array('action' => 'up'), '',
                                       array('id'      => $this->fields["id"]),
                                       $CFG_GLPI["root_doc"]."/pics/deplier_up.png");
                  echo "</td>";
               } else {
                  echo "<td>&nbsp;</td>";
               }

               if ($number != $totalcount) {
                  echo "<td>";
                  Html::showSimpleForm($this->getSearchURL(), array('action' => 'down'), '',
                                       array('id'      => $this->fields["id"]),
                                       $CFG_GLPI["root_doc"]."/pics/deplier_down.png");
                  echo "</td>";
               } else {
                  echo "<td>&nbsp;</td>";
               }
            }
            echo "</tr>";
            $first = false;
         }
         echo "</table></div>";

         if ($is_private
             || Session::haveRight('bookmark_public', PURGE)) {
            $massiveactionparams['ontop']       = false;
            $massiveactionparams['forcecreate'] = true;
            Html::showMassiveActions($massiveactionparams);
         }
      } else {
         echo "<tr class='tab_bg_1'><td colspan='$colspan'>";
         _e('You have not recorded any bookmarks yet');
         echo "</td></tr></table>";
      }
      Html::closeForm();

   }


   /**
    * Modify rule's ranking and automatically reorder all rules
    *
    * @since version 0.85
    *
    * @param $ID     the rule ID whose ranking must be modified
    * @param $action up or down
   **/
   function changeBookmarkOrder($ID, $action) {

      $user = new User();
      $personalorderfield = 'privatebookmarkorder';
      if ($user->getFromDB(Session::getLoginUserID())) {
         $personalorder = importArrayFromDB($user->fields[$personalorderfield]);
      }
      if (!is_array($personalorder)) {
         $personalorder = array();
      }

      if (in_array($ID, $personalorder)) {
         $pos = array_search($ID, $personalorder);
         switch($action) {
            case 'up' :
               if (isset($personalorder[$pos-1])) {
                  $personalorder[$pos] = $personalorder[$pos-1];
                  $personalorder[$pos-1] = $ID;
               }
               break;

            case 'down' :
               if (isset($personalorder[$pos+1])) {
                  $personalorder[$pos] = $personalorder[$pos+1];
                  $personalorder[$pos+1] = $ID;
               }
               break;
         }
         $user->update(array('id'                => Session::getLoginUserID(),
                             $personalorderfield => exportArrayToDB($personalorder)));
      }
   }


   /**
    * Move a bookmark in an ordered collection
    *
    * @since version 0.85
    *
    * @param $items    array      of the rules ID to move
    * @param $ref_ID   integer    of the rule position  (0 means all, so before all or after all)
    * @param $action   string     of move : after or before ( default 'after')
    *
    * @return true if all ok
   **/
   function moveBookmark($items= array(), $ref_ID, $action='after') {
      global $DB;

      if (count($items)) {
         // Clean IDS : drop ref_ID
         if (isset($items[$ref_ID])) {
            unset($items[$ref_ID]);
         }

         $user               = new User();
         $personalorderfield = 'privatebookmarkorder';
         if ($user->getFromDB(Session::getLoginUserID())) {
            $personalorder = importArrayFromDB($user->fields[$personalorderfield]);
         }
         if (!is_array($personalorder)) {
            return false;
         }

         $newpersonalorder = array();
         foreach ($personalorder as $val) {
            // Found item
            if ($val == $ref_ID) {
               // Add after so add ref ID
               if ($action == 'after') {
                  $newpersonalorder[] = $ref_ID;
               }
               foreach ($items as $val2) {
                  $newpersonalorder[] = $val2;
               }
               if ($action == 'before') {
                  $newpersonalorder[] = $ref_ID;
               }
            } else if (!isset($items[$val])) {
               $newpersonalorder[] = $val;
            }
         }
         $user->update(array('id'                => Session::getLoginUserID(),
                             $personalorderfield => exportArrayToDB($newpersonalorder)));
         return true;
      }
      return false;
   }


   /**
    * Display bookmark buttons
    *
    * @param $type      bookmark type to use
    * @param $itemtype  device type of item where is the bookmark (default 0)
   **/
   static function showSaveButton($type, $itemtype=0) {
      global $CFG_GLPI;


      echo " <a href='#' onClick=\"".Html::jsGetElementbyID('bookmarksave').".dialog('open');\">";
      echo "<img src='".$CFG_GLPI["root_doc"]."/pics/bookmark_record.png'
             title=\"".__s('Save as bookmark')."\" alt=\"".__s('Save as bookmark')."\"
             class='calendrier pointer'>";
      echo "</a>";
      Ajax::createIframeModalWindow('bookmarksave',
                                    $CFG_GLPI["root_doc"]."/front/bookmark.php?type=$type".
                                          "&action=edit&itemtype=$itemtype&".
                                          "url=".rawurlencode($_SERVER["REQUEST_URI"]),
                                    array('title'         => __('Save as bookmark'),
                                          'reloadonclose' => true));
   }


}
