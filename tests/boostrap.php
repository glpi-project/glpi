<?php

// fix empty CFG_GLPI on boostrap; see https://github.com/sebastianbergmann/phpunit/issues/325
global $CFG_GLPI;
require_once './inc/includes.php';
