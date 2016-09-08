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

include ('../inc/includes.php');

if (strpos($_SERVER['HTTP_REFERER'], 'bookmark.php') === false) {
   Session::checkCentralAccess();
}

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

try {
   $ma = new MassiveAction($_POST, $_GET, 'process');
} catch (Exception $e) {
   Html::popHeader(__('Bulk modification error'), $_SERVER['PHP_SELF']);

   echo "<div class='center'><img src='".$CFG_GLPI["root_doc"]."/pics/warning.png' alt='".
      __s('Warning')."'><br><br>";
   echo "<span class='b'>".$e->getMessage()."</span><br>";
   Html::displayBackLink();
   echo "</div>";

   Html::popFooter();
   exit();

}
Html::popHeader(__('Bulk modification'), $_SERVER['PHP_SELF']);

$results   = $ma->process();

$nbok      = $results['ok'];
$nbko      = $results['ko'];
$nbnoright = $results['noright'];

if ($nbok == 0) {
   $message = __('Failed operation');
} else if ($nbnoright || $nbko) {
   $message = __('Operation performed partially successful');
} else {
   $message = __('Operation successful');
}
if ($nbnoright || $nbko) {
   //TRANS: %$1d and %$2d are numbers
   $message .= "<br>".sprintf(__('(%1$d authorizations problems, %2$d failures)'),
                              $nbnoright, $nbko);
}
Session::addMessageAfterRedirect($message);
if (isset($results['messages']) && is_array($results['messages']) && count($results['messages'])) {
   foreach ($results['messages'] as $message) {
      Session::addMessageAfterRedirect($message, false, ERROR);
   }
}
Html::redirect($results['redirect']);

Html::popFooter();

?>