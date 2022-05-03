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

use Glpi\Event;

include('../inc/includes.php');

Session::checkLoginUser();
$track = new Ticket();

if (!isset($_GET['id'])) {
    $_GET['id'] = "";
}

$date_fields = [
    'date',
    'due_date',
    'time_to_own'
];

foreach ($date_fields as $date_field) {
   //handle not clean dates...
    if (
        isset($_POST["_$date_field"])
        && isset($_POST[$date_field])
        && trim($_POST[$date_field]) == ''
        && trim($_POST["_$date_field"]) != ''
    ) {
        $_POST[$date_field] = $_POST["_$date_field"];
    }
}

// as _actors virtual field stores json, bypass automatic escaping
if (isset($_UPOST['_actors'])) {
    $_POST['_actors'] = json_decode($_UPOST['_actors'], true);
    $_REQUEST['_actors'] = $_POST['_actors'];
}

if (isset($_POST["add"])) {
    $track->check(-1, CREATE, $_POST);

    if ($track->add($_POST)) {
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($track->getLinkURL());
        }
    }
    Html::back();
} else if (isset($_POST['update'])) {
    if (!$track::canUpdate()) {
        Html::displayRightError();
    }
    $track->update($_POST);

    if (isset($_POST['kb_linked_id'])) {
       //if solution should be linked to selected KB entry
        $params = [
            'knowbaseitems_id' => $_POST['kb_linked_id'],
            'itemtype'         => $track->getType(),
            'items_id'         => $track->getID()
        ];
        $existing = $DB->request(
            'glpi_knowbaseitems_items',
            $params
        );
        if ($existing->numrows() == 0) {
            $kb_item_item = new KnowbaseItem_Item();
            $kb_item_item->add($params);
        }
    }

    Event::log(
        $_POST["id"],
        "ticket",
        4,
        "tracking",
        //TRANS: %s is the user login
        sprintf(__('%s updates an item'), $_SESSION["glpiname"])
    );

    if ($track->can($_POST["id"], READ)) {
        $toadd = '';
       // Copy solution to KB redirect to KB
        if (isset($_POST['_sol_to_kb']) && $_POST['_sol_to_kb']) {
            $toadd = "&_sol_to_kb=1";
        }
        Html::redirect(Ticket::getFormURLWithID($_POST["id"]) . $toadd);
    }
    Session::addMessageAfterRedirect(
        __('You have been redirected because you no longer have access to this ticket'),
        true,
        ERROR
    );
    Html::redirect($CFG_GLPI["root_doc"] . "/front/ticket.php");
} else if (isset($_POST['delete'])) {
    $track->check($_POST['id'], DELETE);
    if ($track->delete($_POST)) {
        Event::log(
            $_POST["id"],
            "ticket",
            4,
            "tracking",
            //TRANS: %s is the user login
            sprintf(__('%s deletes an item'), $_SESSION["glpiname"])
        );
    }
    $track->redirectToList();
} else if (isset($_POST['purge'])) {
    $track->check($_POST['id'], PURGE);
    if ($track->delete($_POST, 1)) {
        Event::log(
            $_POST["id"],
            "ticket",
            4,
            "tracking",
            //TRANS: %s is the user login
            sprintf(__('%s purges an item'), $_SESSION["glpiname"])
        );
    }
    $track->redirectToList();
} else if (isset($_POST["restore"])) {
    $track->check($_POST['id'], DELETE);
    if ($track->restore($_POST)) {
        Event::log(
            $_POST["id"],
            "ticket",
            4,
            "tracking",
            //TRANS: %s is the user login
            sprintf(__('%s restores an item'), $_SESSION["glpiname"])
        );
    }
    Html::back();
} else if (isset($_POST['sla_delete'])) {
    $track->check($_POST["id"], UPDATE);

    $track->deleteLevelAgreement("SLA", $_POST["id"], $_POST['type'], $_POST['delete_date']);
    Event::log(
        $_POST["id"],
        "ticket",
        4,
        "tracking",
        //TRANS: %s is the user login
        sprintf(__('%s updates an item'), $_SESSION["glpiname"])
    );

    Html::redirect(Ticket::getFormURLWithID($_POST["id"]));
} else if (isset($_POST['ola_delete'])) {
    $track->check($_POST["id"], UPDATE);

    $track->deleteLevelAgreement("OLA", $_POST["id"], $_POST['type'], $_POST['delete_date']);
    Event::log(
        $_POST["id"],
        "ticket",
        4,
        "tracking",
        //TRANS: %s is the user login
        sprintf(__('%s updates an item'), $_SESSION["glpiname"])
    );

    Html::redirect(Ticket::getFormURLWithID($_POST["id"]));
} else if (isset($_POST['addme_as_actor'])) {
    $id = (int) $_POST['id'];
    $track->check($id, READ);
    $input = array_merge(Toolbox::addslashes_deep($track->fields), [
        'id' => $id,
        '_itil_' . $_POST['actortype'] => [
            '_type' => "user",
            'users_id' => Session::getLoginUserID(),
            'use_notification' => 1,
        ]
    ]);
    $track->update($input);
    Event::log(
        $id,
        "ticket",
        4,
        "tracking",
        //TRANS: %s is the user login
        sprintf(__('%s adds an actor'), $_SESSION["glpiname"])
    );
    Html::redirect(Ticket::getFormURLWithID($id));
} else if (isset($_POST['delete_document'])) {
    $track->getFromDB((int)$_POST['tickets_id']);
    $doc = new Document();
    $doc->getFromDB((int)$_POST['documents_id']);
    if ($doc->can($doc->getID(), UPDATE)) {
        $document_item = new Document_Item();
        $found_document_items = $document_item->find([
            $track->getAssociatedDocumentsCriteria(),
            'documents_id' => $doc->getID()
        ]);
        foreach ($found_document_items as $item) {
            $document_item->delete(Toolbox::addslashes_deep($item), true);
        }
    }
    Html::back();
}

