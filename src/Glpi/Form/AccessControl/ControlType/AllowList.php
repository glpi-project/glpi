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

namespace Glpi\Form\AccessControl\ControlType;

use AbstractRightsDropdown;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\AccessControl\AccessVote;
use Glpi\Form\AccessControl\FormAccessControl;
use Glpi\Form\AccessControl\FormAccessParameters;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Form\Form;
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
        return __("Allow specifics users, groups or profiles");
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
    public function getWarnings(Form $form, array $warnings): array
    {
        return $warnings;
    }

    #[Override]
    public function renderConfigForm(FormAccessControl $access_control): string
    {
        $config = $access_control->getConfig();
        if (!$config instanceof AllowListConfig) {
            throw new \InvalidArgumentException("Invalid config class");
        }

        $twig = TemplateRenderer::getInstance();
        return $twig->render("pages/admin/form/access_control/allow_list.html.twig", [
            'access_control' => $access_control,
            'config' => $config,
            'label' => $this->getLabel(),
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
        $values = $values ?: []; // No selected values is sent by the html form as an empty string
        return AllowListConfig::jsonDeserialize([
            'user_ids'    => AllowListDropdown::getPostedIds($values, User::class),
            'group_ids'   => AllowListDropdown::getPostedIds($values, Group::class),
            'profile_ids' => AllowListDropdown::getPostedIds($values, Profile::class),
        ]);
    }

    #[Override]
    public function canAnswer(
        JsonFieldInterface $config,
        FormAccessParameters $parameters
    ): AccessVote {
        if (!$config instanceof AllowListConfig) {
            throw new \InvalidArgumentException("Invalid config class");
        }

        if (!$parameters->isAuthenticated()) {
            return AccessVote::Abstain;
        }

        if (!$this->isUserAllowed($config, $parameters)) {
            return AccessVote::Abstain;
        }

        return AccessVote::Grant;
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
        $all_users_are_allowed = in_array(
            AbstractRightsDropdown::ALL_USERS,
            $config->getUserIds()
        );

        if ($all_users_are_allowed) {
            return true;
        }

        return in_array($session_info->getUserId(), $config->getUserIds());
    }

    private function isUserAllowedByGroup(
        AllowListConfig $config,
        SessionInfo $session_info
    ): bool {
        foreach ($session_info->getGroupIds() as $group_id) {
            if (in_array($group_id, $config->getGroupIds())) {
                return true;
            }
        }

        return false;
    }

    private function isUserAllowedByProfile(
        AllowListConfig $config,
        SessionInfo $session_info
    ): bool {
        return in_array($session_info->getProfileId(), $config->getProfileIds());
    }

    public function allowUnauthenticated(JsonFieldInterface $config): bool
    {
        return false;
    }
}
