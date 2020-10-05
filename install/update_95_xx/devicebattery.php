<?php

$migration->addField('glpi_items_devicebatteries', 'real_capacity', 'integer', [
    'after' => 'states_id'
]);