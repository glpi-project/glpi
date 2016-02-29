<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

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

include ('../inc/includes.php');

Session::checkLoginUser();

// Manage tabs
if (isset($_GET['tab']) && isset($_GET['itemtype'])) {
   if ($item = getItemForItemtype($_GET['itemtype'])) {
   
      if (isset($_GET['id']) && !$item->isNewID($_GET['id'])) {
         $item->getFromDB($_GET['id']);
      }

      $tabs         = $item->defineAllTabs();
      if (isset($tabs['no_all_tab'])) {
         unset($tabs['no_all_tab']);
      }
      // Add all tab
      $tabs[-1]     = 'All';
      $selected_tab = '';
      $current      = 0;
      foreach ($tabs as $key => $val) {
         if ($current == $_GET['tab']) {
            $selected_tab = $key;
         }
         $current++;
      }
      if (!empty($selected_tab)) {
         Session::setActiveTab($_GET['itemtype'], $selected_tab);
      }

   }
}
?>
