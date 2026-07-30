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
use Entity_KnowbaseItem;
use Group_KnowbaseItem;
use KnowbaseItem;
use KnowbaseItem_Profile;
use KnowbaseItem_User;
use Override;
use Session;

/**
 * @phpstan-type PermissionEntry array{
 *     itemtype: class-string,
 *     id: int,
 *     type: string,
 *     name: string,
 *     users_id: int|null,
 *     icon: string,
 *     badge_class: string,
 *     entity_name: string|null,
 *     entities_id: int|null,
 *     is_recursive: bool,
 * }
 */
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

        $entries = $this->buildEntries($item);

        $visiblity_dropdown_params = [
            'type'  => '__VALUE__',
            'right' => ($item->getField('is_faq') ? 'faq' : 'knowbase'),
            'allusers' => 1,
            'knowbaseitems_id' => $id,
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
            'entries' => $entries,
            'visiblity_dropdown_params' => $visiblity_dropdown_params,
        ];
    }

    /**
     * Build a single permission list entry for a freshly added relation.
     *
     * @param class-string $class The visibility relation class
     * @param int          $relation_id The relation ID
     * @return PermissionEntry|null
     */
    public function buildEntry(string $class, int $relation_id): ?array
    {
        $relation = getItemForItemtype($class);
        if (!$relation || !$relation->getFromDB($relation_id)) {
            return null;
        }

        return match ($class) {
            KnowbaseItem_User::class    => $this->formatUserEntry($relation->fields),
            Group_KnowbaseItem::class   => $this->formatGroupEntry($relation->fields),
            Entity_KnowbaseItem::class  => $this->formatEntityEntry($relation->fields),
            KnowbaseItem_Profile::class => $this->formatProfileEntry($relation->fields),
            default                     => null,
        };
    }

    /**
     * Build entries array for the permissions list
     *
     * @param KnowbaseItem $item
     * @return list<PermissionEntry>
     */
    private function buildEntries(KnowbaseItem $item): array
    {
        $id = $item->getID();
        $entries = [];

        foreach (KnowbaseItem_User::getUsers($id) as $val) {
            foreach ($val as $data) {
                $entries[] = $this->formatUserEntry($data);
            }
        }

        foreach (Group_KnowbaseItem::getGroups($id) as $val) {
            foreach ($val as $data) {
                $entries[] = $this->formatGroupEntry($data);
            }
        }

        foreach (Entity_KnowbaseItem::getEntities($id) as $val) {
            foreach ($val as $data) {
                $entries[] = $this->formatEntityEntry($data);
            }
        }

        foreach (KnowbaseItem_Profile::getProfiles($id) as $val) {
            foreach ($val as $data) {
                $entries[] = $this->formatProfileEntry($data);
            }
        }

        return $entries;
    }

    /**
     * @param array<string, mixed> $data
     * @return PermissionEntry
     */
    private function formatUserEntry(array $data): array
    {
        return [
            'itemtype'     => KnowbaseItem_User::class,
            'id'           => (int) $data['id'],
            'type'         => 'User',
            'name'         => getUserName((int) $data['users_id']),
            'users_id'     => (int) $data['users_id'],
            'icon'         => 'ti-user',
            'badge_class'  => 'bg-azure-lt',
            'entity_name'  => null,
            'entities_id'  => null,
            'is_recursive' => false,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return PermissionEntry
     */
    private function formatGroupEntry(array $data): array
    {
        $entities_id = $data['entities_id'] !== null ? (int) $data['entities_id'] : null;
        $entity_name = $entities_id !== null
            ? Dropdown::getDropdownName('glpi_entities', $entities_id)
            : null;
        return [
            'itemtype'     => Group_KnowbaseItem::class,
            'id'           => (int) $data['id'],
            'type'         => 'Group',
            'name'         => Dropdown::getDropdownName('glpi_groups', $data['groups_id']),
            'users_id'     => null,
            'icon'         => 'ti-users-group',
            'badge_class'  => 'bg-green-lt',
            'entity_name'  => $entity_name,
            'entities_id'  => $entities_id,
            'is_recursive' => (bool) $data['is_recursive'],
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return PermissionEntry
     */
    private function formatEntityEntry(array $data): array
    {
        return [
            'itemtype'     => Entity_KnowbaseItem::class,
            'id'           => (int) $data['id'],
            'type'         => 'Entity',
            'name'         => Dropdown::getDropdownName('glpi_entities', $data['entities_id']),
            'users_id'     => null,
            'icon'         => 'ti-building',
            'badge_class'  => 'bg-yellow-lt',
            'entity_name'  => null,
            'entities_id'  => $data['entities_id'] !== null ? (int) $data['entities_id'] : null,
            'is_recursive' => (bool) $data['is_recursive'],
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return PermissionEntry
     */
    private function formatProfileEntry(array $data): array
    {
        $entities_id = $data['entities_id'] !== null ? (int) $data['entities_id'] : null;
        $entity_name = $entities_id !== null
            ? Dropdown::getDropdownName('glpi_entities', $entities_id)
            : null;
        return [
            'itemtype'     => KnowbaseItem_Profile::class,
            'id'           => (int) $data['id'],
            'type'         => 'Profile',
            'name'         => Dropdown::getDropdownName('glpi_profiles', $data['profiles_id']),
            'users_id'     => null,
            'icon'         => 'ti-id-badge-2',
            'badge_class'  => 'bg-purple-lt',
            'entity_name'  => $entity_name,
            'entities_id'  => $entities_id,
            'is_recursive' => (bool) $data['is_recursive'],
        ];
    }
}
