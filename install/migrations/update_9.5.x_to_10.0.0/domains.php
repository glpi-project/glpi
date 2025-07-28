<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

/**
 * @var DBmysql $DB
 * @var Migration $migration
 */

/** Domains improvements */

/** Add templates to domains  */

use Glpi\DBAL\QueryExpression;

use function Safe\json_encode;

$migration->addField('glpi_domains', 'is_template', 'bool', [
    'after' => 'comment',
]);
$migration->addField('glpi_domains', 'template_name', 'string', [
    'after' => 'is_template',
]);
$migration->addKey('glpi_domains', 'is_template');
/** /Add templates to domains  */

/** Active domains */
$migration->addField('glpi_domains', 'is_active', 'bool', ['after' => 'template_name']);
$migration->addKey('glpi_domains', 'is_active');
$migration->addPostQuery(
    $DB->buildUpdate(
        'glpi_domains',
        ['is_active' => 1],
        [new QueryExpression('true')]
    )
);
/** /Active domains */

//remove "useless "other" field
$migration->dropField('glpi_domains', 'others');

// Add fields descriptor field
if (!$DB->fieldExists('glpi_domainrecordtypes', 'fields')) {
    $migration->addField(
        'glpi_domainrecordtypes',
        'fields',
        'text',
        [
            'after'  => 'name',
        ]
    );
    foreach (DomainRecordType::getDefaults() as $type) {
        if (countElementsInTable('glpi_domainrecordtypes', ['name' => $type['name']]) === 0) {
            continue;
        }
        $migration->addPostQuery(
            $DB->buildUpdate(
                'glpi_domainrecordtypes',
                ['fields' => $type['fields']],
                ['name' => $type['name']]
            )
        );
    }
} else {
    // "fields" descriptor already exists, but may correspond to an outdated version

    //add is_fqdn on some domain records types
    $fields = [
        'CNAME'  => ['target'],
        'MX'     => ['server'],
        'SOA'    => ['primary_name_server', 'primary_contact'],
        'SRV'    => ['target'],
    ];

    $fields_it = $DB->request([
        'FROM'   => 'glpi_domainrecordtypes',
        'WHERE'  => ['name' => array_keys($fields)],
    ]);
    foreach ($fields_it as $field) {
        if (empty($field['fields']) || $field['fields'] === '[]') {
            if ($field['name'] === 'CNAME') {
                //cname field definition has been added
                $field['fields'] = json_encode([[
                    'key'         => 'target',
                    'label'       => 'Target',
                    'placeholder' => 'sip.example.com.',
                    'is_fqdn'     => true,
                ],
                ]);
            } else {
                continue;
            }
        }
        $type_fields = DomainRecordType::decodeFields($field['fields']);
        $updated = false;
        foreach ($type_fields as &$conf) {
            if (in_array($conf['key'], $fields[$field['name']])) {
                $conf['is_fqdn'] = true;
                $updated = true;
            }
        }

        if ($updated) {
            $migration->addPostQuery(
                $DB->buildUpdate(
                    'glpi_domainrecordtypes',
                    ['fields' => json_encode($type_fields)],
                    ['name' => $field['name']]
                )
            );
        }
    }
}

// Create new CAA default
if (countElementsInTable('glpi_domainrecordtypes', ['name' => 'CAA']) === 0) {
    foreach (DomainRecordType::getDefaults() as $type) {
        if ($type['name'] === 'CAA') {
            unset($type['id']);
            $migration->addPostQuery(
                $DB->buildInsert(
                    'glpi_domainrecordtypes',
                    $type
                )
            );
            break;
        }
    }
}

// Add a field to store record data as an object if user inputs data using helper form
$migration->addField(
    'glpi_domainrecords',
    'data_obj',
    'text',
    [
        'after'  => 'data',
    ]
);

// Rename date_creation (date the domain is created outside GLPI) field, then re-add field (Date the GLPI item was created)
if (!$DB->fieldExists(Domain::getTable(), 'date_domaincreation')) {
    $migration->changeField(Domain::getTable(), 'date_creation', 'date_domaincreation', 'datetime', [
        'after'  => 'date_expiration',
    ]);
    $migration->dropKey(Domain::getTable(), 'date_creation');
    $migration->migrationOneTable(Domain::getTable());
    $migration->addField(Domain::getTable(), 'date_creation', 'datetime');
    $migration->addKey(Domain::getTable(), 'date_creation');
    $migration->addKey(Domain::getTable(), 'date_domaincreation');
}
