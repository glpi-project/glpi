<?php

$ruleset = new TwigCsFixer\Ruleset\Ruleset();
$ruleset->addStandard(new TwigCsFixer\Standard\TwigCsFixer());

// remove some rules
$ruleset->removeRule(TwigCsFixer\Rules\String\SingleQuoteRule::class);
$ruleset->removeRule(TwigCsFixer\Rules\String\HashQuoteRule::class);
$ruleset->removeRule(TwigCsFixer\Rules\Punctuation\TrailingCommaMultiLineRule::class);

$config = new TwigCsFixer\Config\Config();
$config->setCacheFile(sys_get_temp_dir() . '/glpi-twig-cs-fixer.cache');
$config->setRuleset($ruleset);

return $config;
