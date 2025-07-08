<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

require_once(__DIR__ . '/_check_webserver_config.php');

use Glpi\Event;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;

$comment = new KnowbaseItem_Comment();
if (!isset($_POST['knowbaseitems_id'])) {
    Session::addMessageAfterRedirect(__s('Mandatory fields are not filled!'), false, ERROR);
    Html::back();
}
$kbitem = new KnowbaseItem();
$kbitem->getFromDB($_POST['knowbaseitems_id']);
if (!$kbitem->canComment()) {
    throw new AccessDeniedHttpException();
}

if (isset($_POST["add"])) {
    if (!isset($_POST['knowbaseitems_id'], $_POST['comment'])) {
        Session::addMessageAfterRedirect(__s('Mandatory fields are not filled!'), false, ERROR);
        Html::back();
    }

    if ($newid = $comment->add($_POST)) {
        Event::log(
            $_POST["knowbaseitems_id"],
            "knowbaseitem_comment",
            4,
            "tracking",
            sprintf(__('%s adds a comment on knowledge base'), $_SESSION["glpiname"])
        );
        Session::addMessageAfterRedirect(
            "<a href='#kbcomment$newid'>" . __s('Your comment has been added') . "</a>",
            false,
            INFO
        );
    }
    Html::back();
}

if (isset($_POST["edit"])) {
    if (!isset($_POST['knowbaseitems_id']) || !isset($_POST['id']) || !isset($_POST['comment'])) {
        Session::addMessageAfterRedirect(__s('Mandatory fields are not filled!'), false, ERROR);
        Html::back();
    }

    $comment->getFromDB($_POST['id']);
    $data = array_merge($comment->fields, $_POST);
    if ($comment->update($data)) {
        Event::log(
            $_POST["knowbaseitems_id"],
            "knowbaseitem_comment",
            4,
            "tracking",
            sprintf(__('%s edit a comment on knowledge base'), $_SESSION["glpiname"])
        );
        Session::addMessageAfterRedirect(
            "<a href='#kbcomment{$comment->getID()}'>" . __s('Your comment has been edited') . "</a>",
            false,
            INFO
        );
    }
    Html::back();
}

throw new BadRequestHttpException();
