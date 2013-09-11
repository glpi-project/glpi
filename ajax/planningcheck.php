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
* @since version 0.83
*/

// Direct access to file
if (strpos($_SERVER['PHP_SELF'],"planningcheck.php")) {
   $AJAX_INCLUDE = 1;
   include ('../inc/includes.php');
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

Session::checkLoginUser();

if (isset($_POST['users_id']) && ($_POST['users_id'] > 0)) {

      echo " <a href='#' onClick=\"".Html::jsGetElementbyID('planningcheck').".dialog('open');\">";
      echo "<img src='".$CFG_GLPI["root_doc"]."/pics/reservation-3.png'
             title=\"".__s('Availability')."\" alt=\"".__s('Availability')."\"
             class='calendrier'>";
      echo "</a>";
      Ajax::createIframeModalWindow('planningcheck',
                                    $CFG_GLPI["root_doc"]."/front/planning.php?checkavailability=checkavailability".
                                          "&users_id=".$_POST['users_id'],
                                    array('title'         => __('Availability')));
}
?>