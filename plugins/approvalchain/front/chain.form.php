<?php

use GlpiPlugin\Approvalchain\Chain;

Session::checkRight('config', READ);
$chain = new Chain();

if (isset($_POST['add'])) {
    Session::checkRight('config', UPDATE);
    $newID = $chain->add($_POST);
    if ($newID) {
        Html::redirect($chain->getFormURLWithID($newID));
    }
    Html::back();
} elseif (isset($_POST['update'])) {
    Session::checkRight('config', UPDATE);
    $chain->update($_POST);
    Html::back();
} elseif (isset($_POST['purge']) || isset($_POST['delete'])) {
    Session::checkRight('config', UPDATE);
    $chain->delete($_POST, true);
    Html::redirect($chain->getSearchURL());
}

Chain::displayFullPageForItem((int) ($_GET['id'] ?? 0), ['tools', strtolower(Chain::class)]);
