<?php

echo "# Boostrap\n";

// fix empty CFG_GLPI on boostrap; see https://github.com/sebastianbergmann/phpunit/issues/325
global $CFG_GLPI;

// include glpi core
require_once './inc/includes.php';
