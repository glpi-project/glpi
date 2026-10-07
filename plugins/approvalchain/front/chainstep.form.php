<?php

use GlpiPlugin\Approvalchain\Chain;
use GlpiPlugin\Approvalchain\ChainStep;

Session::checkRight('config', UPDATE);
$step = new ChainStep();

if (isset($_POST['add'])) {
    $step->add($_POST);
} elseif (isset($_POST['update'])) {
    $step->update($_POST);
} elseif (isset($_POST['purge'])) {
    $step->delete($_POST, true);
} elseif (isset($_POST['move_up'])) {
    ChainStep::move((int) $_POST['id'], 'up');
} elseif (isset($_POST['move_down'])) {
    ChainStep::move((int) $_POST['id'], 'down');
} else {
    Html::redirect(Chain::getSearchURL());
}
Html::back();
