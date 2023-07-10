<?php

if (($coverage_dir = getenv('COVERAGE_DIR')) === false) {
   $coverage_dir = 'code-coverage';
}
$coverage_path = __DIR__ . '/tests/' . $coverage_dir;

if (!file_exists($coverage_path)) {
    mkdir($coverage_path);
}

$coverageField = new atoum\atoum\report\fields\runner\coverage\html(
    'GLPI',
    $coverage_path
);
$coverageField->setRootUrl('file://' . realpath($coverage_path));

$script
    ->addDefaultReport()
    ->addField($coverageField);

$cloverWriter = new atoum\atoum\writers\file($coverage_path . '/clover.xml');
$cloverReport = new atoum\atoum\reports\asynchronous\clover();
$cloverReport->addWriter($cloverWriter);

$runner->addReport($cloverReport);
