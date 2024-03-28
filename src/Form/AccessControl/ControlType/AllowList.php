<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace Glpi\Form\AccessControl\ControlType;

use FreeJsonConfigInterface;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Form\Form;
use Group;
use Override;
use Profile;
use SessionInfo;
use User;

final class AllowList implements ControlTypeInterface
{
    #[Override]
    public function getLabel(): string
    {
        return __("Restrict to specifics users, groups or profiles");
    }

    #[Override]
    public function getIcon(): string
    {
        return "ti ti-list-check";
    }

    #[Override]
    public function getConfigClass(): string
    {
        return AllowListConfig::class;
    }

    #[Override]
    public function renderConfigForm(FreeJsonConfigInterface $config): string
    {
        if (!$config instanceof AllowListConfig) {
            throw new \InvalidArgumentException("Invalid config class");
        }

        $twig = TemplateRenderer::getInstance();
        return $twig->render("pages/admin/form/access_control/allow_list.html.twig", [
            'config' => $config,
        ]);
    }

    #[Override]
    public function getWeight(): int
    {
        return 10;
    }

    #[Override]
    public function createConfigFromUserInput(array $input): AllowListConfig
    {
        $values = $input['_allow_list_dropdown'] ?? [];
        return new AllowListConfig([
            'user_ids'    => AllowListDropdown::getPostedIds($values, User::class),
            'group_ids'   => AllowListDropdown::getPostedIds($values, Group::class),
            'profile_ids' => AllowListDropdown::getPostedIds($values, Profile::class),
        ]);
    }

    #[Override]
    public function allowUnauthenticatedUsers(FreeJsonConfigInterface $config): bool
    {
        return false;
    }

    #[Override]
    public function canAnswer(
        FreeJsonConfigInterface $config,
        SessionInfo $session
    ): bool {
        if (!$config instanceof AllowListConfig) {
            throw new \InvalidArgumentException("Invalid config class");
        }

        if (
            empty($config->user_ids)
            && empty($config->group_ids)
            && empty($config->profile_ids)
        ) {
            // No restrictions
            return true;
        }

        // User allowlist
        if (in_array($session->user_id, $config->user_ids)) {
            return true;
        }

        // Group allowlist
        foreach ($session->group_ids as $group_id) {
            if (in_array($group_id, $config->group_ids)) {
                return true;
            }
        }

        // Profiles allowlist
        if (in_array($session->profile_id, $config->profile_ids)) {
            return true;
        }

        return false;
    }
}
