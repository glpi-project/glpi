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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Bookmark class
 */
class Bookmark extends CommonDBTM {

   var $auto_message_on_action=false;

   function canCreate() {
      return haveRight('bookmark_public', 'w');
   }


   function canView() {
      return haveRight('bookmark_public', 'r');
   }


   function prepareInputForAdd($input) {

      if (!isset($input['url']) || !isset($input['type'])) {
         return false;
      }

      $taburl = parse_url(rawurldecode($input['url']));

      $index = strpos($taburl["path"], "plugins");
      if (!$index) {
         $index = strpos($taburl["path"], "front");
      }
      $input['path'] = utf8_substr($taburl["path"], $index, utf8_strlen($taburl["path"]) - $index);

      $query_tab = array();

      if (isset($taburl["query"])) {
         parse_str($taburl["query"], $query_tab);
      }

      $input['query'] = append_params($this->prepareQueryToStore($input['type'], $query_tab,
                                                                 $input['itemtype']));

      return $input;
   }


   function pre_updateInDB() {

      // Set new user if initial user have been deleted
      if ($this->fields['users_id']==0 && $uid=getLoginUserID()) {
         $this->input['users_id']  = $uid;
         $this->fields['users_id'] = $uid;
         $this->updates[]          = "users_id";
      }
   }


