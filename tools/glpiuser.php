#!/usr/bin/env php
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

(PHP_SAPI == 'cli') or die("Only available from command line");

// Ensure current directory when run from CLI
chdir(__DIR__);

include ('../inc/includes.php');

if (isset($_SERVER['argv'])) {
   for ($i=1; $i<$_SERVER['argc']; $i++) {
      $it    = explode("=", $_SERVER['argv'][$i], 2);
      $it[0] = preg_replace('/^--/', '', $it[0]);

      $_GET[$it[0]] = (isset($it[1]) ? $it[1] : true);
   }
}
if (isset($_GET['help']) || !isset($_GET['user'])) {
   echo "\nusage " . PHP_BINARY .
        " {$_SERVER['argv'][0]} [ --password=<newpassword> ] [ --active ] [ --db ] --user=<login>\n\n";
   echo "\t--password=secret  change password\n";
   echo "\t--enable           set active state\n";
   echo "\t--disable          unset active state\n";
   echo "\t--db               switch to password authent, for LDAP/IMAP users\n";
   echo "\t--user=name        the user to edit\n";
   die("\n");
}

function displayUser(User $user) {
   printf("\nLogin:    %s\n", $user->getField('name'));
   printf("Name:     %s\n", $user->getRawName());
   printf("Password: %s\n", $user->getField('password'));// ? 'set' : 'sot set');
   printf("Authent:  %s\n", Auth::getMethodName($user->getField('authtype'), $user->getField('auths_id')));
   printf("Active:   %s\n\n", $user->getField('is_active') ? 'yes' : 'no');
}

$user = new User();
if ($user->getFromDBbyName($_GET['user'])) {
   displayUser($user);

   $in = [];

   if ($_GET['enable']) {
      $in['is_active'] = 1;
   } else if ($_GET['disable']) {
      $in['is_active'] = 0;
   }

   if ($_GET['password']) {
      if (Config::validatePassword($input["password"])) {
         $_SESSION['glpiID'] = $user->getID(); // to allow change
         $in['password'] = $in['password2'] = $_GET['password'];
      } else {
         die("Invalid new password\n");
      }
   }

   if ($_GET['db']) {
      $in['authtype'] = 1;
      $in['auths_id'] = Auth::DB_GLPI;
   }

   if (count($in)) {
      $in['id'] = $user->getID();
      if ($user->update($in)) {
         unset($in['id'], $in['password2']);
         echo "Update:   succes (".implode(', ', array_keys($in)).")\n";
         displayUser($user);
      } else {
         echo "Update:   failed\n";
      }
   }
} else {
   die("User not found {$_GET['user']}");
}
