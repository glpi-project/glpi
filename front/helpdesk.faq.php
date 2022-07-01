<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

include('../inc/includes.php');

// Redirect management
if (isset($_GET["redirect"])) {
    Toolbox::manageRedirect($_GET["redirect"]);
}

Session::checkFaqAccess();

if (Session::getLoginUserID()) {
    Html::helpHeader(__('FAQ'), 'faq');
} else {
    $_SESSION["glpilanguage"] = $_SESSION['glpilanguage'] ?? $CFG_GLPI['language'];
   // Anonymous FAQ
    Html::simpleHeader(__('FAQ'), [
        __('Authentication') => '/',
        __('FAQ')            => '/front/helpdesk.faq.php'
    ]);
}

if (isset($_GET["id"])) {
    $kb = new KnowbaseItem();
    if ($kb->getFromDB($_GET["id"])) {
        $kb->showFull();
    }
} else {
   // Manage forcetab : non standard system (file name <> class name)
    if (isset($_GET['forcetab'])) {
        Session::setActiveTab('Knowbase', $_GET['forcetab']);
        unset($_GET['forcetab']);
    }

    $kb = new Knowbase();
    $kb->display($_GET);
}

Html::helpFooter();
