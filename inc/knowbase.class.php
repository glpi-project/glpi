<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2012 by the INDEPNET Development Team.

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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

// class Knowbase
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
         $ki = new KnowbaseItem();
         $tabs[1] = _x('button', 'Search');
         $tabs[2] = _x('button', 'Browse');
         if ($ki->canCreate()) {
            $tabs[3] = _x('button', 'Write');
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
               $item->showWriteView();
               break;
         }
      }
      return true;
   }

   /**
    * Show the knowbase search view
   **/
   static function showSearchView() {
      KnowbaseItem::searchForm($_GET);
   }

   /**
    * Show the knowbase browse view
   **/
   static function showBrowseView() {
      KnowbaseItemCategory::showBrowseForm($_GET);
   }

   /**
    * Show the knowbase write view
   **/
   static function showWriteView() {

   }
}
?>