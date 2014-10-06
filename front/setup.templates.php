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

// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer
/** @file
* @brief
*/

include ('../inc/includes.php');

Session::checkCentralAccess();

if (isset($_GET["itemtype"])) {

   $link = $_GET["itemtype"]::getFormURL();
   $item = str_replace(".form.php","",$link);
   $item = str_replace("front/","",$item);

   // Get right sector
   $sector = 'assets';
   if (isset($_SESSION['glpimenu'])) {
      foreach ($_SESSION['glpimenu'] as $key => $val) {
         if (isset($val['types']) && in_array($_GET["itemtype"], $val['types'])) {
            $sector = $key;
            break;
         }
      }
   }
   
   Html::header(__('Manage templates...'), $_SERVER['PHP_SELF'], $sector, $item);

   CommonDBTM::listTemplates($_GET["itemtype"], $link, $_GET["add"]);

   Html::footer();
}
?>
