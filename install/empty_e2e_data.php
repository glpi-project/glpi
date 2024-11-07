<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

if (!isset($builder, $tables)) {
    return [];
}

/**
 * @var object $builder
 * @var array $tables
 */

$root_entity = array_filter($tables['glpi_entities'], static fn ($e) => $e['id'] === 0);
$root_entity = current($root_entity);
const USER_E2E = 7;

// Main E2E test entity
$e2e_entity = array_replace($root_entity, [
    'id' => 1,
    'name' => 'E2ETestEntity',
    'entities_id' => 0,
    'completename' => __('Root entity') . ' > E2ETestEntity',
    'level' => 2,
]);
$tables['glpi_entities'][] = $e2e_entity;

// Sub entity 1
$e2e_subentity1 = array_replace($root_entity, [
    'id' => 2,
    'name' => 'E2ETestSubEntity1',
    'entities_id' => 1,
    'completename' => __('Root entity') . ' > E2ETestEntity > E2ETestSubEntity1',
    'level' => 3,
]);
$tables['glpi_entities'][] = $e2e_subentity1;

// Sub entity 2
$e2e_subentity2 = array_replace($root_entity, [
    'id' => 3,
    'name' => 'E2ETestSubEntity2',
    'entities_id' => 1,
    'completename' => __('Root entity') . ' > E2ETestEntity > E2ETestSubEntity2',
    'level' => 3,
]);
$tables['glpi_entities'][] = $e2e_subentity2;

// New e2e super-admin user (login: e2e_tests, password: glpi)
$default_glpi_user = array_filter($tables['glpi_users'], static fn ($u) => $u['id'] === $builder::USER_GLPI);
$e2e_user = array_shift($default_glpi_user);
$e2e_user = array_replace($e2e_user, [
    'id' => USER_E2E,
    'name' => 'e2e_tests',
    'realname' => 'E2E Tests',
    'profiles_id' => $builder::PROFILE_SUPER_ADMIN,
]);
$tables['glpi_users'][] = $e2e_user;

// Assign e2e user all default profiles on the e2e entity
$tables['glpi_profiles_users'][] = [
    'id' => 6,
    'users_id' => USER_E2E,
    'profiles_id' => $builder::PROFILE_SUPER_ADMIN,
    'entities_id' => 1,
    'is_recursive' => 1,
    'is_dynamic' => 0,
];
$tables['glpi_profiles_users'][] = [
    'id' => 7,
    'users_id' => USER_E2E,
    'profiles_id' => $builder::PROFILE_SELF_SERVICE,
    'entities_id' => 1,
    'is_recursive' => 1,
    'is_dynamic' => 0,
];
$tables['glpi_profiles_users'][] = [
    'id' => 8,
    'users_id' => USER_E2E,
    'profiles_id' => $builder::PROFILE_OBSERVER,
    'entities_id' => 1,
    'is_recursive' => 1,
    'is_dynamic' => 0,
];
$tables['glpi_profiles_users'][] = [
    'id' => 9,
    'users_id' => USER_E2E,
    'profiles_id' => $builder::PROFILE_ADMIN,
    'entities_id' => 1,
    'is_recursive' => 1,
    'is_dynamic' => 0,
];
$tables['glpi_profiles_users'][] = [
    'id' => 10,
    'users_id' => USER_E2E,
    'profiles_id' => $builder::PROFILE_HOTLINER,
    'entities_id' => 1,
    'is_recursive' => 1,
    'is_dynamic' => 0,
];
$tables['glpi_profiles_users'][] = [
    'id' => 11,
    'users_id' => USER_E2E,
    'profiles_id' => $builder::PROFILE_TECHNICIAN,
    'entities_id' => 1,
    'is_recursive' => 1,
    'is_dynamic' => 0,
];
$tables['glpi_profiles_users'][] = [
    'id' => 12,
    'users_id' => USER_E2E,
    'profiles_id' => $builder::PROFILE_READ_ONLY,
    'entities_id' => 1,
    'is_recursive' => 1,
    'is_dynamic' => 0,
];

foreach (Ticket::getAllStatusArray() as $status => $label) {
    $tables['glpi_tickets'][] = [
        'id' => null,
        'name' => 'E2E Test Ticket ' . $label,
        'entities_id' => 1,
        'users_id_recipient' => USER_E2E,
        'status' => $status,
    ];
}

$tables['glpi_reminders'][] = [
    'id' => null,
    'users_id' => USER_E2E,
    'name' => 'Public reminder 1',
    'text' => 'This is a public reminder.',
    'begin' => '2023-10-01 16:45:11',
    'end' => date('Y-m-d H:i:s', strtotime('+1 year')),
];

$tables['glpi_reminders_users'][] = [
    'users_id' => USER_E2E,
    'reminders_id' => count($tables['glpi_reminders']),
];
