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

/**
 * Notepad class
 *
 * @since 0.85
**/
class Notepad extends CommonDBChild {

   // From CommonDBChild
   static public $itemtype        = 'itemtype';
   static public $items_id        = 'items_id';
   public $dohistory              = false;
   public $auto_message_on_action = false; // Link in message can't work'
   static public $logs_for_parent = true;


   static function getTypeName($nb = 0) {
      //TRANS: Always plural
      return _n('Note', 'Notes', $nb);
   }


   function getLogTypeID() {
      return [$this->fields['itemtype'], $this->fields['items_id']];
   }


   function canCreateItem() {

      if (isset($this->fields['itemtype'])
          && ($item = getItemForItemtype($this->fields['itemtype']))) {
         return Session::haveRight($item::$rightname, UPDATENOTE);
      }
      return false;
   }


   function canUpdateItem() {

      if (isset($this->fields['itemtype'])
          && ($item = getItemForItemtype($this->fields['itemtype']))) {
         return Session::haveRight($item::$rightname, UPDATENOTE);
      }
      return false;
   }


   function prepareInputForAdd($input) {

      $input['users_id']             = Session::getLoginUserID();
      $input['users_id_lastupdater'] = Session::getLoginUserID();
      $input['date']                 = $_SESSION['glpi_currenttime'];
      return $input;
   }


   function prepareInputForUpdate($input) {

      $input['users_id_lastupdater'] = Session::getLoginUserID();
      return $input;
   }

   /**
    * Duplicate all notepads from a item template to his clone
    *
    * @since 9.2
    *
    * @param string $itemtype      itemtype of the item
    * @param integer $oldid        ID of the item to clone
    * @param integer $newid        ID of the item cloned
    **/
   static function cloneItem ($itemtype, $oldid, $newid) {
      global $DB;

      foreach ($DB->request('glpi_notepads',
                            ['WHERE'  => "`items_id` = '$oldid'
                                          AND `itemtype` = '$itemtype'"]) as $data) {
         $cd               = new self();
         unset($data['id']);
         $data['items_id'] = $newid;
         $cd->add($data);
      }
   }

   /**
    * @see CommonGLPI::getTabNameForItem()
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (Session::haveRight($item::$rightname, READNOTE)) {
         $nb = 0;
         if ($_SESSION['glpishow_count_on_tabs']) {
            $nb = self::countForItem($item);
         }
         return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
      }
      return false;
   }


   /**
    * @param $item            CommonGLPI object
    * @param $tabnum          (default 1)
    * @param $withtemplate    (default 0)
   **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      static::showForItem($item, $withtemplate);
   }


   /**
    * @param $item    CommonDBTM object
    *
    * @return number
   **/
   static function countForItem(CommonDBTM $item) {

      return countElementsInTable('glpi_notepads',
                                  ['itemtype' => $item->getType(),
                                   'items_id' => $item->getID()]);
   }


   /**
    * @param $item   CommonDBTM object
   **/
   static function getAllForItem(CommonDBTM $item) {
      global $DB;

      $data = [];
      $iterator = $DB->request([
         'SELECT'    => [
            'glpi_notepads.*',
            'glpi_users.picture'
         ],
         'FROM'      => self::getTable(),
         'LEFT JOIN' => [
            'glpi_users'   => [
               'ON' => [
                  self::getTable()  => 'users_id_lastupdater',
                  'glpi_users'      => 'id'
               ]
            ]
         ],
         'WHERE'     => [
            'itemtype'  => $item->getType(),
            'items_id'  => $item->getID()
         ],
         'ORDERBY'   => 'date_mod DESC'
      ]);

      while ($note = $iterator->next()) {
         $data[] = $note;
      }
      return $data;
   }

   public function rawSearchOptions() {
      $so = self::rawSearchOptionsToAdd();
      foreach ($so as &$s) {
         if ($s['id'] == 200) {
               $s['name'] = __('Content');
         } else if ($s['id'] == 'notepad') {
            $s['id'] = 'common';
         }
      }
      return $so;
   }

   static public function rawSearchOptionsToAdd() {
      $tab = [];
      $name = _n('Note', 'Notes', Session::getPluralNumber());

      $tab[] = [
         'id'                 => 'notepad',
         'name'               => $name
      ];

      $tab[] = [
         'id'                 => '200',
         'table'              => 'glpi_notepads',
         'field'              => 'content',
         'name'               => $name,
         'datatype'           => 'text',
         'joinparams'         => [
            'jointype'           => 'itemtype_item'
         ],
         'forcegroupby'       => true,
         'splititems'         => true,
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '201',
         'table'              => 'glpi_notepads',
         'field'              => 'date',
         'name'               => __('Creation date'),
         'datatype'           => 'datetime',
         'joinparams'         => [
            'jointype'           => 'itemtype_item'
         ],
         'forcegroupby'       => true,
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '202',
         'table'              => 'glpi_users',
         'field'              => 'name',
         'name'               => __('Writer'),
         'datatype'           => 'dropdown',
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_notepads',
               'joinparams'         => [
                  'jointype'           => 'itemtype_item'
               ]
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '203',
         'table'              => 'glpi_notepads',
         'field'              => 'date_mod',
         'name'               => __('Last update'),
         'datatype'           => 'datetime',
         'joinparams'         => [
            'jointype'           => 'itemtype_item'
         ],
         'forcegroupby'       => true,
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '204',
         'table'              => 'glpi_users',
         'field'              => 'name',
         'linkfield'          => 'users_id_lastupdater',
         'name'               => __('Last updater'),
         'datatype'           => 'dropdown',
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_notepads',
               'joinparams'         => [
                  'jointype'           => 'itemtype_item'
               ]
            ]
         ]
      ];

      return $tab;
   }

