<?php
define('GLPI_ROOT','..');
$AJAX_INCLUDE = 1;
include (GLPI_ROOT."/inc/includes.php");

// Send UTF8 Headers
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

$_REQUEST['_users_id_observer_notif']['use_notification'] = true;
$_REQUEST['_users_id_observer'] = 0;
$_REQUEST['entities_id'] = $_SESSION["glpiactive_entity"];

$ticket = new Ticket;
$rand_observer = $ticket->showActorAddFormOnCreate(CommonITILActor::OBSERVER, $_REQUEST);

echo '<hr>';

echo "<span id='observer_$rand_observer'></span>";
Ajax::updateItemOnSelectEvent("dropdown__users_id_observer[]$rand_observer", 
                              "observer_$rand_observer",
                              $CFG_GLPI["root_doc"]."/ajax/helpdesk_observer.php");
