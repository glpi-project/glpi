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

$AJAX_INCLUDE = 1;

include ('../inc/includes.php');
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

$rand = mt_rand();

Html::initEditorSystem("solution$rand");

if (isset($_POST['value']) && ($_POST['value'] > 0)) {
   $template = new SolutionTemplate();

   if ($template->getFromDB($_POST['value'])) {
      echo "<textarea id='solution$rand' name='solution' rows='12' cols='80'>";
      echo $template->getField('content');
      echo "</textarea>\n";
      echo "<script type='text/javascript'>".
               Html::jsSetDropdownValue($_POST["type_id"],
                                        $template->getField('solutiontypes_id')).
           "</script>";
   }

} else {
      echo "<textarea id='solution$rand' name='solution' rows='12' cols='80'></textarea>";
}
?>