<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

// Direct access to file
if (strpos($_SERVER['PHP_SELF'], "ticketassigninformation.php")) {
   $AJAX_INCLUDE = 1;
   include ('../inc/includes.php');
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

Session::checkLoginUser();

$only_number = boolval($_REQUEST['only_number'] ?? false);

if (isset($_REQUEST['users_id_assign']) && ($_REQUEST['users_id_assign'] > 0)) {

   $ticket = new Ticket();

   $options2 = [
      'criteria' => [
         [
            'field'      => 5, // users_id assign
            'searchtype' => 'equals',
            'value'      => $_REQUEST['users_id_assign'],
            'link'       => 'AND',
         ],
         [
            'field'      => 12, // status
            'searchtype' => 'equals',
            'value'      => 'notold',
            'link'       => 'AND',
         ],
      ],
      'reset' => 'reset',
   ];

   $url = $ticket->getSearchURL()."?".Toolbox::append_params($options2, '&amp;');
   $nb  = $ticket->countActiveObjectsForTech($_REQUEST['users_id_assign']);

   if ($only_number) {
      if ($nb > 0) {
         echo "<a href='$url'>".$nb."</a>";
      }
   } else {
      echo "&nbsp;<a href='$url' title=\"".__s('Processing')."\">(";
      printf(__('%1$s: %2$s'), __('Processing'), $nb);
      echo ")</a>";
   }

} else if (isset($_REQUEST['groups_id_assign']) && ($_REQUEST['groups_id_assign'] > 0)) {
   $ticket = new Ticket();

   $options2 = [
      'criteria' => [
         [
            'field'      => 8, // groups_id assign
            'searchtype' => 'equals',
            'value'      => $_REQUEST['groups_id_assign'],
            'link'       => 'AND',
         ],
         [
            'field'      => 12, // status
            'searchtype' => 'equals',
            'value'      => 'notold',
            'link'       => 'AND',
         ],
      ],
      'reset' => 'reset',
   ];

   $url = $ticket->getSearchURL()."?".Toolbox::append_params($options2, '&amp;');
   $nb  = $ticket->countActiveObjectsForTechGroup($_REQUEST['groups_id_assign']);

   if ($only_number) {
      if ($nb > 0) {
         echo "<a href='$url'>".$nb."</a>";
      }
   } else {
      echo "&nbsp;<a href='$url' title=\"".__s('Processing')."\">(";
      printf(__('%1$s: %2$s'), __('Processing'),$nb);
      echo ")</a>";
   }

} else if (isset($_REQUEST['suppliers_id_assign']) && ($_REQUEST['suppliers_id_assign'] > 0)) {

   $ticket = new Ticket();

   $options2 = [
      'criteria' => [
         [
            'field'      => 6, // suppliers_id assign
            'searchtype' => 'equals',
            'value'      => $_REQUEST['suppliers_id_assign'],
            'link'       => 'AND',
         ],
         [
            'field'      => 12, // status
            'searchtype' => 'equals',
            'value'      => 'notold',
            'link'       => 'AND',
         ],
      ],
      'reset' => 'reset',
   ];

   $url = $ticket->getSearchURL()."?".Toolbox::append_params($options2, '&amp;');
   $nb  = $ticket->countActiveObjectsForSupplier($_REQUEST['suppliers_id_assign']);

   if ($only_number) {
      if ($nb > 0) {
         echo "<a href='$url'>".$nb."</a>";
      }
   } else {
      echo "&nbsp;<a href='$url' title=\"".__s('Processing')."\">(";
      printf(__('%1$s: %2$s'), __('Processing'), $nb);
      echo ")</a>";
   }
}

