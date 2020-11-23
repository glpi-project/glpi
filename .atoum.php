<?php

$tests_dir = __DIR__ . '/tests/';
$coverage_dir = $tests_dir . 'code-coverage/';

if (!file_exists($coverage_dir)) {
    mkdir($coverage_dir);
}

$coverageField = new atoum\atoum\report\fields\runner\coverage\html(
    'GLPI',
    $coverage_dir
);
$coverageField->setRootUrl('file://' . realpath($coverage_dir));

$script
    ->addDefaultReport()
    ->addField($coverageField);

//$script->addDefaultReport();

$cloverWriter = new atoum\atoum\writers\file($coverage_dir . 'clover.xml');
$cloverReport = new atoum\atoum\reports\asynchronous\clover();
$cloverReport->addWriter($cloverWriter);

$runner->addReport($cloverReport);
