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

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Group class
**/
class Group extends CommonTreeDropdown {


   static function getTypeName($nb=0) {
      global $LANG;

      if ($nb>1) {
         return $LANG['Menu'][36];
      }
      return $LANG['common'][35];
   }


   function canCreate() {
      return Session::haveRight('group', 'w');
   }


   function canView() {
      return Session::haveRight('group', 'r');
   }


   function post_getEmpty () {

      $this->fields['is_requester'] = 1;
      $this->fields['is_assign']    = 1;
      $this->fields['is_notify']    = 1;
      $this->fields['is_itemgroup'] = 1;
      $this->fields['is_usergroup'] = 1;
   }


   function cleanDBonPurge() {
      global $DB;

      $gu = new Group_User();
      $gu->cleanDBonItemDelete($this->getType(), $this->fields['id']);

      $gt = new Group_Ticket();
      $gt->cleanDBonItemDelete($this->getType(), $this->fields['id']);

      $gp = new Group_Problem();
      $gp->cleanDBonItemDelete($this->getType(), $this->fields['id']);

      $gki = new Group_KnowbaseItem();
      $gki->cleanDBonItemDelete($this->getType(), $this->fields['id']);

      $gr = new Group_Reminder();
      $gr->cleanDBonItemDelete($this->getType(), $this->fields['id']);


      // Ticket rules use various _groups_id_*
      Rule::cleanForItemAction($this, '_groups_id%');
      Rule::cleanForItemCriteria($this, '_groups_id%');
      // GROUPS for RuleMailcollector
      Rule::cleanForItemCriteria($this, 'GROUPS');

      // Set no group to consumables
      $query = "UPDATE `glpi_consumables`
                SET `items_id` = '0'
                WHERE `items_id` = '".$this->fields['id']."'
                      AND `itemtype` = 'Group'";
      $DB->query($query);
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG;

      if (!$withtemplate && Session::haveRight("group","r")) {
         switch ($item->getType()) {
            case 'Group' :
               $ong = array();

               $nb = 0;
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable($this->getTable(), "`groups_id` = '".$item->getID()."'");
               }
               $ong[4] = self::createTabEntry($this->getTypeName(2), $nb);

               if ($item->getField('is_itemgroup')) {
                  $ong[1] = $LANG['common'][111];
               }
               if ($item->getField('is_assign')) {
                  $ong[2] = $LANG['common'][112];
               }
               if ($item->getField('is_usergroup')
                   && Session::haveRight("group", "w")
                   && Session::haveRight("user_authtype", "w")
                   && AuthLdap::useAuthLdap()) {
                  $ong[3] = $LANG['setup'][3];
               }
               return $ong;
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      global $LANG;

      switch ($item->getType()) {
         case 'Group' :
            switch ($tabnum) {
               case 1 :
                  $item->showItems(false);
                  return true;

               case 2 :
                  $item->showItems(true);
                  return true;

               case 3 :
                  $item->showLDAPForm($item->getID());
                  return true;

               case 4 :
                  $item->showChildren();
                  return true;
            }
            break;
      }
      return false;
   }


   function defineTabs($options=array()) {
      global $LANG;

      $ong = array();

      $this->addStandardTab('Group', $ong, $options);
      if ($this->fields['is_usergroup']) {
         $this->addStandardTab('User', $ong, $options);
      }
      if ($this->fields['is_notify']) {
         $this->addStandardTab('NotificationTarget', $ong, $options);
      }
      if ($this->fields['is_requester']) {
         $this->addStandardTab('Ticket', $ong, $options);
      }

      return $ong;
   }


   /**
   * Print the group form
   *
   * @param $ID integer ID of the item
   * @param $options array
   *     - target filename : where to go when done.
   *     - withtemplate boolean : template or basic item
   *
   * @return Nothing (display)
   **/
   function showForm($ID, $options=array()) {
      global $LANG;

      if ($ID > 0) {
         $this->check($ID, 'r');
      } else {
         // Create item
         $this->check(-1, 'w');
      }

      $this->showTabs($options);
      $options['colspan']=4;
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='2'>".$LANG['common'][16]."&nbsp;:&nbsp;</td>";
      echo "<td colspan='2'>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td rowspan='8' class='middle'>".$LANG['common'][25]."&nbsp;:&nbsp;</td>";
      echo "<td class='middle' rowspan='8'>";
      echo "<textarea cols='45' rows='8' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='2'>".$LANG['setup'][75]."</td><td colspan='2'>";
      Dropdown::show('Group',
                     array('value'  => $this->fields['groups_id'],
                           'name'   => 'groups_id',
                           'entity' => $this->fields['entities_id'],
                           'used'   => ($ID>0 ? getSonsOf($this->getTable(), $ID) : array())));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='b' colspan='4'>".$LANG['group'][0]."</td>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>&nbsp;</td>";
      echo "<td>".$LANG['job'][4]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      dropdown::showYesNo('is_requester', $this->fields['is_requester']);
      echo "</td>";
      echo "<td>".$LANG['job'][5]."&nbsp;:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
      dropdown::showYesNo('is_assign', $this->fields['is_assign']);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='2' class='b'>".$LANG['group'][1]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      dropdown::showYesNo('is_notify', $this->fields['is_notify']);
      echo "</td><td></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='b' colspan='4'>".$LANG['group'][2]."</td>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>&nbsp;</td>";
      echo "<td>".$LANG['common'][96]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      dropdown::showYesNo('is_itemgroup', $this->fields['is_itemgroup']);
      echo "</td>";
      echo "<td>".$LANG['Menu'][14]."&nbsp;:&nbsp;&nbsp;";
      dropdown::showYesNo('is_usergroup', $this->fields['is_usergroup']);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='4' class='center'>";
      if (!$ID) {
         $template = "newtemplate";
         echo $LANG['computers'][14]."&nbsp;:&nbsp;";
         echo HTML::convDateTime($_SESSION["glpi_currenttime"]);

      } else {
         echo $LANG['common'][26]."&nbsp;:&nbsp;";
         echo HTML::convDateTime($this->fields["date_mod"]);
      }
      echo "</td></tr>";

      $this->showFormButtons($options);
      $this->addDivForTabs();

      return true;
   }


   /**
    * Print a good title for group pages
    *
    *@return nothing (display)
    **/
   function title() {
      global $LANG, $CFG_GLPI;

      $buttons = array();
      if (Session::haveRight("group", "w")
          && Session::haveRight("user_authtype", "w")
          && AuthLdap::useAuthLdap()) {

         $buttons["ldap.group.php"] = $LANG['setup'][3];
         $title = "";

      } else {
         $title = $LANG['Menu'][36];
      }

      Html::displayTitle($CFG_GLPI["root_doc"] . "/pics/groupes.png", $LANG['Menu'][36], $title,
                         $buttons);
   }


   function getSearchOptions() {
      global $LANG;

      $tab = parent::getSearchOptions();


      if (AuthLdap::useAuthLdap()) {

         $tab[3]['table']     = $this->getTable();
         $tab[3]['field']     = 'ldap_field';
         $tab[3]['name']      = $LANG['setup'][260];
         $tab[3]['datatype']  = 'string';

         $tab[4]['table']     = $this->getTable();
         $tab[4]['field']     = 'ldap_value';
         $tab[4]['name']      = $LANG['setup'][601];
         $tab[4]['datatype']  = 'string';

         $tab[5]['table']     = $this->getTable();
         $tab[5]['field']     = 'ldap_group_dn';
         $tab[5]['name']      = $LANG['setup'][261];
         $tab[5]['datatype']  = 'string';
      }

      $tab[11]['table']         = $this->getTable();
      $tab[11]['field']         = 'is_requester';
      $tab[11]['name']          = $LANG['job'][4];
      $tab[11]['datatype']      = 'bool';

      $tab[12]['table']         = $this->getTable();
      $tab[12]['field']         = 'is_assign';
      $tab[12]['name']          = $LANG['job'][5];
      $tab[12]['datatype']      = 'bool';

      $tab[13]['table']         = $this->getTable();
      $tab[13]['field']         = 'is_notify';
      $tab[13]['name']          = $LANG['group'][1];
      $tab[13]['datatype']      = 'bool';

      $tab[17]['table']         = $this->getTable();
      $tab[17]['field']         = 'is_itemgroup';
      $tab[17]['name']          = $LANG['search'][2]." ".$LANG['common'][96];
      $tab[17]['datatype']      = 'bool';

      $tab[15]['table']         = $this->getTable();
      $tab[15]['field']         = 'is_usergroup';
      $tab[15]['name']          = $LANG['search'][2]." ".$LANG['Menu'][14];
      $tab[15]['datatype']      = 'bool';

      $tab[70]['table'] = 'glpi_users';
      $tab[70]['field'] = 'name';
      $tab[70]['name']  = $LANG['common'][64];
      $tab[70]['itemlink_type'] = 'User';
      $tab[70]['forcegroupby']  = true;
      $tab[70]['massiveaction'] = false;
      $tab[70]['joinparams']    = array('beforejoin'
                                        => array('table'      => 'glpi_groups_users',
                                                 'joinparams' => array('jointype' => 'child',
                                                 'condition' => "AND NEWTABLE.`is_manager` = 1")));

      $tab[71]['table'] = 'glpi_users';
      $tab[71]['field'] = 'name';
      $tab[71]['name']  = $LANG['common'][123];
      $tab[71]['itemlink_type'] = 'User';
      $tab[71]['forcegroupby']  = true;
      $tab[71]['massiveaction'] = false;
      $tab[71]['joinparams']    = array('beforejoin'
                                        => array('table'      => 'glpi_groups_users',
                                                 'joinparams' => array('jointype' => 'child',
                                                 'condition' => "AND NEWTABLE.`is_userdelegate` = 1")));

      return $tab;
   }


   function showLDAPForm ($ID) {
      global $LANG;

      if ($ID > 0) {
         $this->check($ID, 'r');
      } else {
         // Create item
         $this->check(-1, 'w');
      }

      echo "<form name='groupldap_form' id='groupldap_form' method='post' action='".
             $this->getFormURL()."'>";
      echo "<div class='spaced'><table class='tab_cadre_fixe'>";

      if (Session::haveRight("group", "w")
                   && Session::haveRight("user_authtype", "w")
                   && AuthLdap::useAuthLdap()) {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='2' class='center'>".$LANG['setup'][256]."&nbsp;:&nbsp;</td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>".$LANG['setup'][260]."&nbsp;:&nbsp;</td>";
         echo "<td>";
         Html::autocompletionTextField($this, "ldap_field");
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>".$LANG['setup'][601]."&nbsp;:&nbsp;</td>";
         echo "<td>";
         Html::autocompletionTextField($this, "ldap_value");
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='2' class='center'>".$LANG['setup'][257]."&nbsp;:&nbsp;</td>";
         echo "</tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>".$LANG['setup'][261]."&nbsp;:&nbsp;</td>";
         echo "<td>";
         Html::autocompletionTextField($this, "ldap_group_dn");
         echo "</td></tr>";
      }

      $options = array('colspan' => 1,
                       'candel'  => false);
      $this->showFormButtons($options);
   }


   /**
    * get List of Computer in a group
    *
    * @since version 0.83
    *
    * @param $types  Array of types
    * @param $field  String field name
    * @param $tree   Boolean include child groups
    * @param $user   Boolean include members (users)
    * @param $start  Integer (first row to retrieve)
    * @param $res    Array result filled on ouput
    *
    * @return integer total of items
    */
   function getDataItems($types, $field, $tree, $user, $start, &$res) {
      global $DB, $CFG_GLPI, $LANG;

      // include item of child groups ?
      if ($tree) {
         $grprestrict = "IN (".implode(',', getSonsOf('glpi_groups', $this->getID())).")";
      } else {
         $grprestrict = "='".$this->getID()."'";
      }
      // include items of members
      if ($user) {
         $ufield = str_replace('groups', 'users', $field);
         $grprestrict = "(`$field` $grprestrict
                          OR (`$field`=0
                              AND `$ufield` IN
                                  (SELECT `users_id`
                                   FROM `glpi_groups_users`
                                   WHERE `groups_id` $grprestrict)))";
      } else {
         $grprestrict = "`$field` $grprestrict";
      }
      // Count the total of item
      $nb  = array();
      $tot = 0;
      foreach ($types as $itemtype) {
         $nb[$itemtype] = 0;
         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }
         if (!$item->canView()) {
            continue;
         }
         if (!$item->isField($field)) {
            continue;
         }
         $restrict[$itemtype] = $grprestrict;

         if ($item->isEntityAssign()) {
            $restrict[$itemtype] .= getEntitiesRestrictRequest(" AND ", $item->getTable(), '', '',
                                                               $item->maybeRecursive());
         }
         if ($item->maybeTemplate()) {
            $restrict[$itemtype] .= " AND NOT `is_template`";
         }
         if ($item->maybeDeleted()) {
            $restrict[$itemtype] .= " AND NOT `is_deleted`";
         }

         $tot += $nb[$itemtype] = countElementsInTable($item->getTable(), $restrict[$itemtype]);
      }
      $max = $_SESSION['glpilist_limit'];
      if ($start >= $tot) {
         $start = 0;
      }
      $res = array();
      foreach ($types as $itemtype) {
         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }
         if ($start >= $nb[$itemtype]) {
            // No need to read
            $start -= $nb[$itemtype];
         } else {
            $query = "SELECT `id`
                      FROM `".$item->getTable()."`
                      WHERE ".$restrict[$itemtype]."
                      ORDER BY `name`
                      LIMIT $start,$max";
            foreach ($DB->request($query) as $data) {
               $res[] = array('itemtype' => $itemtype,
                              'items_id' => $data['id']);
               $max--;
            }
            // For next type
            $start = 0;
         }
         if (!$max) {
            break;
         }
      }
      return $tot;
   }


   /**
    * Show items for the group
    *
    * @param $tech boolean, false search groups_id, true, search groups_id_tech
    */
   function showItems($tech) {
      global $DB, $CFG_GLPI, $LANG;

      $rand = mt_rand();

      $ID = $this->fields['id'];
      if ($tech) {
         $types = $CFG_GLPI['linkgroup_tech_types'];
         $field = 'groups_id_tech';
         $title = $LANG['common'][112];
      } else {
         $types = $CFG_GLPI['linkgroup_types'];
         $field = 'groups_id';
         $title = $LANG['common'][111];
      }

      $tree = Session::getSavedOption(__CLASS__, 'tree', 0);
      $user = Session::getSavedOption(__CLASS__, 'user', 0);
      $type = Session::getSavedOption(__CLASS__, 'onlytype', '');
      if (!in_array($type, $types)) {
         $type = '';
      }
      echo "<div class='spaced'>";
      // Mini Search engine
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'><th colspan='3'>$title</tr>";
      echo "<tr class='tab_bg_1'><td class='center'>";
      echo $LANG['common'][17]."&nbsp;:&nbsp;";
      Dropdown::showItemType($types,
                             array('value'      => $type,
                                   'name'       => 'onlytype',
                                   'on_change'  => 'reloadTab("start=0&onlytype="+this.value)',
                                   'checkright' => true));
      if ($this->haveChildren()) {
         echo "</td><td class='center'>".$LANG['group'][3]."&nbsp;:&nbsp;";
         Dropdown::showYesNo('tree', $tree, -1,
                             array('on_change' => 'reloadTab("start=0&tree="+this.value)'));
      } else {
         $tree = 0;
      }
      if ($this->getField('is_usergroup')) {
         echo "</td><td class='center'>".User::getTypeName(2)."&nbsp;:&nbsp;";
         Dropdown::showYesNo('user', $user, -1,
                             array('on_change' => 'reloadTab("start=0&user="+this.value)'));
      } else {
         $user = 0;
      }
      echo "</td></tr></table>";

      $datas  = array();
      if ($type) {
         $types = array($type);
      }
      $start  = (isset($_REQUEST['start']) ? $_REQUEST['start'] : 0);
      $nb     = $this->getDataItems($types, $field, $tree, $user, $start, $datas);
      $nbcan  = 0;

      if ($nb) {
         Html::printAjaxPager('', $start, $nb);
         echo "<form name='group_form' id='group_form_$field$rand' method='post' action='".$this->getFormURL()."'>";

         echo "<table class='tab_cadre_fixe'><tr><th width='10'>&nbsp</th>";
         echo "<th>".$LANG['common'][17]."</th>";
         echo "<th>".$LANG['common'][16]."</th><th>".$LANG['entity'][0]."</th>";
         if ($tree || $user) {
            echo "<th>".self::getTypeName(1)." / ".User::getTypeName(1)."</th>";
         }
         echo "</tr>";

         $tuser = new User();
         $group = new Group();
         foreach ($datas as $data) {
            if (!($item = getItemForItemtype($data['itemtype']))) {
               continue;
            }
            echo "<tr class='tab_bg_1'><td>";
            if ($item->can($data['items_id'], 'w')) {
               echo "<input type='checkbox' name='item[".$data['itemtype']."][".$data['items_id']."]' value='1'>";
               $nbcan++;
            }
            echo "</td><td>".$item->getTypeName(1);
            echo "</td><td>".$item->getLink(1);
            echo "</td><td>".Dropdown::getDropdownName("glpi_entities", $item->getEntityID());
            if ($tree || $user) {
               echo "</td><td>";
               if ($grp = $item->getField($field)) {
                  if ($group->getFromDB($grp)) {
                     echo $group->getLink(true);
                  }

               } else if ($usr = $item->getField(str_replace('groups', 'users', $field))) {
                  if ($tuser->getFromDB($usr)) {
                     echo $tuser->getLink(true);
                  }
               }
            }
            echo "</td></tr>";
         }
         echo "</table>";
      } else {
         echo "<p class='center b'>".$LANG['search'][15]."</p>";
      }

      if ($nbcan) {
         Html::openArrowMassives("group_form_$field$rand", true);
         echo $LANG['common'][35]."&nbsp;:&nbsp;";
         echo "<input type='hidden' name='field' value='$field'>";
         Dropdown::show('Group', array('entity'    => $this->fields["entities_id"],
                                       'used'      => array($this->fields["id"]),
                                       'condition' => ($tech ? '`is_assign`' : '`is_itemgroup`')));
         echo "&nbsp;";
         Html::closeArrowMassives(array('changegroup' => $LANG['buttons'][20]));
      }
      if ($nb) {
         Html::closeForm();
      }
      echo "</div>";
   }

}
?>