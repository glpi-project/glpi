<?php
/*
 * @version $Id:  $
-------------------------------------------------------------------------
GLPI - Gestionnaire Libre de Parc Informatique
Copyright (C) 2015 Teclib'.

http://glpi-project.org

based on GLPI - Gestionnaire Libre de Parc Informatique
Copyright (C) 2003-2014 by the INDEPNET Development Team.

-------------------------------------------------------------------------

LICENSE

This file is part of GLPI.

GLPI is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

GLPI is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with GLPI. If not, see <http://www.gnu.org/licenses/>.
--------------------------------------------------------------------------
*/
define('GLPI_ROOT','..');
$AJAX_INCLUDE = 1;
include (GLPI_ROOT."/inc/includes.php");

// Send UTF8 Headers
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

$_REQUEST['_users_id_observer_notif']['use_notification'] = true;
$_REQUEST['_users_id_observer']                           = 0;
$_REQUEST['entities_id']                                  = $_SESSION["glpiactive_entity"];

$ticket = new Ticket();
$rand_observer = $ticket->showActorAddFormOnCreate(CommonITILActor::OBSERVER, $_REQUEST);

echo '<hr>';

echo "<span id='observer_$rand_observer'></span>";
Ajax::updateItemOnSelectEvent("dropdown__users_id_observer[]$rand_observer",
                              "observer_$rand_observer",
                              $CFG_GLPI["root_doc"]."/ajax/helpdesk_observer.php");
