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

use Glpi\Form\AccessControl\FormAccessParameters;
use JsonConfigInterface;
use Glpi\Application\View\TemplateRenderer;
use Group;
use Override;
use Profile;
use Glpi\Session\SessionInfo;
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
    public function renderConfigForm(JsonConfigInterface $config): string
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
    public function canAnswer(
        JsonConfigInterface $config,
        FormAccessParameters $parameters
    ): bool {
        if (!$config instanceof AllowListConfig) {
            throw new \InvalidArgumentException("Invalid config class");
        }

        if (!$parameters->isAuthenticated()) {
            return false;
        }

        return $this->isUserAllowed($config, $parameters);
    }

    private function isUserAllowed(
        AllowListConfig $config,
        FormAccessParameters $parameters
    ): bool {
        $session_info = $parameters->getSessionInfo();

        if ($this->isUserDirectlyAllowed($config, $session_info)) {
            return true;
        } elseif ($this->isUserAllowedByGroup($config, $session_info)) {
            return true;
        } elseif ($this->isUserAllowedByProfile($config, $session_info)) {
            return true;
        }

        return false;
    }

    private function isUserDirectlyAllowed(
        AllowListConfig $config,
        SessionInfo $session_info
    ): bool {
        return in_array($session_info->getUserId(), $config->user_ids);
    }

    private function isUserAllowedByGroup(
        AllowListConfig $config,
        SessionInfo $session_info
    ): bool {
        foreach ($session_info->getGroupsIds() as $group_id) {
            if (in_array($group_id, $config->group_ids)) {
                return true;
            }
        }

        return false;
    }

    private function isUserAllowedByProfile(
        AllowListConfig $config,
        SessionInfo $session_info
    ): bool {
        return in_array($session_info->getProfileId(), $config->profile_ids);
    }
}
