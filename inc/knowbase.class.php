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

// class Knowbase
/**
 * class Knowbase
 *
 * @since version 0.84
 *
**/
class Knowbase extends CommonGLPI {


   static function getTypeName($nb=0) {

      // No plural
      return __('Knowledge base');
   }


   function defineTabs($options=array()) {

      $ong = array();
      $this->addStandardTab(__CLASS__, $ong, $options);

      $ong['no_all_tab'] = true;
      return $ong;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if ($item->getType() == __CLASS__) {
         $tabs[1] = _x('button', 'Search');
         $tabs[2] = _x('button', 'Browse');
         if (KnowbaseItem::canCreate()) {
            $tabs[3] = _x('button', 'Manage');
         }

         return $tabs;
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType() == __CLASS__) {
         switch ($tabnum) {
            case 1 : // all
               $item->showSearchView();
               break;

            case 2 :
               $item->showBrowseView();
               break;

            case 3 :
               $item->showManageView();
               break;
         }
      }
      return true;
   }


   /**
    * Show the knowbase search view
   **/
   static function showSearchView() {
      // Search a solution
      if (!isset($_POST["contains"])
          && isset($_POST["itemtype"])
          && isset($_POST["items_id"])) {

         if ($item = getItemForItemtype($_POST["itemtype"])) {
            if ($item->getFromDB($_POST["items_id"])) {
               $_POST["contains"] = addslashes($item->getField('name'));
            }
         }
      }

      if (isset($_POST["contains"])) {
         $_SESSION['kbcontains'] = $_POST["contains"];
      } else if (isset($_SESSION['kbcontains'])) {
         $_POST['contains'] = $_SESSION["kbcontains"];
      }
      $ki = new KnowbaseItem();
      $ki->searchForm($_POST);

      if (!isset($_POST['contains']) || empty($_POST['contains'])) {
         echo "<div><table class='center-h' width='950px'><tr><td class='center top'>";
         KnowbaseItem::showRecentPopular("recent");
         echo "</td><td class='center top'>";
         KnowbaseItem::showRecentPopular("lastupdate");
         echo "</td><td class='center top'>";
         KnowbaseItem::showRecentPopular("popular");
         echo "</td></tr>";
         echo "</table></div>";
      } else {
         KnowbaseItem::showList($_POST, 'search');
      }
   }


   /**
    * Show the knowbase browse view
   **/
   static function showBrowseView() {
      if (isset($_POST["knowbaseitemcategories_id"])) {
         $_SESSION['kbknowbaseitemcategories_id'] = $_POST["knowbaseitemcategories_id"];
      } else if (isset($_SESSION['kbknowbaseitemcategories_id'])) {
         $_POST["knowbaseitemcategories_id"] = $_SESSION['kbknowbaseitemcategories_id'];
      }

      $ki = new KnowbaseItem();
      $ki->showBrowseForm($_POST);
      if (!isset($_POST["itemtype"])
          || !isset($_POST["items_id"])) {
         KnowbaseItemCategory::showFirstLevel($_POST);
      }
      KnowbaseItem::showList($_POST, 'browse');
   }


   /**
    * Show the knowbase Manage view
   **/
   static function showManageView() {

      if (isset($_POST["unpublished"])) {
         $_SESSION['kbunpublished'] = $_POST["unpublished"];
      } else if (isset($_SESSION['kbunpublished'])) {
         $_POST["unpublished"] = $_SESSION['kbunpublished'];
      }
      if (!isset($_POST["unpublished"])) {
         $_POST["unpublished"] = 'myunpublished';
      }
      $ki = new KnowbaseItem();
      $ki->showManageForm($_POST);
      KnowbaseItem::showList($_POST, $_POST["unpublished"]);
   }
}
?>