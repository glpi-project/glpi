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
use Glpi\Exception\Http\BadRequestHttpException;

/**
 * @since 0.85
 */

$note = new Notepad();

if (isset($_POST['add'])) {
    $note->check(-1, CREATE, $_POST);

    $newID = $note->add($_POST);
    Event::log(
        $newID,
        "notepad",
        4,
        "tools",
        sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $newID)
    );
    Html::back();
} elseif (isset($_POST["purge"])) {
    $note->check($_POST["id"], PURGE);
    $note->delete($_POST, true);
    Event::log(
        $_POST["id"],
        "notepad",
        4,
        "tools",
        //TRANS: %s is the user login
        sprintf(__('%s purges an item'), $_SESSION["glpiname"])
    );
    Html::back();
} elseif (isset($_POST["update"])) {
    $note->check($_POST["id"], UPDATE);

    $note->update($_POST);
    Event::log(
        $_POST["id"],
        "notepad",
        4,
        "tools",
        //TRANS: %s is the user login
        sprintf(__('%s updates an item'), $_SESSION["glpiname"])
    );
    Html::back();
} elseif (isset($_POST["delete_document"])) {
    $doc = new Document();
    $doc->getFromDB(intval($_POST['documents_id']));
    if ($doc->can($doc->getID(), UPDATE)) {
        $document_item = new Document_Item();
        $document_item->deleteByCriteria([
            'itemtype'     => "Notepad",
            'items_id'     => (int) $_POST['id'],
            'documents_id' => $doc->getID(),
        ]);
    }
    Html::back();
}

if (isset($_GET['id']) && $note->getFromDB($_GET['id'])) {
    /** @var class-string<CommonDBTM> $parent_itemtype */
    $parent_itemtype = $note->fields['itemtype'];
    $redirect = $parent_itemtype::getFormURLWithID($note->fields['items_id'], true) . "&forcetab=Notepad$1";
    Html::redirect($redirect);
} else {
    throw new BadRequestHttpException();
}
