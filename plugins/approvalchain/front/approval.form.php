<?php

use GlpiPlugin\Approvalchain\Approval;

Session::checkLoginUser();
$uid = (int) Session::getLoginUserID();

if (isset($_POST['start'])) {
    $ticket = new Ticket();
    if (!$ticket->getFromDB((int) ($_POST['tickets_id'] ?? 0)) || !$ticket->canUpdateItem()) {
        Session::addMessageAfterRedirect(__('You cannot start an approval chain on this ticket.', 'approvalchain'), false, ERROR);
        Html::back();
    }
    if (!Approval::start($ticket, (int) ($_POST['plugin_approvalchain_chains_id'] ?? 0), $uid)) {
        Session::addMessageAfterRedirect(__('The approval chain could not be started (already running or not active).', 'approvalchain'), false, ERROR);
    }
} elseif (isset($_POST['approve']) || isset($_POST['refuse'])) {
    Approval::decide((int) ($_POST['step_id'] ?? 0), isset($_POST['approve']), (string) ($_POST['comment'] ?? ''), $uid);
} elseif (isset($_POST['cancel'])) {
    $approval = new Approval();
    $ticket   = new Ticket();
    if ($approval->getFromDB((int) ($_POST['id'] ?? 0))
        && $ticket->getFromDB((int) $approval->fields['tickets_id'])
        && $ticket->canUpdateItem()) {
        Approval::cancel((int) $approval->getID());
    } else {
        Session::addMessageAfterRedirect(__('You cannot cancel this approval chain.', 'approvalchain'), false, ERROR);
    }
}
Html::back();
