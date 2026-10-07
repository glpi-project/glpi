<?php

use GlpiPlugin\Approvalchain\Chain;

Session::checkRight('config', READ);

Html::header(Chain::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], 'tools', strtolower(Chain::class));
Search::show(Chain::class);
Html::footer();
