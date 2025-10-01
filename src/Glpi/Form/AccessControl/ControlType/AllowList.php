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
use CommonDBTM;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\AccessControl\AccessVote;
use Glpi\Form\AccessControl\FormAccessControl;
use Glpi\Form\AccessControl\FormAccessParameters;
use Glpi\Form\Export\Context\DatabaseMapper;
use Glpi\Form\Export\Serializer\DynamicExportDataField;
use Glpi\Form\Export\Specification\DataRequirementSpecification;
use Glpi\Form\Form;
use Glpi\Session\SessionInfo;
use Group;
use InvalidArgumentException;
use Override;
use Profile;
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
    public function getConfig(): JsonFieldInterface
    {
        return new AllowListConfig();
    }

    #[Override]
    public function getWarnings(Form $form): array
    {
        return [];
    }

    #[Override]
    public function renderConfigForm(FormAccessControl $access_control): string
    {
        $config = $access_control->getConfig();
        if (!$config instanceof AllowListConfig) {
            throw new InvalidArgumentException("Invalid config class");
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
        Form $form,
        JsonFieldInterface $config,
        FormAccessParameters $parameters
    ): AccessVote {
        if (!$config instanceof AllowListConfig) {
            throw new InvalidArgumentException("Invalid config class");
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
            // Check if the user is directly part of the allowed group
            if (in_array($group_id, $config->getGroupIds())) {
                return true;
            }

            // If at least one parent group of the user is part of the allowlist
            // then he should be able to see the form
            $children_groups = getAncestorsOf(Group::getTable(), $group_id);
            $membership = array_intersect(
                $config->getGroupIds(),
                $children_groups
            );
            if (count($membership) > 0) {
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

    #[Override]
    public function exportDynamicConfig(
        JsonFieldInterface $config
    ): DynamicExportDataField {
        $fallback = new DynamicExportDataField($config, []);
        if (!$config instanceof AllowListConfig) {
            return $fallback;
        }

        $to_handle =  [
            User::class    => AllowListConfig::KEY_USER_IDS,
            Group::class   => AllowListConfig::KEY_GROUP_IDS,
            Profile::class => AllowListConfig::KEY_PROFILE_IDS,
        ];

        // Handler users, groups and profiles ids.
        $data = $config->jsonSerialize();
        $requirements = [];
        foreach ($to_handle as $itemtype => $data_key) {
            /** @var class-string<CommonDBTM> $itemtype */
            // Iterate on ids
            $ids = $data[$data_key] ?? [];
            foreach ($ids as $i => $item_id) {
                // Only operate on valid ids
                if (intval($item_id) === 0) {
                    continue;
                }

                // Try to load item
                $item = $itemtype::getById($item_id);
                if (!$item) {
                    continue;
                }

                // Replace id with name and add a requirement
                $requirement = DataRequirementSpecification::fromItem($item);
                $requirements[] = $requirement;
                $data[$data_key][$i] = $requirement->name;
            }
        }

        return new DynamicExportDataField($data, $requirements);
    }

    #[Override]
    public static function prepareDynamicConfigDataForImport(
        array $config,
        DatabaseMapper $mapper,
    ): array {
        $to_handle =  [
            User::class    => AllowListConfig::KEY_USER_IDS,
            Group::class   => AllowListConfig::KEY_GROUP_IDS,
            Profile::class => AllowListConfig::KEY_PROFILE_IDS,
        ];

        // Handler users, groups and profiles ids.
        foreach ($to_handle as $itemtype => $data_key) {
            /** @var class-string<CommonDBTM> $itemtype */
            // Iterate on names
            $names = $config[$data_key] ?? [];
            foreach ($names as $i => $name) {
                // Exclude special values
                if ($name == "all") {
                    continue;
                }

                // Restore correct id
                $id = $mapper->getItemId($itemtype, $name);
                $config[$data_key][$i] = $id;
            }
        }

        return $config;
    }
}
