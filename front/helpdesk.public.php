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

use Glpi\Application\View\TemplateRenderer;

include('../inc/includes.php');

// Change profile system
if (isset($_REQUEST['newprofile'])) {
    if (isset($_SESSION["glpiprofiles"][$_REQUEST['newprofile']])) {
        Session::changeProfile($_REQUEST['newprofile']);

        if (Session::getCurrentInterface() == "central") {
            Html::redirect($CFG_GLPI['root_doc'] . "/front/central.php");
        } else {
            Html::redirect($_SERVER['PHP_SELF']);
        }
    } else {
        Html::redirect(preg_replace("/entities_id=.*/", "", $_SERVER['HTTP_REFERER']));
    }
}

// Manage entity change
if (isset($_GET["active_entity"])) {
    if (!isset($_GET["is_recursive"])) {
        $_GET["is_recursive"] = 0;
    }
    if (Session::changeActiveEntities($_GET["active_entity"], $_GET["is_recursive"])) {
        if ($_GET["active_entity"] == $_SESSION["glpiactive_entity"]) {
            Html::redirect(preg_replace("/(\?|&|" . urlencode('?') . "|" . urlencode('&') . ")?(entities_id|active_entity).*/", "", $_SERVER['HTTP_REFERER']));
        }
    }
}

// Redirect management
if (isset($_GET["redirect"])) {
    Toolbox::manageRedirect($_GET["redirect"]);
}

// redirect if no create ticket right
if (
    !Session::haveRight('ticket', CREATE)
    && !Session::haveRight('reminder_public', READ)
    && !Session::haveRight("rssfeed_public", READ)
) {
    if (
        Session::haveRight('followup', ITILFollowup::SEEPUBLIC)
        || Session::haveRight('task', TicketTask::SEEPUBLIC)
        || Session::haveRightsOr('ticketvalidation', [TicketValidation::VALIDATEREQUEST,
            TicketValidation::VALIDATEINCIDENT
        ])
    ) {
        Html::redirect($CFG_GLPI['root_doc'] . "/front/ticket.php");
    } else if (Session::haveRightsOr('reservation', [READ, ReservationItem::RESERVEANITEM])) {
        Html::redirect($CFG_GLPI['root_doc'] . "/front/reservationitem.php");
    } else if (Session::haveRight('knowbase', KnowbaseItem::READFAQ)) {
        Html::redirect($CFG_GLPI['root_doc'] . "/front/helpdesk.faq.php");
    }
}

Session::checkValidSessionId();

if (isset($_GET['create_ticket'])) {
    Html::helpHeader(__('New ticket'), "create_ticket");
    $ticket = new Ticket();
    $ticket->showFormHelpdesk(Session::getLoginUserID());
} else {
    Html::helpHeader(__('Home'));

    $password_alert = "";
    $user = new User();
    $user->getFromDB(Session::getLoginUserID());
    if ($user->fields['authtype'] == Auth::DB_GLPI && $user->shouldChangePassword()) {
        $password_alert = sprintf(
            __('Your password will expire on %s.'),
            Html::convDateTime(date('Y-m-d H:i:s', $user->getPasswordExpirationTime()))
        );
    }

    $ticket_summary = "";
    $survey_list    = "";
    if (Session::haveRight('ticket', CREATE)) {
        $ticket_summary = Ticket::showCentralCount(true, false);
        $survey_list    = Ticket::showCentralList(0, "survey", false, false);
    }

    $reminder_list = "";
    if (Session::haveRight("reminder_public", READ)) {
        $reminder_list = Reminder::showListForCentral(false, false);
    }

    $rss_feed = "";
    if (Session::haveRight("rssfeed_public", READ)) {
        $rss_feed = RSSFeed::showListForCentral(false, false);
    }

    $kb_popular    = "";
    $kb_recent     = "";
    $kb_lastupdate = "";
    if (Session::haveRight('knowbase', KnowbaseItem::READFAQ)) {
        $kb_popular    = KnowbaseItem::showRecentPopular("popular", false);
        $kb_recent     = KnowbaseItem::showRecentPopular("recent", false);
        $kb_lastupdate = KnowbaseItem::showRecentPopular("lastupdate", false);
    }

    Html::requireJs('masonry');
    TemplateRenderer::getInstance()->display('pages/self-service/home.html.twig', [
        'password_alert' => $password_alert,
        'ticket_summary' => $ticket_summary,
        'survey_list'    => $survey_list,
        'reminder_list'  => $reminder_list,
        'rss_feed'       => $rss_feed,
        'kb_popular'     => $kb_popular,
        'kb_recent'      => $kb_recent,
        'kb_lastupdate'  => $kb_lastupdate,
    ]);
}

Html::helpFooter();
