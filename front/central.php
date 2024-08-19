<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

/** @var array $CFG_GLPI */
global $CFG_GLPI;

if (isset($_GET["embed"]) && isset($_GET["dashboard"])) {
    $SECURITY_STRATEGY = 'no_check'; // Allow anonymous access for embed dashboards.
}

include('../inc/includes.php');

// embed (anonymous) dashboard
if (isset($_GET["embed"]) && isset($_GET["dashboard"])) {
    $grid      = new Glpi\Dashboard\Grid($_GET["dashboard"]);
    $dashboard = $grid->getDashboard();
    Html::zeroSecurityIframedHeader($grid->getDashboard()->getTitle(), 'central', 'central');
    $grid->embed($_REQUEST);
    Html::popFooter();
    exit;
}

// Change profile system
if (isset($_REQUEST['newprofile'])) {
    if (isset($_SESSION["glpiprofiles"][$_REQUEST['newprofile']])) {
        Session::changeProfile($_REQUEST['newprofile']);
        if (Session::getCurrentInterface() == "helpdesk") {
            if ($_SESSION['glpiactiveprofile']['create_ticket_on_login']) {
                Html::redirect($CFG_GLPI['root_doc'] . "/front/helpdesk.public.php?create_ticket=1");
            } else {
                Html::redirect($CFG_GLPI['root_doc'] . "/front/helpdesk.public.php");
            }
        }
        $_SESSION['_redirected_from_profile_selector'] = true;
        Html::redirect($_SERVER['HTTP_REFERER']);
    }
    Html::redirect(preg_replace("/entities_id.*/", "", $_SERVER['HTTP_REFERER']));
}

// Manage entity change
if (isset($_GET["active_entity"])) {
    if (!isset($_GET["is_recursive"])) {
        $_GET["is_recursive"] = 0;
    }
    if (Session::changeActiveEntities($_GET["active_entity"], $_GET["is_recursive"])) {
        if (
            ($_GET["active_entity"] == $_SESSION["glpiactive_entity"])
            && isset($_SERVER['HTTP_REFERER'])
        ) {
            Html::redirect(preg_replace("/(\?|&|" . urlencode('?') . "|" . urlencode('&') . ")?(entities_id|active_entity).*/", "", $_SERVER['HTTP_REFERER']));
        }
    }
}

Session::checkCentralAccess();

Html::header(Central::getTypeName(1), $_SERVER['PHP_SELF'], 'central', 'central');

// Redirect management
if (isset($_GET["redirect"])) {
    Toolbox::manageRedirect($_GET["redirect"]);
}

$central = new Central();
$central->display();

Html::footer();
