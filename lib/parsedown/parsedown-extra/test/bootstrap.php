<?php

$dir = file_exists('../parsedown/')
    ? '../parsedown/' # child
    : 'vendor/erusev/parsedown/'; # parent

include $dir . 'Parsedown.php';
include $dir . 'test/ParsedownTest.php';

include 'ParsedownExtra.php';