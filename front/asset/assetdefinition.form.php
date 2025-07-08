<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

require_once(__DIR__ . '/../_check_webserver_config.php');

/**
 * @var array $CFG_GLPI
 */

use Glpi\Asset\AssetDefinition;
use Glpi\Event;

$asset_definition = new AssetDefinition();

if (isset($_POST['add'])) {
    $asset_definition->check(-1, CREATE, $_POST);

    if ($new_id = $asset_definition->add($_POST)) {
        Event::log(
            $new_id,
            AssetDefinition::class,
            4,
            'setup',
            sprintf(__('%1$s adds the item %2$s'), $_SESSION['glpiname'], $_POST['system_name'])
        );
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($asset_definition->getLinkURL());
        }
    }
    Html::back();
} elseif (isset($_POST['update'])) {
    $asset_definition->check($_POST['id'], UPDATE);

    if (
        (bool) ($_POST['_update_capacities'] ?? false)
        && !array_key_exists('capacities', $_POST)
    ) {
        // If no capacities are checked, `$_POST['capacities']` will not be sent in request.
        $_POST['capacities'] = [];
    }

    if (array_key_exists('profiles', $_POST)) {
        // Ensure profiles can be updated
        foreach (array_keys($_POST['profiles']) as $profile_id) {
            $profile = new Profile();
            $profile->check((int) $profile_id, UPDATE);
        }

        // Convert profiles input from the `components/checkbox_matrix.html.twig` format
        // to the expected format.
        foreach ($_POST['profiles'] as $profiles_id => $rights_matrix) {
            $combined_rights = 0;
            foreach ($rights_matrix as $right_value => $is_enabled) {
                if ($is_enabled) {
                    $combined_rights |= (int) $right_value;
                }
            }
            $_POST['profiles'][$profiles_id] = $combined_rights;
        }
    }

    if ($asset_definition->update($_POST)) {
        Event::log(
            $_POST['id'],
            AssetDefinition::class,
            4,
            'setup',
            sprintf(__('%s updates an item'), $_SESSION['glpiname'])
        );
    }
    Html::back();
} elseif (isset($_POST['purge'])) {
    $asset_definition->check($_POST['id'], PURGE);
    if ($asset_definition->delete($_POST)) {
        Event::log(
            $_POST['id'],
            AssetDefinition::class,
            4,
            'setup',
            sprintf(__('%s purges an item'), $_SESSION['glpiname'])
        );
    }
    $asset_definition->redirectToList();
} else {
    $menus = ['config', AssetDefinition::class];
    AssetDefinition::displayFullPageForItem($_GET['id'] ?? 0, $menus);
}
