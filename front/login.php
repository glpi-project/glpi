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

/**
 * @since 0.85
 */

include ('../inc/includes.php');


if (!isset($_SESSION["glpicookietest"]) || ($_SESSION["glpicookietest"] != 'testcookie')) {
   if (!is_writable(GLPI_SESSION_DIR)) {
      Html::redirect($CFG_GLPI['root_doc'] . "/index.php?error=2");
   } else {
      Html::redirect($CFG_GLPI['root_doc'] . "/index.php?error=1");
   }
}

$_POST = array_map('stripslashes', $_POST);

//Do login and checks
//$user_present = 1;
if (isset($_SESSION['namfield']) && isset($_POST[$_SESSION['namfield']])) {
   $login = $_POST[$_SESSION['namfield']];
} else {
   $login = '';
}
if (isset($_SESSION['pwdfield']) && isset($_POST[$_SESSION['pwdfield']])) {
   $password = Toolbox::unclean_cross_side_scripting_deep($_POST[$_SESSION['pwdfield']]);
} else {
   $password = '';
}
// Manage the selection of the auth source (local, LDAP id, MAIL id)
if (isset($_POST['auth'])) {
   $login_auth = $_POST['auth'];
} else {
   $login_auth = '';
}

$remember = isset($_SESSION['rmbfield']) && isset($_POST[$_SESSION['rmbfield']]) && $CFG_GLPI["login_remember_time"];

// Redirect management
$REDIRECT = "";
if (isset($_POST['redirect']) && (strlen($_POST['redirect']) > 0)) {
   $REDIRECT = "?redirect=" .rawurlencode($_POST['redirect']);

} else if (isset($_GET['redirect']) && strlen($_GET['redirect'])>0) {
   $REDIRECT = "?redirect=" .rawurlencode($_GET['redirect']);
}

$auth = new Auth();


// now we can continue with the process...
if ($auth->login($login, $password, (isset($_REQUEST["noAUTO"])?$_REQUEST["noAUTO"]:false), $remember, $login_auth)) {
   Auth::redirectIfAuthenticated();
} else {
   // we have done at least a good login? No, we exit.
   Html::nullHeader("Login", $CFG_GLPI["root_doc"] . '/index.php');
   echo '<div class="center b">' . $auth->getErr() . '<br><br>';
   // Logout whit noAUto to manage auto_login with errors
   echo '<a href="' . $CFG_GLPI["root_doc"] . '/front/logout.php?noAUTO=1'.
         str_replace("?", "&", $REDIRECT).'">' .__('Log in again') . '</a></div>';
   Html::nullFooter();
   exit();
}