   /**
    * Show notepads for an item
    *
    * @param $item                  CommonDBTM object
    * @param $withtemplate integer  template or basic item (default 0)
   **/
   static function showForItem(CommonDBTM $item, $withtemplate = 0) {
      global $CFG_GLPI;

      if (!Session::haveRight($item::$rightname, READNOTE)) {
         return false;
      }
      $notes   = static::getAllForItem($item);
      $rand    = mt_rand();
      $canedit = Session::haveRight($item::$rightname, UPDATENOTE);

      $showuserlink = 0;
      if (User::canView()) {
         $showuserlink = 1;
      }

      if ($canedit
          && !(!empty($withtemplate) && ($withtemplate == 2))) {
         echo "<div class='boxnote center'>";

         echo "<div class='boxnoteleft'></div>";
         echo "<form name='addnote_form$rand' id='addnote_form$rand' ";
         echo " method='post' action='".Toolbox::getItemTypeFormURL('Notepad')."'>";
         echo Html::hidden('itemtype', ['value' => $item->getType()]);
         echo Html::hidden('items_id', ['value' => $item->getID()]);

         echo "<div class='boxnotecontent'>";
         echo "<div class='floatleft'>";
         echo "<textarea name='content' rows=5 cols=100></textarea>";
         echo "</div>";
         echo "</div>"; // box notecontent

         echo "<div class='boxnoteright'><br>";
         echo Html::submit(_x('button', 'Add'), ['name' => 'add']);
         echo "</div>";

         Html::closeForm();
         echo "</div>"; // boxnote
      }

      if (count($notes)) {
         foreach ($notes as $note) {
            $id = 'note'.$note['id'].$rand;
            $classtoadd = '';
            if ($canedit) {
               $classtoadd = " pointer";
            }
            echo "<div class='boxnote' id='view$id'>";

            echo "<div class='boxnoteleft'>";
            echo "<img class='user_picture_verysmall' alt=\"".__s('Picture')."\" src='".
                User::getThumbnailURLForPicture($note['picture'])."'>";
            echo "</div>"; // boxnoteleft

            echo "<div class='boxnotecontent'>";

            echo "<div class='boxnotefloatright'>";
            $username = NOT_AVAILABLE;
            if ($note['users_id_lastupdater']) {
               $username = getUserName($note['users_id_lastupdater'], $showuserlink);
            }
            $update = sprintf(__('Last update by %1$s on %2$s'), $username,
                              Html::convDateTime($note['date_mod']));
            $username = NOT_AVAILABLE;
            if ($note['users_id']) {
               $username = getUserName($note['users_id'], $showuserlink);
            }
            $create = sprintf(__('Create by %1$s on %2$s'), $username,
                              Html::convDateTime($note['date']));
            printf(__('%1$s / %2$s'), $update, $create);
            echo "</div>"; // floatright

            echo "<div class='boxnotetext $classtoadd' ";
            if ($canedit) {
               echo "onclick=\"".Html::jsHide("view$id")." ".
                              Html::jsShow("edit$id")."\"";
            }
            echo ">";
            $content = nl2br($note['content']);
            if (empty($content)) {
               $content = NOT_AVAILABLE;
            }
            echo $content.'</div>'; // boxnotetext

            echo "</div>"; // boxnotecontent
            echo "<div class='boxnoteright'>";
            if ($canedit) {
               Html::showSimpleForm(Toolbox::getItemTypeFormURL('Notepad'),
                                    ['purge' => 'purge'],
                                    _x('button', 'Delete permanently'),
                                    ['id'   => $note['id']],
                                    'fa-times-circle',
                                    '',
                                     __('Confirm the final deletion?'));
            }
            echo "</div>"; // boxnoteright
            echo "</div>"; // boxnote

            if ($canedit) {
                echo "<div class='boxnote starthidden' id='edit$id'>";
                echo "<form name='update_form$id$rand' id='update_form$id$rand' ";
                echo " method='post' action='".Toolbox::getItemTypeFormURL('Notepad')."'>";

                echo "<div class='boxnoteleft'></div>";
                echo "<div class='boxnotecontent'>";
                echo Html::hidden('id', ['value' => $note['id']]);
                echo "<textarea name='content' rows=5 cols=100>".$note['content']."</textarea>";
                echo "</div>"; // boxnotecontent

                echo "<div class='boxnoteright'><br>";
                echo Html::submit(_x('button', 'Update'), ['name' => 'update']);
                echo "</div>"; // boxnoteright

                Html::closeForm();
                echo "</div>"; // boxnote
            }
         }
      }
      return true;
   }
}