   function post_getEmpty () {

      $this->fields["users_id"]     = getLoginUserID();
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
   * @param $ID integer ID of the item
   * @param $options array
   *     - target for the Form
   *     - type bookmark type when adding a new bookmark
   *     - url when adding a new bookmark
   *     - itemtype when adding a new bookmark
   **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI, $LANG;

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
      if (isset($options['type']) && $options['type'] != 0) {
         echo "<input type='hidden' name='type' value='".$options['type']."'>";
      }

      if (isset($options['url'])) {
         echo "<input type='hidden' name='url' value='" . rawurlencode($options['url']) . "'>";
      }

      echo "<table class='tab_cadre_report'>";
      echo "<tr><th>&nbsp;</th><th>";
      if ($ID>0) {
         echo $LANG['bookmark'][1] . " - " . $LANG['common'][2]." $ID";
      } else {
         echo $LANG['bookmark'][4];
      }
      echo "</th></tr>";

      echo "<tr><td class='tab_bg_1'>".$LANG['common'][16]."&nbsp;:</td>";
      echo "<td class='tab_bg_1'>";
      autocompletionTextField($this, "name", array('user' => $this->fields["users_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td>".$LANG['common'][17]."&nbsp;:</td>";
      echo "<td>";

      if (haveRight("bookmark_public","w")) {
         Dropdown::showPrivatePublicSwitch($this->fields["is_private"],
                                           $this->fields["entities_id"],
                                           $this->fields["is_recursive"]);
      } else {
         if ($this->fields["is_private"]) {
            echo $LANG['common'][77];
         } else {
            echo $LANG['common'][76];
         }
      }
      echo "</td></tr>";

      if ($ID <= 0) { // add
         echo "<tr>";
         echo "<td class='tab_bg_2 top' colspan='2'>";
         echo "<input type='hidden' name='users_id' value='".$this->fields['users_id']."'>";
         echo "<div class='center'>";
         echo "<input type='submit' name='add' value=\"".$LANG['buttons'][8]."\" class='submit'>";
         echo "</div></td></tr>";

      } else {
         echo "<tr>";
         echo "<td class='tab_bg_2 top' colspan='2'>";
         echo "<input type='hidden' name='id' value='$ID'>";
         echo "<div class='center'>";
         echo "<input type='submit' name='update' value=\"".$LANG['buttons'][7]."\" class='submit'>";
         echo "<input type='hidden' name='id' value='$ID'>";
         echo "<input type='submit' name='delete' value=\"".$LANG['buttons'][6]."\" class='submit'>";
         echo "</div></td></tr>";
      }
      echo "</table></div></form>";
   }


   /**
   * Prepare query to store depending of the type
   *
   * @param $type bookmark type
   * @param $query_tab parameters array
   * @param $itemtype device type
   *
   * @return clean query array
   **/
   function prepareQueryToStore($type, $query_tab, $itemtype=0) {

      switch ($type) {
         case BOOKMARK_SEARCH :
            $fields_toclean = array('start', 'add_search_count', 'delete_search_count',
                                    'add_search_count2', 'delete_search_count2', 'glpisearchcount',
                                    'glpisearchcount2');
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
   * @param $type bookmark type
   * @param $query_tab parameters array
   *
   * @return prepared query array
   **/
   function prepareQueryToUse($type, $query_tab) {

      switch ($type) {
         case BOOKMARK_SEARCH :
            $query_tab['reset'] = 'reset';
            break;
      }
      return $query_tab;
   }


   /**
   * load a bookmark
   *
   * @param $ID ID of the bookmark
   * @param $opener boolean load bookmark in opener window ? false -> current window
   *
   * @return nothing
   **/
   function load($ID, $opener=true) {

      if ($params= $this->getParameters($ID)) {
         $url  = GLPI_ROOT."/".rawurldecode($this->fields["path"]);
         $url .= "?".append_params($params);
         if ($opener) {
            echo "<script type='text/javascript' >\n";
            echo "window.opener.location.href='$url';";
            echo "</script>";
         } else {
            glpi_header($url);
         }
      }
   }


   /**
   * get bookmark parameters
   *
   * @param $ID ID of the bookmark
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
      if ($this->getFromDB($ID) && $this->fields['type']=BOOKMARK_SEARCH) {
         $dd = new Bookmark_User();
         // Is default view for this itemtype already exists ?
         $query = "SELECT `id`
                   FROM `glpi_bookmarks_users`
                   WHERE `users_id` = '".getLoginUserID()."'
                         AND `itemtype` = '".$this->fields['itemtype']."'";

         if ($result=$DB->query($query)) {
            if ($DB->numrows($result) > 0) {
               // already exists update it
               $updateID = $DB->result($result, 0, 0);
               $dd->update(array('id'           => $updateID,
                                 'bookmarks_id' => $ID));
            } else {
               $dd->add(array('bookmarks_id' => $ID,
                              'users_id'     => getLoginUserID(),
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
      if ($this->getFromDB($ID) && $this->fields['type']=BOOKMARK_SEARCH) {
         $dd = new Bookmark_User();
         // Is default view for this itemtype already exists ?
         $query = "SELECT `id`
                   FROM `glpi_bookmarks_users`
                   WHERE `users_id` = '".getLoginUserID()."'
                         AND `bookmarks_id` = '$ID'
                         AND `itemtype` = '".$this->fields['itemtype']."'";

         if ($result=$DB->query($query)) {
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
   * @param $target target to use for links
   * @param $is_private show private of public bookmarks ?
   *
   * @return nothing
   **/
   function showBookmarkList($target, $is_private=1) {
      global $DB, $LANG, $CFG_GLPI;

      if (!$is_private && !haveRight('bookmark_public','r')) {
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
                     AND `".$this->getTable()."`.`users_id`='".getLoginUserID()."') ";
      } else {
         $query .= "(`".$this->getTable()."`.`is_private`='0' ".
                     getEntitiesRestrictRequest("AND", $this->getTable(), "", "", true) . ")";
      }

      $query .= " ORDER BY `itemtype`, `name`";

      if ($result = $DB->query($query)) {
         $rand = mt_rand();
         echo "<form method='post' id='form_load_bookmark$rand' action=\"$target\">";
         echo "<div class='center' id='tabsbody' >";

         echo "<table class='tab_cadrehov'>";
         echo "<tr>";
         echo "<th class='center' colspan='3'>".$LANG['buttons'][52]." ".$LANG['bookmark'][1]."</th>";
         echo "<th width='20px'>&nbsp;</th>";
         echo "<th>".$LANG['bookmark'][6]."</th></tr>";

         if ( $DB->numrows($result)) {
            $current_type      = -1;
            $current_type_name = NOT_AVAILABLE;
            while ($this->fields = $DB->fetch_assoc($result)) {
               if ($current_type!=$this->fields['itemtype']) {
                  $current_type      = $this->fields['itemtype'];
                  $current_type_name = NOT_AVAILABLE;

                  if ($current_type=="States") {
                     $current_type_name = $LANG['state'][0];
                  } else if (class_exists($current_type)) {
                     $item = new $current_type();
                     $current_type_name = $item->getTypeName();
                  }
               }
               $canedit = $this->can($this->fields["id"],"w");

               echo "<tr class='tab_bg_1'>";
               echo "<td width='10px'>";
               if ($canedit) {
                  $sel = "";
                  if (isset($_GET["select"]) && $_GET["select"]=="all") {
                     $sel = "checked";
                  }
                  echo "<input type='checkbox' name='bookmark[".$this->fields["id"]."]'". $sel.">";
               } else {
                  echo "&nbsp;";
               }
               echo "</td>";
               echo "<td>$current_type_name</td>";
               echo "<td><a href=\"".GLPI_ROOT."/front/popup.php?popup=load_bookmark&amp;id=".
                          $this->fields["id"]."\">".$this->fields["name"]."</a></td>";
               if ($canedit) {
                  echo "<td><a href=\"".GLPI_ROOT."/front/popup.php?popup=edit_bookmark&amp;id=".
                             $this->fields["id"]."\">
                            <img src='".$CFG_GLPI["root_doc"]."/pics/edit.png' alt='".
                              $LANG['buttons'][14]."'></a></td>";
               } else {
                  echo "<td>&nbsp;</td>";
               }
               echo "<td class='center'>";
               if ($this->fields['type']==BOOKMARK_SEARCH) {
                  if (is_null($this->fields['IS_DEFAULT'])) {
                     echo "<a href=\"".GLPI_ROOT."/front/popup.php?popup=edit_bookmark&amp;
                            mark_default=1&amp;id=".$this->fields["id"]."\">".$LANG['choice'][0].
                          "</a>";
                  } else {
                     echo "<a href=\"".GLPI_ROOT."/front/popup.php?popup=edit_bookmark&amp;
                            mark_default=0&amp;id=".$this->fields["id"]."\">".$LANG['choice'][1].
                          "</a>";
                  }
               }
               echo "</td></tr>";
            }
            echo "</table></div>";

            openArrowMassive("form_load_bookmark$rand");
            closeArrowMassive('delete_several', $LANG['buttons'][6]);

         } else {
            echo "<tr class='tab_bg_1'><td colspan='5'>".$LANG['bookmark'][3]."</td></tr></table>";
         }
         echo '</form>';
      }
   }


   /**
    * Display bookmark buttons
    *
    * @param $type bookmark type to use
    * @param $itemtype device type of item where is the bookmark
    **/
   static function showSaveButton($type, $itemtype=0) {
      global $CFG_GLPI, $LANG;

      echo " <a href='#' onClick=\"var w = window.open('".$CFG_GLPI["root_doc"].
              "/front/popup.php?popup=edit_bookmark&amp;type=$type&amp;itemtype=$itemtype&amp;url=".
              rawurlencode($_SERVER["REQUEST_URI"]).
              "' ,'glpipopup', 'height=400, width=600, top=100, left=100, scrollbars=yes' );w.focus();\">";
      echo "<img src='".$CFG_GLPI["root_doc"]."/pics/bookmark_record.png'
             title=\"".$LANG['buttons'][51]." ".$LANG['bookmark'][1]."\"
             alt=\"".$LANG['buttons'][51]." ".$LANG['bookmark'][1]."\" class='calendrier'>";
      echo "</a>";
   }

}
?>
