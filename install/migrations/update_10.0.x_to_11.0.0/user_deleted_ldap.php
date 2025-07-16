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
 * @var Migration $migration
 */
// Migrate user_deleted_ldap into 3 distinct fields
$user_deleted_ldap = Config::getConfigurationValue('core', 'user_deleted_ldap');
if ($user_deleted_ldap !== null) {
    switch ($user_deleted_ldap) {
        // AuthLDAP::DELETED_USER_PRESERVE (preserve user)
        default:
        case 0:
            $user_deleted_ldap_user = AuthLDAP::DELETED_USER_ACTION_USER_DO_NOTHING;
            $user_deleted_ldap_groups = AuthLDAP::DELETED_USER_ACTION_GROUPS_DO_NOTHING;
            $user_deleted_ldap_authorizations = AuthLDAP::DELETED_USER_ACTION_AUTHORIZATIONS_DO_NOTHING;
            break;

            // AuthLDAP::DELETED_USER_DELETE (put user in trashbin)
        case 1:
            $user_deleted_ldap_user = AuthLDAP::DELETED_USER_ACTION_USER_MOVE_TO_TRASHBIN;
            $user_deleted_ldap_groups = AuthLDAP::DELETED_USER_ACTION_GROUPS_DO_NOTHING;
            $user_deleted_ldap_authorizations = AuthLDAP::DELETED_USER_ACTION_AUTHORIZATIONS_DO_NOTHING;
            break;

            // AuthLDAP::DELETED_USER_WITHDRAWDYNINFO (withdraw dynamic authorizations and groups)
        case 2:
            $user_deleted_ldap_user = AuthLDAP::DELETED_USER_ACTION_USER_DO_NOTHING;
            $user_deleted_ldap_groups =  AuthLDAP::DELETED_USER_ACTION_GROUPS_DELETE_DYNAMIC;
            $user_deleted_ldap_authorizations = AuthLDAP::DELETED_USER_ACTION_AUTHORIZATIONS_DELETE_DYNAMIC;
            break;

            // AuthLDAP::DELETED_USER_DISABLE (disable user)
        case 3:
            $user_deleted_ldap_user = AuthLDAP::DELETED_USER_ACTION_USER_DISABLE;
            $user_deleted_ldap_groups = AuthLDAP::DELETED_USER_ACTION_GROUPS_DO_NOTHING;
            $user_deleted_ldap_authorizations = AuthLDAP::DELETED_USER_ACTION_AUTHORIZATIONS_DO_NOTHING;
            break;

            // AuthLDAP::DELETED_USER_DISABLEANDWITHDRAWDYNINFO (disable user and withdraw dynamic authorizations/groups)
        case 4:
            $user_deleted_ldap_user = AuthLDAP::DELETED_USER_ACTION_USER_DISABLE;
            $user_deleted_ldap_groups = AuthLDAP::DELETED_USER_ACTION_GROUPS_DELETE_DYNAMIC;
            $user_deleted_ldap_authorizations = AuthLDAP::DELETED_USER_ACTION_AUTHORIZATIONS_DELETE_DYNAMIC;
            break;

            // AuthLDAP::DELETED_USER_DISABLEANDDELETEGROUPS (disable user and withdraw groups)
        case 5:
            $user_deleted_ldap_user = AuthLDAP::DELETED_USER_ACTION_USER_DISABLE;
            $user_deleted_ldap_groups = AuthLDAP::DELETED_USER_ACTION_GROUPS_DELETE_ALL;
            $user_deleted_ldap_authorizations = AuthLDAP::DELETED_USER_ACTION_AUTHORIZATIONS_DO_NOTHING;
            break;
    }

    $migration->addConfig([
        'user_deleted_ldap_user'           => $user_deleted_ldap_user,
        'user_deleted_ldap_groups'         => $user_deleted_ldap_groups,
        'user_deleted_ldap_authorizations' => $user_deleted_ldap_authorizations,
    ], 'core');

    $migration->removeConfig(['user_deleted_ldap']);
}
