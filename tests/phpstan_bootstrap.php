<?php

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';

(new \Glpi\Config\LegacyConfigurators\BaseConfig($root, 'development'))->execute();
