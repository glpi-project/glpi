<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace Glpi\Knowbase\SidePanel;

use Dropdown;
use Entity;
use Entity_KnowbaseItem;
use Group;
use Group_KnowbaseItem;
use KnowbaseItem;
use KnowbaseItem_Profile;
use KnowbaseItem_User;
use Override;
use Profile;
use Session;
use User;

final class PermissionsRenderer implements RendererInterface
{
    #[Override]
    public function canView(KnowbaseItem $item): bool
    {
        return $item->can($item->getID(), READ);
    }

    #[Override]
    public function getTemplate(): string
    {
        return "pages/tools/kb/modal/permissions.html.twig";
    }

    #[Override]
    public function getParams(KnowbaseItem $item): array
    {
        $id = $item->getID();
        $can_edit = $item->canEdit($id);
        $rand = mt_rand();
        $owner_id = (int) $item->fields['users_id'];

        $entries = $this->buildEntries($item, $owner_id);

        $visiblity_dropdown_params = [
            'type'  => '__VALUE__',
            'right' => ($item->getField('is_faq') ? 'faq' : 'knowbase'),
            'allusers' => 1,
        ];
        if (isset($item->fields['entities_id'])) {
            $visiblity_dropdown_params['entity'] = $item->fields['entities_id'];
        }
        if (isset($item->fields['is_recursive'])) {
            $visiblity_dropdown_params['is_recursive'] = $item->fields['is_recursive'];
        }

        return [
            'id' => $id,
            'rand' => $rand,
            'can_edit' => $can_edit,
            'is_owner' => ($owner_id === Session::getLoginUserID()),
            'owner' => [
                'name' => getUserName($owner_id),
                'users_id' => $owner_id,
            ],
            'entries' => $entries,
            'visiblity_dropdown_params' => $visiblity_dropdown_params,
        ];
    }

    /**
     * Build entries array for the permissions list
     *
     * @param KnowbaseItem $item
     * @param int $owner_id
     * @return array<int, array{itemtype: string, id: int, type: string, type_label: string, name: string, icon: string, badge_class: string, entity_name: ?string, is_recursive: bool, is_owner: bool}>
     */
    private function buildEntries(KnowbaseItem $item, int $owner_id): array
    {
        $id = $item->getID();
        $entries = [];

        $users = KnowbaseItem_User::getUsers($id);
        foreach ($users as $val) {
            foreach ($val as $data) {
                $entries[] = [
                    'itemtype'     => 'KnowbaseItem_User',
                    'id'           => $data['id'],
                    'type'         => 'User',
                    'type_label'   => User::getTypeName(1),
                    'name'         => getUserName($data['users_id']),
                    'icon'         => 'ti-user',
                    'badge_class'  => 'bg-azure-lt',
                    'entity_name'  => null,
                    'is_recursive' => false,
                    'is_owner'     => ((int) $data['users_id'] === $owner_id),
                ];
            }
        }

        $groups = Group_KnowbaseItem::getGroups($id);
        foreach ($groups as $val) {
            foreach ($val as $data) {
                $entity_name = null;
                if ($data['entities_id'] !== null) {
                    $entity_name = Dropdown::getDropdownName('glpi_entities', $data['entities_id']);
                }
                $entries[] = [
                    'itemtype'     => 'Group_KnowbaseItem',
                    'id'           => $data['id'],
                    'type'         => 'Group',
                    'type_label'   => Group::getTypeName(1),
                    'name'         => Dropdown::getDropdownName('glpi_groups', $data['groups_id']),
                    'icon'         => 'ti-users-group',
                    'badge_class'  => 'bg-green-lt',
                    'entity_name'  => $entity_name,
                    'is_recursive' => (bool) $data['is_recursive'],
                    'is_owner'     => false,
                ];
            }
        }

        $entities = Entity_KnowbaseItem::getEntities($id);
        foreach ($entities as $val) {
            foreach ($val as $data) {
                $entries[] = [
                    'itemtype'     => 'Entity_KnowbaseItem',
                    'id'           => $data['id'],
                    'type'         => 'Entity',
                    'type_label'   => Entity::getTypeName(1),
                    'name'         => Dropdown::getDropdownName('glpi_entities', $data['entities_id']),
                    'icon'         => 'ti-building',
                    'badge_class'  => 'bg-yellow-lt',
                    'entity_name'  => null,
                    'is_recursive' => (bool) $data['is_recursive'],
                    'is_owner'     => false,
                ];
            }
        }

        $profiles = KnowbaseItem_Profile::getProfiles($id);
        foreach ($profiles as $val) {
            foreach ($val as $data) {
                $entity_name = null;
                if ($data['entities_id'] !== null) {
                    $entity_name = Dropdown::getDropdownName('glpi_entities', $data['entities_id']);
                }
                $entries[] = [
                    'itemtype'     => 'KnowbaseItem_Profile',
                    'id'           => $data['id'],
                    'type'         => 'Profile',
                    'type_label'   => Profile::getTypeName(1),
                    'name'         => Dropdown::getDropdownName('glpi_profiles', $data['profiles_id']),
                    'icon'         => 'ti-id-badge-2',
                    'badge_class'  => 'bg-purple-lt',
                    'entity_name'  => $entity_name,
                    'is_recursive' => (bool) $data['is_recursive'],
                    'is_owner'     => false,
                ];
            }
        }

        return $entries;
    }
}
