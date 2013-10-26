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

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
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
         if ($_SESSION['glpishow_count_on_tabs']) {
            return self::createTabEntry(self::getTypeName(2), self::countForItem($item));
         }
         return self::getTypeName(2);
      }
      return false;
   }


   /**
    * @param $item            CommonGLPI object
    * @param $tabnum          (default 1)
    * @param $withtemplate    (default 0)
   **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      static::showForItem($item);
   }


   /**
    * @param$item    CommonDBTM object
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
      foreach($DB->request('glpi_notepads', array('itemtype' => $item->getType(),
                                                  'items_id' => $item->getID(),
                                                  'ORDER'    => 'date_mod DESC')) as $note) {
         $data[] = $note;
      }
      return $data;
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

      if ($canedit) {
         echo "<div class='boxnote center'>";
          echo "<div class='boxnoteleft'></div>";
           echo "<div class='boxnotecontent'>";
            echo "<form name='addnote_form$rand' id='addnote_form$rand' ";
            echo " method='post' action='".Toolbox::getItemTypeFormURL('Notepad')."'>";
            echo Html::hidden('itemtype', array('value' => $item->getType()));
            echo Html::hidden('items_id', array('value' => $item->getID()));

            echo "<div class='floatleft'>";
            echo "<textarea name='content' rows=5 cols=100></textarea>";
            echo "</div>";

            echo "<div class='boxnoteright'>";
            echo Html::submit(_x('button','Add'), array('name' => 'add'));
            echo "</div>";

            Html::closeForm();
           echo "</div>";
          echo "</div>";
         echo "</div>";
      }

      if (count($notes)) {
//          echo "<div>";
         foreach ($notes as $note) {
            $id = 'note'.$note['id'].$rand;
            echo "<div>";
             echo "<div class='boxnote' id='view$id'>";
              echo "<div class='boxnoteleft'>";
               if ($canedit) {
                  Html::showSimpleForm(Toolbox::getItemTypeFormURL('Notepad'),
                                       array('purge' => 'purge'), '',
                                       array('id'   => $note['id']),
                                       $CFG_GLPI["root_doc"]."/pics/delete.png");
               }
              echo "</div>";

              echo "<div class='boxnotecontent pointer'>";
               echo "<div class='boxnotetext'>";
                $content = nl2br($note['content']);
                if ($canedit) {
                   $content ="<a href='#$id' onclick=\"".Html::jsHide("view$id")." ".
                               Html::jsShow("edit$id")."\">".$content.'</a>';
                }
               echo $content.'</div>';

               echo "<div class='floatright'>";
                $username = NOT_AVAILABLE;
                if ($note['users_id_lastupdater']) {
                   $username = getUserName($note['users_id_lastupdater']);
                }
                $update = sprintf(__('Last update by %1$s on %2$s'), $username,
                                  Html::convDateTime($note['date_mod']));
                $username = NOT_AVAILABLE;
                if ($note['users_id']) {
                   $username = getUserName($note['users_id']);
                }
                $create = sprintf(__('Create by %1$s on %2$s'), $username,
                                  Html::convDateTime($note['date']));
                printf(__('%1$s / %2$s'), $update, $create);
               echo "</div>";

              echo "</div>";
             echo "</div>";

             if ($canedit) {
                echo "<div class='boxnote starthidden' id='edit$id'>";
                 echo "<div class='boxnoteleft'></div>";
                  echo "<div class='boxnotecontent'>";
                   echo "<form name='update_form$id$rand' id='update_form$id$rand' ";
                   echo " method='post' action='".Toolbox::getItemTypeFormURL('Notepad')."'>";
                   echo Html::hidden('id', array('value' => $note['id']));
                   echo "<textarea name='content' rows=5 cols=100>".$note['content']."</textarea>";

                   echo "<div class='boxnoteright'>";
                   echo Html::submit(_x('button','Update'), array('name' => 'update'));
                   echo "</div>";

                   Html::closeForm();
                  echo "</div>";
                 echo "</div>";
                echo "</div>";
             }
            echo "</div>";
         }
//          echo "</div>";
      }

      return true;
   }
}
?>
