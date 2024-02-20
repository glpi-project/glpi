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
 * @var CommonDBTM $obj
 * @var CommonItilObject_Item $item_obj
 */

use Glpi\Event;
use Glpi\Http\Response;

Session::checkLoginUser();

if (!($obj instanceof CommonDBTM) || !($item_obj instanceof CommonItilObject_Item)) {
    Response::sendError(400, 'Bad request', Response::CONTENT_TYPE_TEXT_HTML);
}

$obj_fkey = $obj->getForeignKeyField();

if (isset($_POST["add"])) {
    if (isset($_POST['my_items']) && !empty($_POST['my_items'])) {
        list($_POST['itemtype'], $_POST['items_id']) = explode('_', $_POST['my_items']);
    }

    if (isset($_POST['add_items_id'])) {
        $_POST['items_id'] = $_POST['add_items_id'];
    }

    if (!isset($_POST['items_id']) || empty($_POST['items_id'])) {
        $message = sprintf(
            __('Mandatory fields are not filled. Please correct: %s'),
            _n('Associated element', 'Associated elements', 1)
        );
        Session::addMessageAfterRedirect($message, false, ERROR);
        Html::back();
    }

    $item_obj->check(-1, CREATE, $_POST);

    if ($item_obj->add($_POST)) {
        Event::log(
            $_POST[$obj_fkey],
            strtolower($obj->getType()),
            4,
            "tracking",
            //TRANS: %s is the user login
            sprintf(__('%s adds a link with an item'), $_SESSION["glpiname"])
        );
    }
    Html::back();
} else if (isset($_POST["delete"])) {
    $item_obj->deleteByCriteria([
        $obj_fkey  => $_POST[$obj_fkey],
        'items_id' => $_POST['items_id'],
        'itemtype' => $_POST['itemtype']
    ]);
    Html::back();
}

Html::displayErrorAndDie("lost");
