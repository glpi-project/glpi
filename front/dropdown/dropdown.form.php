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
 * @var array $CFG_GLPI
 */

use Glpi\Dropdown\Dropdown;
use Glpi\Dropdown\DropdownDefinition;
use Glpi\Event;
use Glpi\Http\Response;

if (array_key_exists('id', $_REQUEST) && !Dropdown::isNewId($_REQUEST['id'])) {
    $dropdown = Dropdown::getById($_REQUEST['id']);
} else {
    $definition = new DropdownDefinition();
    $classname  = array_key_exists('class', $_GET) && $definition->getFromDBBySystemName((string)$_GET['class'])
        ? $definition->getCustomObjectClassName()
        : null;
    $dropdown      = $classname !== null && class_exists($classname)
        ? new $classname()
        : null;
}

if ($dropdown === null) {
    Response::sendError(400, 'Bad request', Response::CONTENT_TYPE_TEXT_HTML);
}

Session::checkRight($dropdown::$rightname, READ);

if (isset($_POST['add'])) {
    $dropdown->check(-1, CREATE, $_POST);

    if ($new_id = $dropdown->add($_POST)) {
        Event::log(
            $new_id,
            $dropdown::class,
            4,
            'inventory',
            sprintf(__('%1$s adds the item %2$s'), $_SESSION['glpiname'], $_POST['name'])
        );
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($dropdown->getLinkURL());
        }
    }
    Html::back();
} elseif (isset($_POST['update'])) {
    $dropdown->check($_POST['id'], UPDATE);
    if ($dropdown->update($_POST)) {
        Event::log(
            $_POST['id'],
            $dropdown::class,
            4,
            'inventory',
            sprintf(__('%s updates an item'), $_SESSION['glpiname'])
        );
    }
    Html::back();
} elseif (isset($_POST['delete'])) {
    $dropdown->check($_POST['id'], DELETE);
    if ($dropdown->delete($_POST)) {
        Event::log(
            $_POST['id'],
            $dropdown::class,
            4,
            'inventory',
            sprintf(__('%s deletes an item'), $_SESSION['glpiname'])
        );
    }
    $dropdown->redirectToList();
} elseif (isset($_POST['purge'])) {
    $dropdown->check($_POST['id'], PURGE);
    if ($dropdown->delete($_POST)) {
        Event::log(
            $_POST['id'],
            $dropdown::class,
            4,
            'inventory',
            sprintf(__('%s purges an item'), $_SESSION["glpiname"])
        );
    }
    $dropdown->redirectToList();
} elseif (isset($_POST['restore'])) {
    $dropdown->check($_POST['id'], DELETE);
    if ($dropdown->restore($_POST, 1)) {
        Event::log(
            $_POST['id'],
            $dropdown::class,
            4,
            'inventory',
            sprintf(__('%s restores an item'), $_SESSION['glpiname'])
        );
    }
    $dropdown->redirectToList();
} else {
    $id = (int)($_GET['id'] ?? null);
    $menus = ['config', 'commondropdown', $dropdown::class];
    $dropdown::displayFullPageForItem($id, $menus, [
        DropdownDefinition::getForeignKeyField() => $dropdown::getDefinition()->getID(),
        'withtemplate' => $_GET["withtemplate"] ?? '',
        'formoptions'  => "data-track-changes=true",
    ]);
}
