<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

$AJAX_INCLUDE = 1;
include ('../inc/includes.php');

// Send UTF8 Headers
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

$mailcollector = new MailCollector;

if (isset($_REQUEST['action'])) {
   switch ($_REQUEST['action']) {
      case "getFoldersList":
         // Load config if already exists
         // Necessary if password is not updated
         if (array_key_exists('id', $_REQUEST)) {
            $mailcollector->getFromDB($_REQUEST['id']);
         }

         // Update fields with input values
         $input = $_REQUEST;

         if (isset($input["passwd"])) {
            if (empty($input["passwd"])) {
               unset($input["passwd"]);
            } else {
               $input["passwd"] = Toolbox::encrypt($input["passwd"], GLPIKEY);
            }
         }

         if (isset($input['mail_server']) && !empty($input['mail_server'])) {
            $input["host"] = Toolbox::constructMailServerConfig($input);
         }

         if (!isset($input['errors'])) {
            $input['errors'] = 0;
         }

         $mailcollector->fields = array_merge($mailcollector->fields, $input);
         echo $mailcollector->displayFoldersList($_REQUEST['input_id']);
         break;
   }
}