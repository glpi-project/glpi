<?php

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

(new \Glpi\Config\LegacyConfigurators\BaseConfig($root))->execute();
(new \Glpi\Config\LegacyConfigurators\DefineConstants())->execute();
(new \Glpi\Config\LegacyConfigurators\DefineDbFunctions())->execute();
