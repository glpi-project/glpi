<?php

$AJAX_INCLUDE = 1;
include ('../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkRight('ticket', UPDATE);

if ($_POST["actortype"] > 0) {
    $ticket = new Ticket();
    $rand   = mt_rand();
    $ticket->showActorReplaceForm($_POST["actortype"], $rand, $_POST["tickets"], false);
    echo "&nbsp;<input type='submit' name='replace_actor' class='submit' value=\""._sx('button','Replace')."\">";
}
