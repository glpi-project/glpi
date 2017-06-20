<?php
/*
 * @version $Id$
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
 * Notepad class
 *
 * @since version 0.85
**/
class Notepad extends CommonDBChild {

   // From CommonDBChild
   static public $itemtype        = 'itemtype';
   static public $items_id        = 'items_id';
   public $dohistory              = false;
   public $auto_message_on_action = false; // Link in message can't work'
   static public $logs_for_parent = true;


   static function getTypeName($nb=0) {
      //TRANS: Always plural
      return _n('Note', 'Notes', $nb);
   }


   function getLogTypeID() {
      return array($this->fields['itemtype'], $this->fields['items_id']);
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
    * @see CommonGLPI::getTabNameForItem()
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

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
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      static::showForItem($item, $withtemplate);
   }


   /**
    * @param $item    CommonDBTM object
    *
    * @return number
   **/
   static function countForItem(CommonDBTM $item) {

      return countElementsInTable('glpi_notepads',
                                 "`itemtype` = '".$item->getType()."'
                                    AND `items_id` = '".$item->getID()."'");
   }


   /**
    * @param $item   CommonDBTM object
   **/
   static function getAllForItem(CommonDBTM $item) {
      global $DB;

      $data = array();
      $query = "SELECT `glpi_notepads`.*, `glpi_users`.`picture`
                FROM `glpi_notepads`
                LEFT JOIN `glpi_users` ON (`glpi_notepads`.`users_id_lastupdater` = `glpi_users`.`id`)
                WHERE `glpi_notepads`.`itemtype` = '".$item->getType()."'
                     AND `glpi_notepads`.`items_id` = '".$item->getID()."'
                ORDER BY `date_mod` DESC";

      foreach($DB->request($query) as $note) {
         $data[] = $note;
      }
      return $data;
   }


   /**
    * @since version 0.85
   **/
   static function getSearchOptionsToAdd() {

      $tab                      = array();

      $tab['notepad']             = _n('Note', 'Notes', Session::getPluralNumber());

      $tab[200]['table']          = 'glpi_notepads';
      $tab[200]['field']          = 'content';
      $tab[200]['name']           = _n('Note', 'Notes', Session::getPluralNumber());
      $tab[200]['datatype']       = 'text';
      $tab[200]['joinparams']     = array('jointype' => 'itemtype_item');
      $tab[200]['forcegroupby']   = true;
      $tab[200]['splititems']     = true;
      $tab[200]['massiveaction']  = false;

      $tab[201]['table']          = 'glpi_notepads';
      $tab[201]['field']          = 'date';
      $tab[201]['name']           = __('Creation date');
      $tab[201]['datatype']       = 'datetime';
      $tab[201]['joinparams']     = array('jointype' => 'itemtype_item');
      $tab[201]['forcegroupby']   = true;
      $tab[201]['massiveaction']  = false;

      $tab[202]['table']          = 'glpi_users';
      $tab[202]['field']          = 'name';
      $tab[202]['name']           = __('Writer');
      $tab[202]['datatype']       = 'dropdown';
      $tab[202]['forcegroupby']   = true;
      $tab[202]['massiveaction']  = false;
      $tab[202]['joinparams']     = array('beforejoin'
                                       => array('table'      => 'glpi_notepads',
                                                'joinparams' => array('jointype'  => 'itemtype_item')));

      $tab[203]['table']          = 'glpi_notepads';
      $tab[203]['field']          = 'date_mod';
      $tab[203]['name']           = __('Last update');
      $tab[203]['datatype']       = 'datetime';
      $tab[203]['joinparams']     = array('jointype' => 'itemtype_item');
      $tab[203]['forcegroupby']   = true;
      $tab[203]['massiveaction']  = false;

      $tab[204]['table']          = 'glpi_users';
      $tab[204]['field']          = 'name';
      $tab[204]['linkfield']      = 'users_id_lastupdater';
      $tab[204]['name']           = __('Last updater');
      $tab[204]['datatype']       = 'dropdown';
      $tab[204]['forcegroupby']   = true;
      $tab[204]['massiveaction']  = false;
      $tab[204]['joinparams']     = array('beforejoin'
                                       => array('table'      => 'glpi_notepads',
                                                'joinparams' => array('jointype'  => 'itemtype_item')));

      return $tab;
   }


   /**
    * Show notepads for an item
    *
    * @param $item                  CommonDBTM object
    * @param $withtemplate integer  template or basic item (default '')
   **/
   static function showForItem(CommonDBTM $item, $withtemplate='') {
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
         echo Html::hidden('itemtype', array('value' => $item->getType()));
         echo Html::hidden('items_id', array('value' => $item->getID()));

         echo "<div class='boxnotecontent'>";
         echo "<div class='floatleft'>";
         echo "<textarea name='content' rows=5 cols=100></textarea>";
         echo "</div>";
         echo "</div>"; // box notecontent

         echo "<div class='boxnoteright'><br>";
         echo Html::submit(_x('button','Add'), array('name' => 'add'));
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
            if (empty($content)) $content = NOT_AVAILABLE;
            echo $content.'</div>'; // boxnotetext

            echo "</div>"; // boxnotecontent
            echo "<div class='boxnoteright'>";
            if ($canedit) {
               Html::showSimpleForm(Toolbox::getItemTypeFormURL('Notepad'),
                                    array('purge' => 'purge'),
                                    _x('button', 'Delete permanently'),
                                    array('id'   => $note['id']),
                                    $CFG_GLPI["root_doc"]."/pics/delete.png",
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
                echo Html::hidden('id', array('value' => $note['id']));
                echo "<textarea name='content' rows=5 cols=100>".$note['content']."</textarea>";
                echo "</div>"; // boxnotecontent

                echo "<div class='boxnoteright'><br>";
                echo Html::submit(_x('button','Update'), array('name' => 'update'));
                echo "</div>"; // boxnoteright

                Html::closeForm();
                echo "</div>"; // boxnote
             }
         }
      }

      return true;
   }
}
?>
