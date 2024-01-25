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

/**
 * @since 0.85
 */

use Glpi\Event;

/** @var array $CFG_GLPI */
global $CFG_GLPI;

if (!defined('GLPI_ROOT')) {
    include('../inc/includes.php');
}

$link = new Group_Ticket();
$item = new Ticket();

Session::checkLoginUser();

if (isset($_POST['delete'])) {
    $link->check($_POST['id'], DELETE);
    $link->delete($_POST);

    Event::log(
        $link->fields['tickets_id'],
        "ticket",
        4,
        "tracking",
        sprintf(__('%s deletes an actor'), $_SESSION["glpiname"])
    );

    if ($item->can($link->fields["tickets_id"], READ)) {
        Html::redirect(Ticket::getFormURLWithID($link->fields['tickets_id']));
    }
    Session::addMessageAfterRedirect(
        __('You have been redirected because you no longer have access to this item'),
        true,
        ERROR
    );

    Html::redirect($CFG_GLPI["root_doc"] . "/front/ticket.php");
}

Html::displayErrorAndDie('Lost');