if (isset($_GET["id"]) && ($_GET["id"] > 0)) {
    $available_options = ['load_kb_sol', '_openfollowup'];
    $options = [];

    foreach ($available_options as $key) {
        if (isset($_GET[$key])) {
            $options[$key] = $_GET[$key];
        }
    }

    $url = KnowbaseItem::getFormURLWithParam($_GET) . '&_in_modal=1&item_itemtype=Ticket&item_items_id=' . $_GET['id'];
    if (strpos($url, '_to_kb=') !== false) {
        $options['after_display'] = Ajax::createIframeModalWindow(
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

    $menus = [
        'central'  => ['helpdesk', 'ticket'],
        'helpdesk' => ["tickets", "ticket"],
    ];
    Ticket::displayFullPageForItem($_GET["id"], $menus, $options);
} else {
    if (Session::getCurrentInterface() != 'central') {
        Html::redirect($CFG_GLPI["root_doc"] . "/front/helpdesk.public.php?create_ticket=1");
        die;
    }

    unset($_REQUEST['id']);
    unset($_GET['id']);
    unset($_POST['id']);

    // alternative email must be empty for create ticket
    unset($_REQUEST['_users_id_requester_notif']['alternative_email']);
    unset($_REQUEST['_users_id_observer_notif']['alternative_email']);
    unset($_REQUEST['_users_id_assign_notif']['alternative_email']);
    unset($_REQUEST['_suppliers_id_assign_notif']['alternative_email']);
    // Add a ticket from item : format data
    if (
        isset($_REQUEST['_add_fromitem'])
        && isset($_REQUEST['itemtype'])
        && isset($_REQUEST['items_id'])
    ) {
        $_REQUEST['items_id'] = [$_REQUEST['itemtype'] => [$_REQUEST['items_id']]];
    }

    if (isset($_GET['showglobalkanban']) && $_GET['showglobalkanban']) {
        Html::header(sprintf(__('%s Kanban'), Ticket::getTypeName(1)), '', "helpdesk", "ticket");
        $track::showKanban(0);
        Html::footer();
    } else {
        $menus = ["helpdesk", "ticket"];
        Ticket::displayFullPageForItem(0, $menus, $_REQUEST);
    }
}
