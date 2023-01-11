<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

use Glpi\Event;

include('../inc/includes.php');

if (empty($_GET["id"])) {
    $_GET["id"] = '';
}

Session::checkLoginUser();

// as _actors virtual field stores json, bypass automatic escaping
if (isset($_UPOST['_actors'])) {
    $_POST['_actors'] = json_decode($_UPOST['_actors'], true);
    $_REQUEST['_actors'] = $_POST['_actors'];
}

$change = new Change();
if (isset($_POST["add"])) {
    $change->check(-1, CREATE, $_POST);

    $newID = $change->add($_POST);
    Event::log(
        $newID,
        "change",
        4,
        "maintain",
        //TRANS: %1$s is the user login, %2$s is the name of the item
        sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"])
    );
    if ($_SESSION['glpibackcreated']) {
        Html::redirect($change->getLinkURL());
    } else {
        Html::back();
    }
} else if (isset($_POST["delete"])) {
    $change->check($_POST["id"], DELETE);

    $change->delete($_POST);
    Event::log(
        $_POST["id"],
        "change",
        4,
        "maintain",
        //TRANS: %s is the user login
        sprintf(__('%s deletes an item'), $_SESSION["glpiname"])
    );
    $change->redirectToList();
} else if (isset($_POST["restore"])) {
    $change->check($_POST["id"], DELETE);

    $change->restore($_POST);
    Event::log(
        $_POST["id"],
        "change",
        4,
        "maintain",
        //TRANS: %s is the user login
        sprintf(__('%s restores an item'), $_SESSION["glpiname"])
    );
    $change->redirectToList();
} else if (isset($_POST["purge"])) {
    $change->check($_POST["id"], PURGE);
    $change->delete($_POST, 1);

    Event::log(
        $_POST["id"],
        "change",
        4,
        "maintain",
        //TRANS: %s is the user login
        sprintf(__('%s purges an item'), $_SESSION["glpiname"])
    );
    $change->redirectToList();
} else if (isset($_POST["update"])) {
    $change->check($_POST["id"], UPDATE);

    $change->update($_POST);
    Event::log(
        $_POST["id"],
        "change",
        4,
        "maintain",
        //TRANS: %s is the user login
        sprintf(__('%s updates an item'), $_SESSION["glpiname"])
    );

    Html::back();
} else if (isset($_POST['addme_observer'])) {
    $change->check($_POST['changes_id'], READ);
    $input = array_merge(Toolbox::addslashes_deep($change->fields), [
        'id' => $_POST['changes_id'],
        '_itil_observer' => [
            '_type' => "user",
            'users_id' => Session::getLoginUserID(),
            'use_notification' => 1,
        ]
    ]);
    $change->update($input);
    Event::log(
        $_POST['changes_id'],
        "change",
        4,
        "maintain",
        //TRANS: %s is the user login
        sprintf(__('%s adds an actor'), $_SESSION["glpiname"])
    );
    Html::redirect($change->getFormURLWithID($_POST['changes_id']));
} else if (isset($_POST['addme_assign'])) {
    $change_user = new Change_User();

    $change->check($_POST['changes_id'], READ);
    $input = ['changes_id'       => $_POST['changes_id'],
        'users_id'         => Session::getLoginUserID(),
        'use_notification' => 1,
        'type'             => CommonITILActor::ASSIGN
    ];
    $change_user->add($input);
    Event::log(
        $_POST['changes_id'],
        "change",
        4,
        "maintain",
        //TRANS: %s is the user login
        sprintf(__('%s adds an actor'), $_SESSION["glpiname"])
    );
    Html::redirect(Change::getFormURLWithID($_POST['changes_id']));
} else if (isset($_POST['delete_document'])) {
    $change->getFromDB((int)$_POST['changes_id']);
    $doc = new Document();
    $doc->getFromDB(intval($_POST['documents_id']));
    if ($doc->can($doc->getID(), UPDATE)) {
        $document_item = new Document_Item();
        $found_document_items = $document_item->find([
            $change->getAssociatedDocumentsCriteria(),
            'documents_id' => $doc->getID()
        ]);
        foreach ($found_document_items as $item) {
            $document_item->delete(Toolbox::addslashes_deep($item), true);
        }
    }
    Html::back();
} else if (isset($_POST['addme_as_actor'])) {
    $id = (int) $_POST['id'];
    $change->check($id, READ);
    $input = array_merge(Toolbox::addslashes_deep($change->fields), [
        'id' => $id,
        '_itil_' . $_POST['actortype'] => [
            '_type' => "user",
            'users_id' => Session::getLoginUserID(),
            'use_notification' => 1,
        ]
    ]);
    $change->update($input);
    Event::log(
        $id,
        "change",
        4,
        "tracking",
        //TRANS: %s is the user login
        sprintf(__('%s adds an actor'), $_SESSION["glpiname"])
    );
    Html::redirect(Change::getFormURLWithID($id));
} else {
    if (isset($_GET['showglobalkanban']) && $_GET['showglobalkanban']) {
        Html::header(sprintf(__('%s Kanban'), Change::getTypeName(1)), $_SERVER['PHP_SELF'], "helpdesk", "change");
        $change::showKanban(0);
    } else {
        $menus = ["helpdesk", "change"];
        Change::displayFullPageForItem($_REQUEST['id'] ?? 0, $menus, $_REQUEST);
    }

    if (isset($_GET['id']) && ($_GET['id'] > 0)) {
        $url = KnowbaseItem::getFormURLWithParam($_GET) . '&_in_modal=1&item_itemtype=Ticket&item_items_id=' . $_GET['id'];
        if (strpos($url, '_to_kb=') !== false) {
            Ajax::createIframeModalWindow(
                'savetokb',
                $url,
                [
                    'title'         => __('Save and add to the knowledge base'),
                    'reloadonclose' => false,
                    'autoopen'      => true,
                ]
            );
        }
    }

    Html::footer();
}
