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

use Glpi\Asset\Asset;
use Glpi\Asset\AssetDefinition;
use Glpi\Event;
use Glpi\Http\Response;

include('../../inc/includes.php');

if (array_key_exists('id', $_REQUEST)) {
    $asset = Asset::getById($_REQUEST['id']);
} else {
    $definition = new AssetDefinition();
    $classname  = array_key_exists('class', $_GET) && $definition->getFromDBBySystemName((string)$_GET['class'])
        ? $definition->getAssetClassName()
        : null;
    $asset      = $classname !== null && class_exists($classname)
        ? new $classname()
        : null;
}

if ($asset === null) {
    Response::sendError(400, 'Bad request', Response::CONTENT_TYPE_TEXT_HTML);
}

if (isset($_POST['add'])) {
    $asset->check(-1, CREATE, $_POST);

    if ($new_id = $asset->add($_POST)) {
        Event::log(
            $new_id,
            $asset::class,
            4,
            'inventory',
            sprintf(__('%1$s adds the item %2$s'), $_SESSION['glpiname'], $_POST['name'])
        );
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($asset->getLinkURL());
        }
    }
    Html::back();
} elseif (isset($_POST['update'])) {
    $asset->check($_POST['id'], UPDATE);
    if ($asset->update($_POST)) {
        Event::log(
            $_POST['id'],
            $asset::class,
            4,
            'inventory',
            sprintf(__('%s updates an item'), $_SESSION['glpiname'])
        );
    }
    Html::back();
} elseif (isset($_POST['delete'])) {
    $asset->check($_POST['id'], DELETE);
    if ($asset->delete($_POST)) {
        Event::log(
            $_POST['id'],
            $asset::class,
            4,
            'inventory',
            sprintf(__('%s deletes an item'), $_SESSION['glpiname'])
        );
    }
    $asset->redirectToList();
} elseif (isset($_POST['delete'])) {
    $asset->check($_POST['id'], DELETE);
    if ($asset->delete($_POST)) {
        Event::log(
            $_POST['id'],
            $asset::class,
            4,
            'inventory',
            sprintf(__('%s deletes an item'), $_SESSION['glpiname'])
        );
    }
    $asset->redirectToList();
} elseif (isset($_POST['restore'])) {
    $asset->check($_POST['id'], DELETE);
    if ($asset->restore($_POST, 1)) {
        Event::log(
            $_POST['id'],
            $asset::class,
            4,
            'inventory',
            sprintf(__('%s restores an item'), $_SESSION['glpiname'])
        );
    }
    $asset->redirectToList();
} else {
    $id = (int)($_GET['id'] ?? null);
    $menus = ['assets', $asset::class];
    $asset->displayFullPageForItem(
        $id,
        $menus,
        [AssetDefinition::getForeignKeyField() => $asset::getDefinition()->getID()]
    );
}
