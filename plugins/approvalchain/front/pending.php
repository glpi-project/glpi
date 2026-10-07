<?php

use GlpiPlugin\Approvalchain\Approval;
use GlpiPlugin\Approvalchain\Chain;

Session::checkLoginUser();

$title = __('My pending approvals', 'approvalchain');
if (Session::getCurrentInterface() === 'helpdesk') {
    Html::helpHeader($title);
} else {
    Html::header($title, $_SERVER['PHP_SELF'], 'tools', strtolower(Chain::class));
}
Approval::showPending((int) Session::getLoginUserID());
Html::footer();
