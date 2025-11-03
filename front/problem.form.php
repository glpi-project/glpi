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

use function Safe\json_decode;

if (empty($_GET["id"])) {
    $_GET["id"] = '';
}

// as _actors virtual field stores json, bypass automatic escaping
if (isset($_POST['_actors'])) {
    $_POST['_actors'] = json_decode($_POST['_actors'], true);
    $_REQUEST['_actors'] = $_POST['_actors'];
}

$problem = new Problem();
if (isset($_POST["add"])) {
    $problem->check(-1, CREATE, $_POST);

    if ($newID = $problem->add($_POST)) {
        Event::log(
            $newID,
            "problem",
            4,
            "maintain",
            sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"])
        );
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($problem->getLinkURL());
        }
    }
    Html::back();
} elseif (isset($_POST["delete"])) {
    $problem->check($_POST["id"], DELETE);

    $problem->delete($_POST);
    Event::log(
        $_POST["id"],
        "problem",
        4,
        "maintain",
        //TRANS: %s is the user login
        sprintf(__('%s deletes an item'), $_SESSION["glpiname"])
    );
    $problem->redirectToList();
} elseif (isset($_POST["restore"])) {
    $problem->check($_POST["id"], DELETE);

    $problem->restore($_POST);
    Event::log(
        $_POST["id"],
        "problem",
        4,
        "maintain",
        //TRANS: %s is the user login
        sprintf(__('%s restores an item'), $_SESSION["glpiname"])
    );
    $problem->redirectToList();
} elseif (isset($_POST["purge"])) {
    $problem->check($_POST["id"], PURGE);

    $problem->delete($_POST, true);
    Event::log(
        $_POST["id"],
        "problem",
        4,
        "maintain",
        //TRANS: %s is the user login
        sprintf(__('%s purges an item'), $_SESSION["glpiname"])
    );
    $problem->redirectToList();
} elseif (isset($_POST["update"])) {
    $problem->check($_POST["id"], UPDATE);

    $problem->update($_POST);
    Event::log(
        $_POST["id"],
        "problem",
        4,
        "maintain",
        //TRANS: %s is the user login
        sprintf(__('%s updates an item'), $_SESSION["glpiname"])
    );

    // Copy solution to KB redirect to KB
    if (isset($_POST['_sol_to_kb']) && $_POST['_sol_to_kb']) {
        Html::redirect(KnowbaseItem::getFormURL() . "?id=new&item_itemtype=Problem&item_items_id=" . (int) $_POST["id"]);
    } else {
        Html::back();
    }
} elseif (isset($_POST['addme_observer'])) {
    $problem->check($_POST['problems_id'], READ);
    $input = array_merge($problem->fields, [
        'id' => $_POST['problems_id'],
        '_itil_observer' => [
            '_type' => "user",
            'users_id' => Session::getLoginUserID(),
            'use_notification' => 1,
        ],
    ]);
    $problem->update($input);
    Event::log(
        $_POST['problems_id'],
        "problem",
        4,
        "maintain",
        //TRANS: %s is the user login
        sprintf(__('%s adds an actor'), $_SESSION["glpiname"])
    );
    Html::redirect($problem->getFormURLWithID($_POST['problems_id']));
} elseif (isset($_POST['addme_assign'])) {
    $problem_user = new Problem_User();
    $problem->check($_POST['problems_id'], READ);
    $input = ['problems_id'       => $_POST['problems_id'],
        'users_id'         => Session::getLoginUserID(),
        'use_notification' => 1,
        'type'             => CommonITILActor::ASSIGN,
    ];
    $problem_user->add($input);
    Event::log(
        $_POST['problems_id'],
        "problem",
        4,
        "maintain",
        //TRANS: %s is the user login
        sprintf(__('%s adds an actor'), $_SESSION["glpiname"])
    );
    Html::redirect($problem->getFormURLWithID($_POST['problems_id']));
} elseif (isset($_POST['delete_document'])) {
    $problem->getFromDB((int) $_POST['problems_id']);
    $doc = new Document();
    $doc->getFromDB(intval($_POST['documents_id']));
    if ($doc->can($doc->getID(), UPDATE)) {
        $document_item = new Document_Item();
        $found_document_items = $document_item->find([
            $problem->getAssociatedDocumentsCriteria(),
            'documents_id' => $doc->getID(),
        ]);
        foreach ($found_document_items as $item) {
            $document_item->delete($item, true);
        }
    }
    Html::back();
} elseif (isset($_POST['addme_as_actor'])) {
    $id = (int) $_POST['id'];
    $problem->check($id, READ);
    $input = array_merge($problem->fields, [
        'id' => $id,
        '_itil_' . $_POST['actortype'] => [
            '_type' => "user",
            'users_id' => Session::getLoginUserID(),
            'use_notification' => 1,
        ],
    ]);
    $problem->update($input);
    Event::log(
        $id,
        "problem",
        4,
        "tracking",
        //TRANS: %s is the user login
        sprintf(__('%s adds an actor'), $_SESSION["glpiname"])
    );
    Html::redirect(Problem::getFormURLWithID($id));
} else {
    // Add a problem from item : format data
    if (
        isset($_REQUEST['_add_fromitem'], $_REQUEST['itemtype'], $_REQUEST['items_id'])
    ) {
        $_REQUEST['items_id'] = [$_REQUEST['itemtype'] => [$_REQUEST['items_id']]];
    }

    if (isset($_GET['showglobalkanban']) && $_GET['showglobalkanban']) {
        Html::header(sprintf(__('%s Kanban'), Problem::getTypeName(1)), '', "helpdesk", "problem");
        $problem::showKanban(0);
        Html::footer();
    } else {
        $options = $_REQUEST;
        $id = (int) $_GET['id'];

        $menus = ["helpdesk", "problem"];
        Problem::displayFullPageForItem($id, $menus, $options);

        if ($id > 0) {
            $url = KnowbaseItem::getFormURLWithParam($_GET) . '&_in_modal=1&item_itemtype=Problem&item_items_id=' . $id;
            if (str_contains($url, '_to_kb=')) {
                echo Ajax::createIframeModalWindow(
                    'savetokb',
                    $url,
                    [
                        'title'         => __('Save and add to the knowledge base'),
                        'reloadonclose' => false,
                        'autoopen'      => true,
                        'display'       => false,
                    ]
                );
            }
        }
    }

    Html::footer();
}
