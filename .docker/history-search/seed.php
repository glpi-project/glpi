<?php

/** Create repeatable fixtures in the isolated history-search demo only. */

use Glpi\Kernel\Kernel;

if (PHP_SAPI !== 'cli' || getenv('GLPI_HISTORY_SEARCH_DEMO') !== '1') {
    exit("Run this script inside the isolated history-search app container.\n");
}

require dirname(__DIR__, 2) . '/vendor/autoload.php';
(new Kernel())->boot();

$fixtures = [
    'RENAMED' => [
        ['name' => 'DEMO-OLD-PC', 'serial' => 'DEMO-OLD-SERIAL'],
        ['name' => 'DEMO-MIDDLE-PC', 'serial' => 'DEMO-MIDDLE-SERIAL'],
        ['name' => 'DEMO-CURRENT-PC', 'serial' => 'DEMO-CURRENT-SERIAL'],
    ],
    'CURRENT' => [['name' => 'DEMO-OLD-PC']],
    'CONTROL' => [
        ['name' => 'DEMO-CONTROL-PC', 'comment' => 'Initial comment'],
        ['comment' => 'DEMO-OLD-PC'],
    ],
    'UNTOUCHED' => [['name' => 'DEMO-UNTOUCHED-PC']],
];

foreach ($fixtures as $key => $changes) {
    $computer = new Computer();
    $marker = 'HISTORY-SEARCH-DEMO-' . $key;
    if (!$computer->getFromDBByCrit(['otherserial' => $marker])) {
        $id = $computer->add(array_shift($changes) + ['entities_id' => 0, 'otherserial' => $marker]);
        if (!$id) {
            throw new RuntimeException('Cannot create ' . $marker);
        }
        foreach ($changes as $change) {
            if (!$computer->update(['id' => $id] + $change)) {
                throw new RuntimeException('Cannot update ' . $marker);
            }
        }
    }
    printf("%s: #%d %s\n", $key, $computer->getID(), $computer->fields['name']);
}
